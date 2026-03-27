<?php

namespace Modules\DoubleEntry\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Support\Facades\DB;
use Modules\DoubleEntry\Http\Requests\JournalStore;
use Modules\DoubleEntry\Http\Requests\JournalUpdate;
use Modules\DoubleEntry\Models\Account;
use Modules\DoubleEntry\Models\Journal;
use Modules\DoubleEntry\Models\JournalLine;

class Journals extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $journals = Journal::where('company_id', company_id())
            ->with('lines.account')
            ->orderBy('date', 'desc')
            ->paginate(25);

        return view('double-entry::journals.index', compact('journals'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $accounts = $this->getAccountOptions();

        return view('double-entry::journals.create', compact('accounts'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(JournalStore $request)
    {
        $journal = DB::transaction(fn () => $this->persistJournal(new Journal(), $request));

        $message = trans('messages.success.added', ['type' => trans('double-entry::general.journal_entry')]);

        flash($message)->success();

        return response()->json([
            'success' => true,
            'error' => false,
            'message' => $message,
            'redirect' => route('double-entry.journals.index'),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        $journal = Journal::where('company_id', company_id())
            ->with('lines.account')
            ->findOrFail($id);

        return view('double-entry::journals.show', compact('journal'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id)
    {
        $journal = Journal::where('company_id', company_id())
            ->with('lines')
            ->findOrFail($id);

        $accounts = $this->getAccountOptions();

        return view('double-entry::journals.edit', compact('journal', 'accounts'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(int $id, JournalUpdate $request)
    {
        $journal = Journal::where('company_id', company_id())->findOrFail($id);

        DB::transaction(fn () => $this->persistJournal($journal, $request));

        $message = trans('messages.success.updated', ['type' => trans('double-entry::general.journal_entry')]);

        flash($message)->success();

        return response()->json([
            'success' => true,
            'error' => false,
            'message' => $message,
            'redirect' => route('double-entry.journals.index'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $journal = Journal::where('company_id', company_id())->findOrFail($id);

        if ($journal->status === 'posted') {
            return response()->json([
                'success' => false,
                'error' => true,
                'message' => trans('double-entry::general.error.posted_journal'),
            ]);
        }

        $journal->lines()->delete();
        $journal->delete();

        $message = trans('messages.success.deleted', ['type' => trans('double-entry::general.journal_entry')]);

        flash($message)->success();

        return response()->json([
            'success' => true,
            'error' => false,
            'message' => $message,
            'redirect' => route('double-entry.journals.index'),
        ]);
    }

    protected function persistJournal(Journal $journal, $request): Journal
    {
        $journal->fill([
            'company_id' => company_id(),
            'number' => $request->get('number'),
            'date' => $request->get('date'),
            'description' => $request->get('description'),
            'reference' => $request->get('reference'),
            'status' => $request->get('status', 'draft'),
            'is_recurring' => (bool) $request->get('is_recurring', false),
            'recurring_frequency' => $request->get('is_recurring') ? $request->get('recurring_frequency') : null,
            'next_run_at' => $request->get('is_recurring') ? $request->get('next_run_at') : null,
        ])->save();

        $journal->lines()->delete();

        foreach ($request->get('lines', []) as $line) {
            JournalLine::create([
                'company_id' => company_id(),
                'journal_id' => $journal->id,
                'account_id' => $line['account_id'],
                'debit' => $line['debit'] ?? 0,
                'credit' => $line['credit'] ?? 0,
                'description' => $line['description'] ?? null,
            ]);
        }

        return $journal->load('lines.account');
    }

    protected function getAccountOptions()
    {
        return Account::where('company_id', company_id())
            ->enabled()
            ->orderBy('code')
            ->get()
            ->mapWithKeys(function (Account $account) {
                return [$account->id => $account->code . ' - ' . $account->name];
            })
            ->toArray();
    }
}
