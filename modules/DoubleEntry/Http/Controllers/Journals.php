<?php

namespace Modules\DoubleEntry\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\DoubleEntry\Http\Requests\JournalStore;
use Modules\DoubleEntry\Http\Requests\JournalUpdate;
use Modules\DoubleEntry\Models\Journal;
use Modules\DoubleEntry\Services\AccountBalanceService;

class Journals extends Controller
{
    public function __construct(protected AccountBalanceService $service)
    {
    }

    public function index(Request $request)
    {
        $journals = Journal::where('company_id', company_id())
            ->withCount('lines')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->get('status')))
            ->orderByDesc('date')
            ->paginate(25);

        return view('double-entry::journals.index', compact('journals'));
    }

    public function create()
    {
        return view('double-entry::journals.create', [
            'accounts' => $this->service->accountOptions(),
            'journalNumber' => $this->service->nextJournalNumber(),
        ]);
    }

    public function store(JournalStore $request): RedirectResponse
    {
        if (!$this->service->journalIsBalanced($request->get('lines', []))) {
            return back()->withInput()->withErrors(['lines' => trans('double-entry::general.balanced_required')]);
        }

        $journal = $this->service->syncJournal(new Journal(), [
            'number' => $this->service->nextJournalNumber(),
            'date' => $request->get('date'),
            'reference' => $request->get('reference'),
            'description' => $request->get('description'),
            'is_recurring' => $request->boolean('is_recurring'),
            'recurring_frequency' => $request->get('recurring_frequency'),
            'next_run_at' => $request->get('next_run_at'),
            'status' => Journal::STATUS_DRAFT,
        ], $request->get('lines', []), false);

        flash(trans('messages.success.added', ['type' => trans('double-entry::general.journal')]))->success();

        return redirect()->route('double-entry.journals.show', $journal->id);
    }

    public function show($journal)
    {
        $journal = $this->journal($journal)->load('lines.account');

        return view('double-entry::journals.show', compact('journal'));
    }

    public function edit($journal)
    {
        $journal = $this->journal($journal)->load('lines');

        abort_if($journal->status !== Journal::STATUS_DRAFT, 403);

        return view('double-entry::journals.edit', [
            'journal' => $journal,
            'accounts' => $this->service->accountOptions(),
        ]);
    }

    public function update(JournalUpdate $request, $journal): RedirectResponse
    {
        $journal = $this->journal($journal);

        abort_if($journal->status !== Journal::STATUS_DRAFT, 403);

        if (!$this->service->journalIsBalanced($request->get('lines', []))) {
            return back()->withInput()->withErrors(['lines' => trans('double-entry::general.balanced_required')]);
        }

        $this->service->syncJournal($journal, [
            'date' => $request->get('date'),
            'reference' => $request->get('reference'),
            'description' => $request->get('description'),
            'is_recurring' => $request->boolean('is_recurring'),
            'recurring_frequency' => $request->get('recurring_frequency'),
            'next_run_at' => $request->get('next_run_at'),
            'status' => Journal::STATUS_DRAFT,
        ], $request->get('lines', []), false);

        flash(trans('messages.success.updated', ['type' => trans('double-entry::general.journal')]))->success();

        return redirect()->route('double-entry.journals.show', $journal->id);
    }

    public function post($journal): RedirectResponse
    {
        $journal = $this->journal($journal)->load('lines');

        abort_if($journal->status !== Journal::STATUS_DRAFT, 403);

        if (!$this->service->journalIsBalanced($journal->lines->toArray())) {
            return back()->withErrors(['lines' => trans('double-entry::general.balanced_required')]);
        }

        $journal->update([
            'status' => Journal::STATUS_POSTED,
            'posted_at' => now(),
            'updated_by' => auth()->id(),
        ]);

        flash(trans('messages.success.updated', ['type' => trans('double-entry::general.status')]))->success();

        return redirect()->route('double-entry.journals.show', $journal->id);
    }

    public function void(Request $request, $journal): RedirectResponse
    {
        $journal = $this->journal($journal);

        abort_if($journal->status === Journal::STATUS_VOIDED, 403);

        $this->service->voidJournal($journal, $request->get('reason'));

        flash(trans('messages.success.updated', ['type' => trans('double-entry::general.status')]))->success();

        return redirect()->route('double-entry.journals.show', $journal->id);
    }

    public function destroy($journal): RedirectResponse
    {
        $journal = $this->journal($journal);

        abort_if($journal->status === Journal::STATUS_POSTED, 403);

        $journal->delete();

        flash(trans('messages.success.deleted', ['type' => trans('double-entry::general.journal')]))->success();

        return redirect()->route('double-entry.journals.index');
    }

    protected function journal($journal): Journal
    {
        return Journal::where('company_id', company_id())->findOrFail($journal);
    }
}
