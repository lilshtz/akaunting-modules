<?php

namespace Modules\ExpenseClaims\Http\Controllers;

use App\Abstracts\Http\Controller;
use App\Models\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Modules\Employees\Models\Employee;
use Modules\ExpenseClaims\Http\Requests\ClaimStore;
use Modules\ExpenseClaims\Http\Requests\ClaimUpdate;
use Modules\ExpenseClaims\Models\ExpenseClaim;
use Modules\ExpenseClaims\Models\ExpenseClaimCategory;
use Modules\ExpenseClaims\Notifications\ClaimStatusUpdated;
use Modules\ExpenseClaims\Notifications\ClaimSubmitted;
use Modules\ExpenseClaims\Services\ReimbursementService;

class Claims extends Controller
{
    public function __construct(protected ReimbursementService $reimbursements)
    {
    }

    public function index(Request $request): Response
    {
        $query = ExpenseClaim::where('company_id', company_id())
            ->with(['employee.contact', 'approver', 'items.category']);

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->integer('employee_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('search')) {
            $search = $request->get('search');

            $query->where(function ($builder) use ($search) {
                $builder->where('description', 'like', '%' . $search . '%')
                    ->orWhere('claim_number', 'like', '%' . $search . '%')
                    ->orWhereHas('employee.contact', function ($contactQuery) use ($search) {
                        $contactQuery->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        $claims = $query->orderByDesc('created_at')->paginate(25);

        $employees = Employee::where('company_id', company_id())->with('contact')->get()->pluck('name', 'id');
        $statuses = collect(ExpenseClaim::STATUSES)->mapWithKeys(fn ($status) => [$status => trans('expense-claims::general.statuses.' . $status)]);

        return view('expense-claims::claims.index', compact('claims', 'employees', 'statuses'));
    }

    public function create(): Response
    {
        return view('expense-claims::claims.create', $this->formData());
    }

    public function store(ClaimStore $request): Response
    {
        $claim = ExpenseClaim::create([
            'company_id' => company_id(),
            'employee_id' => $request->integer('employee_id'),
            'approver_id' => $request->integer('approver_id') ?: null,
            'status' => ExpenseClaim::STATUS_DRAFT,
            'claim_number' => $this->generateClaimNumber(),
            'description' => $request->get('description'),
            'due_date' => $request->get('due_date'),
        ]);

        $this->syncItems($claim, $request);
        $claim->recalculateTotals();

        flash(trans('messages.success.added', ['type' => trans('expense-claims::general.claim')]))->success();

        return redirect()->route('expense-claims.claims.show', $claim->id);
    }

    public function show(int $id): Response
    {
        $claim = ExpenseClaim::where('company_id', company_id())
            ->with(['employee.contact', 'approver', 'items.category', 'reimbursementDocument', 'reimbursementTransaction'])
            ->findOrFail($id);

        return view('expense-claims::claims.show', compact('claim'));
    }

    public function edit(int $id): Response
    {
        $claim = ExpenseClaim::where('company_id', company_id())
            ->with('items')
            ->findOrFail($id);

        if (! in_array($claim->status, [ExpenseClaim::STATUS_DRAFT, ExpenseClaim::STATUS_REFUSED], true)) {
            flash(trans('expense-claims::general.messages.not_editable'))->warning();

            return redirect()->route('expense-claims.claims.show', $claim->id);
        }

        return view('expense-claims::claims.edit', array_merge($this->formData(), compact('claim')));
    }

    public function update(int $id, ClaimUpdate $request): Response
    {
        $claim = ExpenseClaim::where('company_id', company_id())->findOrFail($id);

        if (! in_array($claim->status, [ExpenseClaim::STATUS_DRAFT, ExpenseClaim::STATUS_REFUSED], true)) {
            flash(trans('expense-claims::general.messages.not_editable'))->warning();

            return redirect()->route('expense-claims.claims.show', $claim->id);
        }

        $claim->update([
            'employee_id' => $request->integer('employee_id'),
            'approver_id' => $request->integer('approver_id') ?: null,
            'description' => $request->get('description'),
            'due_date' => $request->get('due_date'),
            'refusal_reason' => null,
            'refused_at' => null,
            'status' => ExpenseClaim::STATUS_DRAFT,
        ]);

        $this->deleteStoredReceipts($claim);
        $claim->items()->delete();
        $this->syncItems($claim, $request);
        $claim->recalculateTotals();

        flash(trans('messages.success.updated', ['type' => trans('expense-claims::general.claim')]))->success();

        return redirect()->route('expense-claims.claims.show', $claim->id);
    }

    public function destroy(int $id): Response
    {
        $claim = ExpenseClaim::where('company_id', company_id())->findOrFail($id);

        if (in_array($claim->status, [ExpenseClaim::STATUS_APPROVED, ExpenseClaim::STATUS_PAID], true)) {
            flash(trans('expense-claims::general.messages.not_deletable'))->warning();

            return redirect()->route('expense-claims.claims.index');
        }

        $this->deleteStoredReceipts($claim);
        $claim->delete();

        flash(trans('messages.success.deleted', ['type' => trans('expense-claims::general.claim')]))->success();

        return redirect()->route('expense-claims.claims.index');
    }

    public function submit(int $id): Response
    {
        $claim = ExpenseClaim::where('company_id', company_id())->with(['employee.user', 'approver'])->findOrFail($id);

        if ($claim->items()->count() === 0) {
            flash(trans('expense-claims::general.messages.no_items'))->warning();

            return redirect()->route('expense-claims.claims.show', $claim->id);
        }

        $claim->update([
            'status' => ExpenseClaim::STATUS_SUBMITTED,
            'submitted_at' => now(),
            'refusal_reason' => null,
            'refused_at' => null,
        ]);

        if ($claim->approver) {
            $claim->approver->notify(new ClaimSubmitted($claim));
        }

        flash(trans('expense-claims::general.messages.submitted'))->success();

        return redirect()->route('expense-claims.claims.show', $claim->id);
    }

    public function approve(Request $request, int $id): Response
    {
        $claim = ExpenseClaim::where('company_id', company_id())->with(['employee.user', 'approver'])->findOrFail($id);

        if (! in_array($claim->status, [ExpenseClaim::STATUS_SUBMITTED, ExpenseClaim::STATUS_PENDING], true)) {
            flash(trans('expense-claims::general.messages.invalid_status'))->warning();

            return redirect()->route('expense-claims.claims.show', $claim->id);
        }

        $claim->update([
            'status' => ExpenseClaim::STATUS_APPROVED,
            'approved_at' => now(),
            'refused_at' => null,
            'refusal_reason' => null,
        ]);

        $this->reimbursements->createBill($claim);

        if ($claim->employee?->user) {
            $claim->employee->user->notify(new ClaimStatusUpdated($claim, ExpenseClaim::STATUS_APPROVED));
        }

        flash(trans('expense-claims::general.messages.approved'))->success();

        return redirect()->route('expense-claims.claims.show', $claim->id);
    }

    public function refuse(Request $request, int $id): Response
    {
        $request->validate([
            'reason' => 'required|string',
        ]);

        $claim = ExpenseClaim::where('company_id', company_id())->with('employee.user')->findOrFail($id);

        if (! in_array($claim->status, [ExpenseClaim::STATUS_SUBMITTED, ExpenseClaim::STATUS_PENDING], true)) {
            flash(trans('expense-claims::general.messages.invalid_status'))->warning();

            return redirect()->route('expense-claims.claims.show', $claim->id);
        }

        $reason = $request->get('reason');

        $claim->update([
            'status' => ExpenseClaim::STATUS_REFUSED,
            'refused_at' => now(),
            'refusal_reason' => $reason,
        ]);

        if ($claim->employee?->user) {
            $claim->employee->user->notify(new ClaimStatusUpdated($claim, ExpenseClaim::STATUS_REFUSED, $reason));
        }

        flash(trans('expense-claims::general.messages.refused'))->success();

        return redirect()->route('expense-claims.claims.show', $claim->id);
    }

    public function pay(int $id): Response
    {
        $claim = ExpenseClaim::where('company_id', company_id())->with('employee.user')->findOrFail($id);

        if (! in_array($claim->status, [ExpenseClaim::STATUS_APPROVED, ExpenseClaim::STATUS_PAID], true)) {
            flash(trans('expense-claims::general.messages.invalid_status'))->warning();

            return redirect()->route('expense-claims.claims.show', $claim->id);
        }

        if (! $claim->reimbursement_document_id && $claim->reimbursable_total > 0) {
            $this->reimbursements->createBill($claim);
        }

        $this->reimbursements->createPayment($claim);

        $claim->update([
            'status' => ExpenseClaim::STATUS_PAID,
            'paid_at' => now(),
        ]);

        if ($claim->employee?->user) {
            $claim->employee->user->notify(new ClaimStatusUpdated($claim, ExpenseClaim::STATUS_PAID));
        }

        flash(trans('expense-claims::general.messages.paid'))->success();

        return redirect()->route('expense-claims.claims.show', $claim->id);
    }

    public function export(Request $request)
    {
        $claims = ExpenseClaim::where('company_id', company_id())
            ->with(['employee.contact', 'items.category'])
            ->orderByDesc('created_at')
            ->get();

        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, [
            'claim_number',
            'employee',
            'approver_id',
            'status',
            'description',
            'due_date',
            'item_date',
            'item_category',
            'item_description',
            'item_amount',
            'paid_by_employee',
            'item_notes',
        ]);

        foreach ($claims as $claim) {
            foreach ($claim->items as $item) {
                fputcsv($stream, [
                    $claim->claim_number,
                    $claim->employee_name,
                    $claim->approver_id,
                    $claim->status,
                    $claim->description,
                    optional($claim->due_date)->toDateString(),
                    optional($item->date)->toDateString(),
                    $item->category?->name,
                    $item->description,
                    $item->amount,
                    $item->paid_by_employee ? 1 : 0,
                    $item->notes,
                ]);
            }
        }

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="expense-claims.csv"',
        ]);
    }

    public function import(): Response
    {
        return view('expense-claims::claims.import');
    }

    public function importStore(Request $request): Response
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = fgetcsv($handle);
        $grouped = [];

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);
            $claimNumber = $data['claim_number'] ?: $this->generateClaimNumber();
            $grouped[$claimNumber][] = $data;
        }

        fclose($handle);

        foreach ($grouped as $claimNumber => $rows) {
            $employee = Employee::where('company_id', company_id())
                ->with('contact')
                ->get()
                ->first(fn ($item) => $item->name === $rows[0]['employee']);

            if (! $employee) {
                continue;
            }

            $claim = ExpenseClaim::create([
                'company_id' => company_id(),
                'employee_id' => $employee->id,
                'approver_id' => $rows[0]['approver_id'] ?: null,
                'status' => $rows[0]['status'] ?: ExpenseClaim::STATUS_DRAFT,
                'claim_number' => $claimNumber,
                'description' => $rows[0]['description'] ?: null,
                'due_date' => $rows[0]['due_date'] ?: null,
            ]);

            foreach ($rows as $row) {
                $category = null;

                if (! empty($row['item_category'])) {
                    $category = ExpenseClaimCategory::firstOrCreate(
                        ['company_id' => company_id(), 'name' => $row['item_category']],
                        ['enabled' => true]
                    );
                }

                $claim->items()->create([
                    'category_id' => $category?->id,
                    'date' => $row['item_date'] ?: now()->toDateString(),
                    'description' => $row['item_description'] ?: trans('general.na'),
                    'amount' => $row['item_amount'] ?: 0,
                    'notes' => $row['item_notes'] ?: null,
                    'paid_by_employee' => (bool) ($row['paid_by_employee'] ?? true),
                ]);
            }

            $claim->recalculateTotals();
        }

        flash(trans('expense-claims::general.messages.imported'))->success();

        return redirect()->route('expense-claims.claims.index');
    }

    public function pdf(int $id)
    {
        $claim = ExpenseClaim::where('company_id', company_id())
            ->with(['employee.contact', 'approver', 'items.category'])
            ->findOrFail($id);

        $html = view('expense-claims::claims.pdf', compact('claim'))->render();

        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->download(($claim->claim_number ?: 'expense-claim-' . $claim->id) . '.pdf');
        }

        return response($html);
    }

    protected function formData(): array
    {
        $employees = Employee::where('company_id', company_id())->with('contact')->get()->pluck('name', 'id');
        $approvers = User::orderBy('name')->pluck('name', 'id');
        $categories = ExpenseClaimCategory::where('company_id', company_id())->enabled()->orderBy('name')->pluck('name', 'id');
        $statuses = collect(ExpenseClaim::STATUSES)->mapWithKeys(fn ($status) => [$status => trans('expense-claims::general.statuses.' . $status)]);

        return compact('employees', 'approvers', 'categories', 'statuses');
    }

    protected function syncItems(ExpenseClaim $claim, Request $request): void
    {
        foreach ((array) $request->get('items', []) as $index => $item) {
            $receiptPath = null;
            $uploadedFile = data_get($request->file('items', []), $index . '.receipt');

            if ($uploadedFile) {
                $receiptPath = $uploadedFile->store('expense-claims/' . company_id(), 'public');
            }

            $claim->items()->create([
                'category_id' => $item['category_id'] ?: null,
                'date' => $item['date'],
                'description' => $item['description'],
                'amount' => $item['amount'],
                'receipt_path' => $receiptPath,
                'notes' => $item['notes'] ?? null,
                'paid_by_employee' => (bool) ($item['paid_by_employee'] ?? true),
            ]);
        }
    }

    protected function deleteStoredReceipts(ExpenseClaim $claim): void
    {
        foreach ($claim->items as $item) {
            if ($item->receipt_path) {
                Storage::disk('public')->delete($item->receipt_path);
            }
        }
    }

    protected function generateClaimNumber(): string
    {
        $key = 'expense_claims.number_next';
        $next = (int) setting($key, 1);
        $number = 'EXP-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);

        setting([$key => $next + 1]);
        setting()->save();

        return $number;
    }
}
