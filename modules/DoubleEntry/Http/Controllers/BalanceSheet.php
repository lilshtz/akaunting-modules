<?php

namespace Modules\DoubleEntry\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Modules\DoubleEntry\Models\Account;
use Modules\DoubleEntry\Services\AccountBalanceService;

class BalanceSheet extends Controller
{
    protected AccountBalanceService $balanceService;

    public function __construct(AccountBalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    public function index(Request $request): Response
    {
        $companyId = company_id();
        $asOfDate = $request->get('as_of_date', now()->toDateString());
        $basis = $request->get('basis', 'accrual');
        $comparative = $request->boolean('comparative', false);

        $data = $this->buildBalanceSheet($companyId, $asOfDate, $basis);

        $priorData = null;
        if ($comparative) {
            $priorDate = now()->parse($asOfDate)->subYear()->toDateString();
            $priorData = $this->buildBalanceSheet($companyId, $priorDate, $basis);
        }

        return $this->response('double-entry::balance-sheet.index', compact(
            'data', 'priorData', 'asOfDate', 'basis', 'comparative'
        ));
    }

    public function export(Request $request): \Illuminate\Http\Response
    {
        $companyId = company_id();
        $asOfDate = $request->get('as_of_date', now()->toDateString());
        $basis = $request->get('basis', 'accrual');
        $format = $request->get('format', 'csv');
        $comparative = $request->boolean('comparative', false);

        $data = $this->buildBalanceSheet($companyId, $asOfDate, $basis);

        $priorData = null;
        if ($comparative) {
            $priorDate = now()->parse($asOfDate)->subYear()->toDateString();
            $priorData = $this->buildBalanceSheet($companyId, $priorDate, $basis);
        }

        if ($format === 'pdf') {
            $pdf = app('dompdf.wrapper');
            $pdf->loadView('double-entry::balance-sheet.pdf', compact('data', 'priorData', 'asOfDate', 'basis', 'comparative'));

            return $pdf->download('balance-sheet.pdf');
        }

        $filename = 'balance-sheet-' . $asOfDate . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($data, $priorData, $comparative) {
            $handle = fopen('php://output', 'w');

            $header = ['Account Code', 'Account Name', 'Balance'];
            if ($comparative && $priorData) {
                $header[] = 'Prior Period';
            }
            fputcsv($handle, $header);

            foreach (['assets' => 'ASSETS', 'liabilities' => 'LIABILITIES', 'equity' => 'EQUITY'] as $key => $label) {
                fputcsv($handle, ['', $label, '']);

                foreach ($data[$key] as $group) {
                    if (!empty($group['label'])) {
                        fputcsv($handle, ['', '  ' . $group['label'], '']);
                    }
                    foreach ($group['accounts'] as $row) {
                        $indent = $row['account']->parent_id ? '    ' : '  ';
                        $line = [$row['account']->code, $indent . $row['account']->name, number_format($row['balance'], 2)];
                        if ($comparative && $priorData) {
                            $priorGroup = collect($priorData[$key])->firstWhere('label', $group['label']);
                            $priorRow = $priorGroup ? collect($priorGroup['accounts'])->firstWhere('account.id', $row['account']->id ?? null) : null;
                            $line[] = $priorRow ? number_format($priorRow['balance'], 2) : '-';
                        }
                        fputcsv($handle, $line);
                    }
                    if (!empty($group['label']) && !empty($group['subtotal'])) {
                        fputcsv($handle, ['', '  Subtotal: ' . $group['label'], number_format($group['subtotal'], 2)]);
                    }
                }

                $totalKey = 'total_' . rtrim($key, 's');
                if ($key === 'assets') {
                    $totalKey = 'total_assets';
                } elseif ($key === 'liabilities') {
                    $totalKey = 'total_liabilities';
                } elseif ($key === 'equity') {
                    $totalKey = 'total_equity';
                }
                fputcsv($handle, ['', 'Total ' . $label, number_format($data[$totalKey], 2)]);
            }

            fputcsv($handle, ['', 'Total Liabilities & Equity', number_format($data['total_liabilities'] + $data['total_equity'], 2)]);

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    protected function buildBalanceSheet(int $companyId, string $asOfDate, string $basis): array
    {
        $accounts = Account::where('company_id', $companyId)
            ->enabled()
            ->whereIn('type', ['asset', 'liability', 'equity'])
            ->with('parent')
            ->orderBy('type')
            ->orderBy('code')
            ->get();

        // Build hierarchical structure: group by type, then by parent
        $grouped = ['asset' => [], 'liability' => [], 'equity' => []];
        $parentNames = [];

        foreach ($accounts as $account) {
            $balance = $this->balanceService->getBalance($account->id, $asOfDate, $basis);

            if (abs($balance) < 0.005) {
                continue;
            }

            $row = ['account' => $account, 'balance' => $balance];

            // Group by parent account for hierarchy
            $parentKey = $account->parent_id ?: 0;
            if ($account->parent_id && $account->parent) {
                $parentNames[$account->parent_id] = $account->parent->name;
            }

            $grouped[$account->type][$parentKey][] = $row;
        }

        // Build section arrays with subgroups
        $assets = $this->buildHierarchicalSection($grouped['asset'], $parentNames);
        $liabilities = $this->buildHierarchicalSection($grouped['liability'], $parentNames);
        $equity = $this->buildHierarchicalSection($grouped['equity'], $parentNames);

        $totalAssets = $this->sumSection($assets);
        $totalLiabilities = $this->sumSection($liabilities);
        $totalEquity = $this->sumSection($equity);

        // Calculate retained earnings (net income for the period)
        $netIncome = $this->calculateNetIncome($companyId, $asOfDate, $basis);
        if (abs($netIncome) >= 0.005) {
            $equity[] = [
                'label' => '',
                'subtotal' => 0,
                'accounts' => [
                    [
                        'account' => (object) [
                            'id' => null,
                            'code' => '',
                            'name' => 'Net Income (Current Period)',
                            'parent_id' => null,
                            'display_name' => 'Net Income (Current Period)',
                        ],
                        'balance' => $netIncome,
                    ],
                ],
            ];
            $totalEquity += $netIncome;
        }

        return [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'total_equity' => $totalEquity,
            'is_balanced' => abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.01,
        ];
    }

    /**
     * Build hierarchical section with parent groupings and subtotals.
     */
    protected function buildHierarchicalSection(array $groupedByParent, array $parentNames): array
    {
        $sections = [];

        // First add root-level accounts (no parent)
        if (!empty($groupedByParent[0])) {
            $sections[] = [
                'label' => '',
                'subtotal' => 0,
                'accounts' => $groupedByParent[0],
            ];
        }

        // Then add parent-grouped accounts
        foreach ($groupedByParent as $parentId => $rows) {
            if ($parentId === 0) {
                continue;
            }

            $subtotal = array_sum(array_column($rows, 'balance'));
            $sections[] = [
                'label' => $parentNames[$parentId] ?? 'Other',
                'subtotal' => $subtotal,
                'accounts' => $rows,
            ];
        }

        return $sections;
    }

    protected function sumSection(array $sections): float
    {
        $total = 0;
        foreach ($sections as $group) {
            foreach ($group['accounts'] as $row) {
                $total += $row['balance'];
            }
        }
        return $total;
    }

    protected function calculateNetIncome(int $companyId, string $asOfDate, string $basis): float
    {
        $incomeAccounts = Account::where('company_id', $companyId)
            ->enabled()
            ->where('type', 'income')
            ->get();

        $expenseAccounts = Account::where('company_id', $companyId)
            ->enabled()
            ->where('type', 'expense')
            ->get();

        $totalIncome = 0;
        foreach ($incomeAccounts as $account) {
            $totalIncome += $this->balanceService->getBalance($account->id, $asOfDate, $basis);
        }

        $totalExpenses = 0;
        foreach ($expenseAccounts as $account) {
            $totalExpenses += $this->balanceService->getBalance($account->id, $asOfDate, $basis);
        }

        return $totalIncome - $totalExpenses;
    }
}
