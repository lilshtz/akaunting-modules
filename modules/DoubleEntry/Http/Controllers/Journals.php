<?php

namespace Modules\DoubleEntry\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Modules\DoubleEntry\Http\Requests\JournalStore;
use Modules\DoubleEntry\Http\Requests\JournalUpdate;
use Modules\DoubleEntry\Models\Account;
use Modules\DoubleEntry\Models\Journal;

class Journals extends Controller
{
    public function index(Request $request): Response|mixed
    {
        $query = Journal::where('company_id', company_id())
            ->with('lines.account')
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('reference')) {
            $query->where('reference', 'like', '%' . $request->get('reference') . '%');
        }

        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->get('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->get('date_to'));
        }

        $journals = $query->paginate(25);

        return $this->response('double-entry::journals.index', compact('journals'));
    }

    public function create(): Response|mixed
    {
        $accounts = Account::where('company_id', company_id())
            ->enabled()
            ->orderBy('code')
            ->get()
            ->mapWithKeys(fn ($a) => [$a->id => $a->display_name]);

        return view('double-entry::journals.create', compact('accounts'));
    }

    public function store(JournalStore $request): Response|mixed
    {
        DB::transaction(function () use ($request, &$journal) {
            $journal = Journal::create([
                'company_id' => company_id(),
                'date' => $request->get('date'),
                'reference' => $request->get('reference'),
                'description' => $request->get('description'),
                'basis' => $request->get('basis', 'accrual'),
                'status' => $request->get('status', 'posted'),
                'recurring_frequency' => $request->get('recurring_frequency'),
                'next_recurring_date' => $request->get('next_recurring_date'),
                'created_by' => auth()->id(),
            ]);

            foreach ($request->get('lines') as $line) {
                $journal->lines()->create([
                    'account_id' => $line['account_id'],
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'description' => $line['description'] ?? null,
                ]);
            }
        });

        flash(trans('messages.success.added', ['type' => trans('double-entry::general.journal_entries')]))->success();

        return redirect()->route('double-entry.journals.index');
    }

    public function show(int $id): Response|mixed
    {
        $journal = Journal::where('company_id', company_id())
            ->with('lines.account', 'creator')
            ->findOrFail($id);

        return $this->response('double-entry::journals.show', compact('journal'));
    }

    public function edit(int $id): Response|mixed
    {
        $journal = Journal::where('company_id', company_id())
            ->with('lines')
            ->findOrFail($id);

        if ($journal->status !== 'draft') {
            flash(trans('double-entry::general.only_draft_editable'))->warning();

            return redirect()->route('double-entry.journals.show', $id);
        }

        $accounts = Account::where('company_id', company_id())
            ->enabled()
            ->orderBy('code')
            ->get()
            ->mapWithKeys(fn ($a) => [$a->id => $a->display_name]);

        return view('double-entry::journals.edit', compact('journal', 'accounts'));
    }

    public function update(int $id, JournalUpdate $request): Response|mixed
    {
        $journal = Journal::where('company_id', company_id())->findOrFail($id);

        if ($journal->status !== 'draft') {
            flash(trans('double-entry::general.only_draft_editable'))->warning();

            return redirect()->route('double-entry.journals.show', $id);
        }

        DB::transaction(function () use ($journal, $request) {
            $journal->update([
                'date' => $request->get('date'),
                'reference' => $request->get('reference'),
                'description' => $request->get('description'),
                'basis' => $request->get('basis', 'accrual'),
                'status' => $request->get('status', 'posted'),
                'recurring_frequency' => $request->get('recurring_frequency'),
                'next_recurring_date' => $request->get('next_recurring_date'),
            ]);

            $journal->lines()->delete();

            foreach ($request->get('lines') as $line) {
                $journal->lines()->create([
                    'account_id' => $line['account_id'],
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'description' => $line['description'] ?? null,
                ]);
            }
        });

        flash(trans('messages.success.updated', ['type' => trans('double-entry::general.journal_entries')]))->success();

        return redirect()->route('double-entry.journals.show', $journal->id);
    }

    public function destroy(int $id): Response|mixed
    {
        $journal = Journal::where('company_id', company_id())->findOrFail($id);

        if ($journal->status === 'posted') {
            // Create reversing entry instead of deleting
            DB::transaction(function () use ($journal) {
                $reversal = Journal::create([
                    'company_id' => $journal->company_id,
                    'date' => now()->toDateString(),
                    'reference' => 'REV-' . $journal->reference,
                    'description' => 'Reversal of: ' . ($journal->description ?? $journal->reference),
                    'basis' => $journal->basis,
                    'status' => 'posted',
                    'created_by' => auth()->id(),
                ]);

                foreach ($journal->lines as $line) {
                    $reversal->lines()->create([
                        'account_id' => $line->account_id,
                        'debit' => $line->credit,
                        'credit' => $line->debit,
                        'description' => 'Reversal: ' . ($line->description ?? ''),
                    ]);
                }

                $journal->delete();
            });
        } else {
            $journal->delete();
        }

        flash(trans('messages.success.deleted', ['type' => trans('double-entry::general.journal_entries')]))->success();

        return redirect()->route('double-entry.journals.index');
    }

    public function duplicate(int $id): Response|mixed
    {
        $original = Journal::where('company_id', company_id())
            ->with('lines')
            ->findOrFail($id);

        DB::transaction(function () use ($original, &$journal) {
            $journal = Journal::create([
                'company_id' => $original->company_id,
                'date' => now()->toDateString(),
                'reference' => 'COPY-' . $original->reference,
                'description' => $original->description,
                'basis' => $original->basis,
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            foreach ($original->lines as $line) {
                $journal->lines()->create([
                    'account_id' => $line->account_id,
                    'debit' => $line->debit,
                    'credit' => $line->credit,
                    'description' => $line->description,
                ]);
            }
        });

        flash(trans('double-entry::general.journal_duplicated'))->success();

        return redirect()->route('double-entry.journals.edit', $journal->id);
    }
}
