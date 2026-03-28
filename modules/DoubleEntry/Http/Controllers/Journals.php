<?php

namespace Modules\DoubleEntry\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\DoubleEntry\Http\Requests\JournalStore;
use Modules\DoubleEntry\Http\Requests\JournalUpdate;
use Modules\DoubleEntry\Models\Account;
use Modules\DoubleEntry\Models\Journal;
use Modules\DoubleEntry\Models\JournalLine;

class Journals extends Controller
{
    public function index(Request $request)
    {
        [$sort, $direction] = $this->validatedSort($request);

        $journals = Journal::query()
            ->byCompany()
            ->with('lines')
            ->withSum('lines as total_amount', 'debit')
            ->when($sort === 'total', fn ($query) => $query->orderBy('total_amount', $direction))
            ->when($sort !== 'total', fn ($query) => $query->orderBy($sort, $direction))
            ->orderBy('date', 'desc')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('double-entry::journals.index', compact('journals', 'sort', 'direction'));
    }

    public function create()
    {
        return view('double-entry::journals.create', [
            'accountOptions' => $this->accountOptions(),
            'suggestedReference' => Journal::generateNextReference(),
            'lineItems' => old('lines', $this->defaultLineItems()),
        ]);
    }

    public function store(JournalStore $request): RedirectResponse
    {
        $journal = null;

        DB::transaction(function () use ($request, &$journal): void {
            $journal = $this->persistJournal(new Journal(), $request->validated());
        });

        flash(trans('messages.success.added', ['type' => trans('double-entry::general.journal_entry')]))->success();

        return redirect()->route('double-entry.journals.show', $journal->id);
    }

    public function show(int $id)
    {
        $journal = $this->findJournal($id);

        return view('double-entry::journals.show', compact('journal'));
    }

    public function edit(int $id)
    {
        $journal = $this->findJournal($id);
        abort_unless($journal->isEditable(), 403);

        return view('double-entry::journals.edit', [
            'journal' => $journal,
            'accountOptions' => $this->accountOptions(),
            'suggestedReference' => $journal->reference ?: Journal::generateNextReference(),
            'lineItems' => old('lines', $journal->lines->map(function (JournalLine $line): array {
                return [
                    'account_id' => $line->account_id,
                    'debit' => $line->debit,
                    'credit' => $line->credit,
                    'description' => $line->description,
                ];
            })->all()),
        ]);
    }

    public function update(JournalUpdate $request, int $id): RedirectResponse
    {
        $journal = $this->findJournal($id);
        abort_unless($journal->isEditable(), 403);

        DB::transaction(function () use ($request, $journal): void {
            $this->persistJournal($journal, $request->validated());
        });

        flash(trans('messages.success.updated', ['type' => trans('double-entry::general.journal_entry')]))->success();

        return redirect()->route('double-entry.journals.show', $journal->id);
    }

    public function destroy(int $id): RedirectResponse
    {
        $journal = $this->findJournal($id);
        abort_unless($journal->isEditable(), 403);

        $journal->delete();

        flash(trans('messages.success.deleted', ['type' => trans('double-entry::general.journal_entry')]))->success();

        return redirect()->route('double-entry.journals.index');
    }

    public function post(int $id): RedirectResponse
    {
        $journal = $this->findJournal($id);

        if ($journal->status !== 'draft') {
            return redirect()->route('double-entry.journals.show', $journal->id);
        }

        if (! $journal->isBalanced()) {
            flash(trans('double-entry::general.messages.journal_not_balanced'))->error();

            return redirect()->route('double-entry.journals.show', $journal->id);
        }

        $journal->update(['status' => 'posted']);

        flash(trans('double-entry::general.messages.journal_posted'))->success();

        return redirect()->route('double-entry.journals.show', $journal->id);
    }

    public function unpost(int $id): RedirectResponse
    {
        $journal = $this->findJournal($id);

        if ($journal->status !== 'posted') {
            return redirect()->route('double-entry.journals.show', $journal->id);
        }

        $journal->update(['status' => 'draft']);

        flash(trans('double-entry::general.messages.journal_unposted'))->success();

        return redirect()->route('double-entry.journals.show', $journal->id);
    }

    protected function persistJournal(Journal $journal, array $validated): Journal
    {
        $journal->fill([
            'company_id' => company_id(),
            'date' => $validated['date'],
            'reference' => $validated['reference'] ?: Journal::generateNextReference(company_id()),
            'description' => $validated['description'] ?? null,
            'basis' => $validated['basis'],
            'status' => $validated['status'],
            'created_by' => $journal->exists ? $journal->created_by : Auth::id(),
        ]);

        $journal->save();

        $journal->lines()->delete();

        foreach ($validated['lines'] as $line) {
            $journal->lines()->create([
                'account_id' => (int) $line['account_id'],
                'debit' => $line['debit'] ?? 0,
                'credit' => $line['credit'] ?? 0,
                'description' => $line['description'] ?? null,
            ]);
        }

        $journal->load('lines.account', 'creator');

        if (! $journal->isBalanced()) {
            throw new \RuntimeException('Journal must remain balanced after save.');
        }

        return $journal;
    }

    protected function findJournal(int $id): Journal
    {
        return Journal::query()
            ->byCompany()
            ->with(['lines.account', 'creator'])
            ->findOrFail($id);
    }

    protected function accountOptions(): array
    {
        return Account::query()
            ->byCompany()
            ->orderBy('code')
            ->get()
            ->mapWithKeys(fn (Account $account) => [
                $account->id => $account->code . ' - ' . $account->name,
            ])
            ->all();
    }

    protected function defaultLineItems(): array
    {
        return [
            ['account_id' => null, 'debit' => null, 'credit' => null, 'description' => null],
            ['account_id' => null, 'debit' => null, 'credit' => null, 'description' => null],
        ];
    }

    protected function validatedSort(Request $request): array
    {
        $allowed = ['date', 'reference', 'description', 'status', 'total'];
        $sort = in_array($request->query('sort'), $allowed, true) ? $request->query('sort') : 'date';
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';

        return [$sort, $direction];
    }
}
