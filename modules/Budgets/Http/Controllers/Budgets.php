<?php

namespace Modules\Budgets\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Modules\Budgets\Http\Requests\BudgetStore;
use Modules\Budgets\Http\Requests\BudgetUpdate;
use Modules\Budgets\Models\Budget;
use Modules\DoubleEntry\Models\Account;

class Budgets extends Controller
{
    public function index(Request $request): Response|mixed
    {
        $query = Budget::where('company_id', company_id())
            ->withCount('lines')
            ->with('lines');

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('search')) {
            $search = $request->get('search');

            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', '%' . $search . '%')
                    ->orWhere('scenario', 'like', '%' . $search . '%');
            });
        }

        $budgets = $query->orderByDesc('period_start')->orderByDesc('id')->paginate(20);
        $statuses = collect([
            Budget::STATUS_DRAFT,
            Budget::STATUS_ACTIVE,
            Budget::STATUS_CLOSED,
        ])->mapWithKeys(fn ($status) => [$status => trans('budgets::general.statuses.' . $status)]);

        return view('budgets::budgets.index', compact('budgets', 'statuses'));
    }

    public function create(Request $request): Response|mixed
    {
        $budget = new Budget([
            'period_type' => Budget::PERIOD_ANNUAL,
            'period_start' => now()->startOfYear()->toDateString(),
            'period_end' => now()->endOfYear()->toDateString(),
            'status' => Budget::STATUS_DRAFT,
            'scenario' => 'realistic',
        ]);

        $lineItems = [];

        if ($request->filled('copy_budget_id')) {
            $source = $this->findBudget($request->integer('copy_budget_id'));
            $budget = $this->prefillFromBudget($budget, $source);
            $lineItems = $source->lines->map(fn ($line) => [
                'account_id' => $line->account_id,
                'amount' => $line->amount,
            ])->all();
        }

        return view('budgets::budgets.create', array_merge($this->formData(), compact('budget', 'lineItems')));
    }

    public function store(BudgetStore $request): Response|mixed
    {
        $budget = Budget::create([
            'company_id' => company_id(),
            'name' => $request->get('name'),
            'period_type' => $request->get('period_type'),
            'scenario' => $request->get('scenario'),
            'period_start' => $request->get('period_start'),
            'period_end' => $request->get('period_end'),
            'status' => $request->get('status'),
        ]);

        $this->syncLines($budget, $request->get('lines', []));

        flash(trans('messages.success.added', ['type' => trans('budgets::general.budget')]))->success();

        return redirect()->route('budgets.budgets.show', $budget->id);
    }

    public function show(int $id): Response|mixed
    {
        $budget = $this->findBudget($id);

        return view('budgets::budgets.show', compact('budget'));
    }

    public function edit(int $id): Response|mixed
    {
        $budget = $this->findBudget($id);
        $lineItems = $budget->lines->map(fn ($line) => [
            'account_id' => $line->account_id,
            'amount' => $line->amount,
        ])->all();

        return view('budgets::budgets.edit', array_merge($this->formData(), compact('budget', 'lineItems')));
    }

    public function update(int $id, BudgetUpdate $request): Response|mixed
    {
        $budget = $this->findBudget($id);

        $budget->update([
            'name' => $request->get('name'),
            'period_type' => $request->get('period_type'),
            'scenario' => $request->get('scenario'),
            'period_start' => $request->get('period_start'),
            'period_end' => $request->get('period_end'),
            'status' => $request->get('status'),
        ]);

        $budget->lines()->delete();
        $this->syncLines($budget, $request->get('lines', []));

        flash(trans('messages.success.updated', ['type' => trans('budgets::general.budget')]))->success();

        return redirect()->route('budgets.budgets.show', $budget->id);
    }

    public function destroy(int $id): Response|mixed
    {
        $budget = $this->findBudget($id);
        $budget->delete();

        flash(trans('messages.success.deleted', ['type' => trans('budgets::general.budget')]))->success();

        return redirect()->route('budgets.budgets.index');
    }

    public function copy(int $id): Response|mixed
    {
        $source = $this->findBudget($id);
        $start = $this->shiftDate($source->period_start, $source->period_type);
        $end = $this->shiftDate($source->period_end, $source->period_type);

        $copy = Budget::create([
            'company_id' => company_id(),
            'name' => $source->name . ' ' . trans('budgets::general.copy_suffix'),
            'period_type' => $source->period_type,
            'scenario' => $source->scenario,
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
            'status' => Budget::STATUS_DRAFT,
        ]);

        $this->syncLines($copy, $source->lines->map(fn ($line) => [
            'account_id' => $line->account_id,
            'amount' => $line->amount,
        ])->all());

        flash(trans('budgets::general.messages.copied'))->success();

        return redirect()->route('budgets.budgets.edit', $copy->id);
    }

    protected function formData(): array
    {
        $accounts = Account::where('company_id', company_id())
            ->enabled()
            ->orderBy('code')
            ->get();

        $accountOptions = $accounts->mapWithKeys(fn ($account) => [$account->id => $account->display_name]);
        $periodTypes = collect([
            Budget::PERIOD_MONTHLY,
            Budget::PERIOD_QUARTERLY,
            Budget::PERIOD_ANNUAL,
        ])->mapWithKeys(fn ($period) => [$period => trans('budgets::general.period_types.' . $period)]);
        $statuses = collect([
            Budget::STATUS_DRAFT,
            Budget::STATUS_ACTIVE,
            Budget::STATUS_CLOSED,
        ])->mapWithKeys(fn ($status) => [$status => trans('budgets::general.statuses.' . $status)]);
        $scenarios = collect(['optimistic', 'realistic', 'pessimistic'])
            ->mapWithKeys(fn ($scenario) => [$scenario => trans('budgets::general.scenarios.' . $scenario)]);
        $copyOptions = Budget::where('company_id', company_id())
            ->orderByDesc('period_start')
            ->get()
            ->mapWithKeys(fn ($budget) => [$budget->id => $budget->name . ' (' . $budget->period_start->toDateString() . ' - ' . $budget->period_end->toDateString() . ')']);

        return compact('accountOptions', 'periodTypes', 'statuses', 'scenarios', 'copyOptions');
    }

    protected function syncLines(Budget $budget, array $lines): void
    {
        foreach ($lines as $line) {
            if (empty($line['account_id'])) {
                continue;
            }

            $budget->lines()->create([
                'account_id' => (int) $line['account_id'],
                'amount' => (float) $line['amount'],
            ]);
        }
    }

    protected function prefillFromBudget(Budget $budget, Budget $source): Budget
    {
        $budget->name = $source->name . ' ' . trans('budgets::general.copy_suffix');
        $budget->period_type = $source->period_type;
        $budget->scenario = $source->scenario;
        $budget->period_start = $this->shiftDate($source->period_start, $source->period_type)->toDateString();
        $budget->period_end = $this->shiftDate($source->period_end, $source->period_type)->toDateString();

        return $budget;
    }

    protected function shiftDate(Carbon|string $date, string $periodType): Carbon
    {
        $date = $date instanceof Carbon ? $date->copy() : Carbon::parse($date);

        return match ($periodType) {
            Budget::PERIOD_MONTHLY => $date->addMonth(),
            Budget::PERIOD_QUARTERLY => $date->addQuarter(),
            default => $date->addYear(),
        };
    }

    protected function findBudget(int $id): Budget
    {
        return Budget::where('company_id', company_id())
            ->with(['lines.account'])
            ->findOrFail($id);
    }
}
