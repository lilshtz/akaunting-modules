<?php

namespace Modules\Crm\Http\Controllers;

use App\Abstracts\Http\Controller;
use App\Models\Document\Document;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Crm\Http\Requests\ActivityStore;
use Modules\Crm\Http\Requests\DealMove;
use Modules\Crm\Http\Requests\DealStatusUpdate;
use Modules\Crm\Http\Requests\DealStore;
use Modules\Crm\Http\Requests\DealUpdate;
use Modules\Crm\Models\CrmActivity;
use Modules\Crm\Models\CrmContact;
use Modules\Crm\Models\CrmDeal;
use Modules\Crm\Models\CrmPipelineStage;

class Deals extends Controller
{
    public function index(Request $request): Response|mixed
    {
        CrmPipelineStage::ensureDefaults(company_id());

        $stages = CrmPipelineStage::forCompany(company_id())
            ->ordered()
            ->get();

        $dealQuery = CrmDeal::forCompany(company_id())
            ->visible()
            ->with(['contact.crmCompany', 'stage', 'invoice'])
            ->orderByDesc('updated_at');

        if ($request->filled('search')) {
            $search = $request->get('search');

            $dealQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('notes', 'like', '%' . $search . '%')
                    ->orWhereHas('contact', fn ($contactQuery) => $contactQuery->where('name', 'like', '%' . $search . '%'));
            });
        }

        if ($request->filled('crm_contact_id')) {
            $dealQuery->where('crm_contact_id', $request->integer('crm_contact_id'));
        }

        if ($request->filled('status')) {
            $dealQuery->where('status', $request->get('status'));
        }

        $deals = $dealQuery->get()->groupBy('stage_id');

        return view('crm::deals.index', array_merge(
            compact('stages', 'deals'),
            $this->formOptions($request->integer('crm_contact_id'))
        ));
    }

    public function create(Request $request): Response|mixed
    {
        CrmPipelineStage::ensureDefaults(company_id());

        return view('crm::deals.create', $this->formOptions($request->integer('crm_contact_id')));
    }

    public function store(DealStore $request): Response|mixed
    {
        $contact = $this->findContact($request->integer('crm_contact_id'));
        $stage = $this->findStage($request->integer('stage_id'));
        $invoice = $this->selectedInvoice($request->integer('invoice_id'));

        $deal = CrmDeal::create([
            'company_id' => company_id(),
            'crm_contact_id' => $contact->id,
            'name' => $request->get('name'),
            'value' => $request->get('value'),
            'stage_id' => $stage->id,
            'expected_close' => $request->get('expected_close'),
            'status' => $this->statusFromStage($stage),
            'invoice_id' => $invoice?->id,
            'notes' => $request->get('notes'),
            'closed_at' => $this->closedAtFromStage($stage),
        ]);

        $this->logDealActivity(
            $deal,
            trans('crm::general.activity_created_deal', ['name' => $deal->name, 'stage' => $stage->name])
        );

        flash(trans('messages.success.added', ['type' => $deal->name]))->success();

        return redirect()->route('crm.deals.show', $deal->id);
    }

    public function show(int $id): Response|mixed
    {
        $deal = $this->findDeal($id, [
            'contact.crmCompany',
            'contact.owner',
            'contact.akauntingContact',
            'stage',
            'invoice',
            'activities.user',
        ]);

        return view('crm::deals.show', array_merge(
            compact('deal'),
            $this->formOptions($deal->crm_contact_id, $deal->invoice_id)
        ));
    }

    public function edit(int $id): Response|mixed
    {
        $deal = $this->findDeal($id);

        return view('crm::deals.edit', array_merge(
            ['deal' => $deal],
            $this->formOptions($deal->crm_contact_id, $deal->invoice_id)
        ));
    }

    public function update(int $id, DealUpdate $request): Response|mixed
    {
        $deal = $this->findDeal($id, ['stage']);
        $contact = $this->findContact($request->integer('crm_contact_id'));
        $stage = $this->findStage($request->integer('stage_id'));
        $invoice = $this->selectedInvoice($request->integer('invoice_id'));
        $previousStage = $deal->stage?->name;

        $deal->update([
            'crm_contact_id' => $contact->id,
            'name' => $request->get('name'),
            'value' => $request->get('value'),
            'stage_id' => $stage->id,
            'expected_close' => $request->get('expected_close'),
            'status' => $this->statusFromStage($stage, $deal->status),
            'invoice_id' => $invoice?->id,
            'notes' => $request->get('notes'),
            'closed_at' => $this->closedAtFromStage($stage, $deal->closed_at),
        ]);

        $message = trans('crm::general.activity_updated_deal', ['name' => $deal->name]);

        if ($previousStage !== $stage->name) {
            $message .= ' ' . trans('crm::general.activity_stage_changed', [
                'from' => $previousStage ?: trans('general.na'),
                'to' => $stage->name,
            ]);
        }

        $this->logDealActivity($deal->fresh(['stage']), $message);

        flash(trans('messages.success.updated', ['type' => $deal->name]))->success();

        return redirect()->route('crm.deals.show', $deal->id);
    }

    public function destroy(int $id): Response|mixed
    {
        $deal = $this->findDeal($id);

        $deal->update([
            'status' => CrmDeal::STATUS_DELETED,
            'closed_at' => now(),
        ]);

        $this->logDealActivity($deal, trans('crm::general.activity_deleted_deal', ['name' => $deal->name]));

        flash(trans('messages.success.deleted', ['type' => $deal->name]))->success();

        return redirect()->route('crm.deals.index');
    }

    public function move(int $id, DealMove $request): Response|mixed
    {
        $deal = $this->findDeal($id, ['stage']);
        $stage = $this->findStage($request->integer('stage_id'));
        $previousStage = $deal->stage?->name;

        $deal->update([
            'stage_id' => $stage->id,
            'status' => $this->statusFromStage($stage, $deal->status),
            'closed_at' => $this->closedAtFromStage($stage, $deal->closed_at),
        ]);

        $this->logDealActivity($deal->fresh(['stage']), trans('crm::general.activity_stage_changed', [
            'from' => $previousStage ?: trans('general.na'),
            'to' => $stage->name,
        ]));

        flash(trans('messages.success.updated', ['type' => $deal->name]))->success();

        return redirect()->route('crm.deals.index');
    }

    public function updateStatus(int $id, DealStatusUpdate $request): Response|mixed
    {
        $deal = $this->findDeal($id);
        $status = $request->get('status');
        $stage = $request->filled('stage_id') ? $this->findStage($request->integer('stage_id')) : $this->terminalStage($status);

        $deal->update([
            'status' => $status,
            'stage_id' => $stage?->id ?: $deal->stage_id,
            'closed_at' => $status === CrmDeal::STATUS_OPEN ? null : now(),
        ]);

        $this->logDealActivity($deal->fresh(['stage']), trans('crm::general.activity_status_changed', [
            'name' => $deal->name,
            'status' => trans('crm::general.deal_statuses.' . $status),
        ]));

        flash(trans('messages.success.updated', ['type' => $deal->name]))->success();

        return redirect()->route('crm.deals.show', $deal->id);
    }

    public function storeActivity(int $id, ActivityStore $request): Response|mixed
    {
        $deal = $this->findDeal($id);

        CrmActivity::create([
            'company_id' => company_id(),
            'crm_contact_id' => $deal->crm_contact_id,
            'crm_deal_id' => $deal->id,
            'type' => $request->get('type'),
            'description' => $request->get('description'),
            'scheduled_at' => $request->get('scheduled_at'),
            'completed_at' => $request->get('completed_at'),
            'user_id' => auth()->id(),
            'created_at' => now(),
        ]);

        flash(trans('messages.success.added', ['type' => trans('crm::general.activity')]))->success();

        return redirect()->route('crm.deals.show', $deal->id);
    }

    protected function formOptions(?int $selectedContactId = null, ?int $selectedInvoiceId = null): array
    {
        $contacts = CrmContact::where('company_id', company_id())
            ->orderBy('name')
            ->pluck('name', 'id');

        $selectedContact = $selectedContactId ? $this->findContact($selectedContactId) : null;
        $invoiceOptions = $this->invoiceOptions($selectedContact, $selectedInvoiceId);

        return [
            'contacts' => $contacts,
            'stages' => CrmPipelineStage::forCompany(company_id())->ordered()->get(),
            'activityTypes' => [
                CrmActivity::TYPE_CALL => trans('crm::general.activity_types.call'),
                CrmActivity::TYPE_MEETING => trans('crm::general.activity_types.meeting'),
                CrmActivity::TYPE_EMAIL => trans('crm::general.activity_types.email'),
                CrmActivity::TYPE_NOTE => trans('crm::general.activity_types.note'),
                CrmActivity::TYPE_TASK => trans('crm::general.activity_types.task'),
            ],
            'dealStatuses' => [
                CrmDeal::STATUS_OPEN => trans('crm::general.deal_statuses.open'),
                CrmDeal::STATUS_WON => trans('crm::general.deal_statuses.won'),
                CrmDeal::STATUS_LOST => trans('crm::general.deal_statuses.lost'),
            ],
            'invoiceOptions' => $invoiceOptions,
        ];
    }

    protected function invoiceOptions(?CrmContact $contact = null, ?int $selectedInvoiceId = null)
    {
        $query = Document::where('company_id', company_id())
            ->where('type', Document::INVOICE_TYPE)
            ->latest()
            ->limit(100);

        if ($contact?->akaunting_contact_id) {
            $query->where('contact_id', $contact->akaunting_contact_id);
        }

        $options = $query->get()->mapWithKeys(function (Document $invoice) {
            $label = ($invoice->document_number ?: ('#' . $invoice->id)) . ' - ' . money($invoice->amount, $invoice->currency_code);

            return [$invoice->id => $label];
        });

        if ($selectedInvoiceId && ! $options->has($selectedInvoiceId)) {
            $selectedInvoice = $this->selectedInvoice($selectedInvoiceId);

            $options->put(
                $selectedInvoice->id,
                ($selectedInvoice->document_number ?: ('#' . $selectedInvoice->id)) . ' - ' . money($selectedInvoice->amount, $selectedInvoice->currency_code)
            );
        }

        return $options->prepend(trans('general.none'), '');
    }

    protected function findDeal(int $id, array $with = []): CrmDeal
    {
        return CrmDeal::forCompany(company_id())
            ->visible()
            ->with($with)
            ->findOrFail($id);
    }

    protected function findContact(int $id): CrmContact
    {
        return CrmContact::where('company_id', company_id())->findOrFail($id);
    }

    protected function findStage(int $id): CrmPipelineStage
    {
        return CrmPipelineStage::forCompany(company_id())->findOrFail($id);
    }

    protected function selectedInvoice(?int $id): ?Document
    {
        if (empty($id)) {
            return null;
        }

        return Document::where('company_id', company_id())
            ->where('type', Document::INVOICE_TYPE)
            ->findOrFail($id);
    }

    protected function terminalStage(string $status): ?CrmPipelineStage
    {
        if ($status === CrmDeal::STATUS_WON) {
            return CrmPipelineStage::forCompany(company_id())->where('is_won', true)->ordered()->first();
        }

        if ($status === CrmDeal::STATUS_LOST) {
            return CrmPipelineStage::forCompany(company_id())->where('is_lost', true)->ordered()->first();
        }

        return CrmPipelineStage::forCompany(company_id())
            ->where('is_won', false)
            ->where('is_lost', false)
            ->ordered()
            ->first();
    }

    protected function statusFromStage(CrmPipelineStage $stage, ?string $currentStatus = null): string
    {
        if ($stage->is_won) {
            return CrmDeal::STATUS_WON;
        }

        if ($stage->is_lost) {
            return CrmDeal::STATUS_LOST;
        }

        return $currentStatus === CrmDeal::STATUS_DELETED ? CrmDeal::STATUS_DELETED : CrmDeal::STATUS_OPEN;
    }

    protected function closedAtFromStage(CrmPipelineStage $stage, $currentValue = null)
    {
        if ($stage->is_won || $stage->is_lost) {
            return $currentValue ?: now();
        }

        return null;
    }

    protected function logDealActivity(CrmDeal $deal, string $description): void
    {
        CrmActivity::create([
            'company_id' => company_id(),
            'crm_contact_id' => $deal->crm_contact_id,
            'crm_deal_id' => $deal->id,
            'type' => CrmActivity::TYPE_NOTE,
            'description' => $description,
            'user_id' => auth()->id(),
            'created_at' => now(),
        ]);
    }
}
