<?php

namespace Modules\Crm\Http\Controllers;

use App\Abstracts\Http\Controller;
use App\Models\Auth\User;
use App\Models\Document\Document;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Crm\Http\Requests\ContactStore;
use Modules\Crm\Http\Requests\ContactUpdate;
use Modules\Crm\Http\Requests\ImportContacts;
use Modules\Crm\Models\CrmActivity;
use Modules\Crm\Models\CrmCompany;
use Modules\Crm\Models\CrmContact;
use Modules\Crm\Services\CrmContactSyncService;
use SplFileObject;

class Contacts extends Controller
{
    public function __construct(protected CrmContactSyncService $syncService)
    {
    }

    public function index(Request $request): Response|mixed
    {
        $query = CrmContact::where('company_id', company_id())
            ->with(['crmCompany', 'owner', 'akauntingContact'])
            ->withCount('activities');

        $query->stage($request->get('stage'))
            ->source($request->get('source'))
            ->crmCompany($request->get('crm_company_id'));

        if ($request->filled('search')) {
            $search = $request->get('search');

            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%')
                    ->orWhere('notes', 'like', '%' . $search . '%')
                    ->orWhereHas('crmCompany', function ($companyQuery) use ($search) {
                        $companyQuery->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        $contacts = $query->latest()->paginate(25);

        return $this->response('crm::contacts.index', array_merge(
            compact('contacts'),
            $this->formOptions()
        ));
    }

    public function create(): Response|mixed
    {
        return view('crm::contacts.create', $this->formOptions());
    }

    public function store(ContactStore $request): Response|mixed
    {
        $crmCompany = $this->selectedCrmCompany($request->get('crm_company_id'));

        $contact = CrmContact::create([
            'company_id' => company_id(),
            'crm_company_id' => $crmCompany?->id,
            'name' => $request->get('name'),
            'email' => $request->get('email'),
            'phone' => $request->get('phone'),
            'source' => $request->get('source'),
            'stage' => $request->get('stage') ?: ($crmCompany?->default_stage ?: CrmContact::STAGE_LEAD),
            'owner_user_id' => $request->get('owner_user_id') ?: null,
            'notes' => $request->get('notes'),
        ]);

        $contact->load('crmCompany');
        $this->syncService->sync($contact);

        CrmActivity::create([
            'company_id' => company_id(),
            'crm_contact_id' => $contact->id,
            'type' => CrmActivity::TYPE_NOTE,
            'description' => trans('crm::general.activity_created_contact', ['name' => $contact->name]),
            'user_id' => auth()->id(),
            'created_at' => now(),
        ]);

        flash(trans('messages.success.added', ['type' => $contact->name]))->success();

        return redirect()->route('crm.contacts.show', $contact->id);
    }

    public function show(int $id): Response|mixed
    {
        $contact = $this->findContact($id, ['crmCompany', 'owner', 'akauntingContact', 'activities.user']);

        $invoices = collect();

        if ($contact->akaunting_contact_id) {
            $invoices = Document::where('company_id', company_id())
                ->where('contact_id', $contact->akaunting_contact_id)
                ->where('type', Document::INVOICE_TYPE)
                ->latest()
                ->get();
        }

        return view('crm::contacts.show', array_merge(
            compact('contact', 'invoices'),
            $this->formOptions()
        ));
    }

    public function edit(int $id): Response|mixed
    {
        $contact = $this->findContact($id);

        return view('crm::contacts.edit', array_merge(
            ['contact' => $contact],
            $this->formOptions()
        ));
    }

    public function update(int $id, ContactUpdate $request): Response|mixed
    {
        $contact = $this->findContact($id, ['crmCompany']);

        $contact->update([
            'crm_company_id' => $request->get('crm_company_id') ?: null,
            'name' => $request->get('name'),
            'email' => $request->get('email'),
            'phone' => $request->get('phone'),
            'source' => $request->get('source'),
            'stage' => $request->get('stage'),
            'owner_user_id' => $request->get('owner_user_id') ?: null,
            'notes' => $request->get('notes'),
        ]);

        $contact->load('crmCompany');
        $this->syncService->sync($contact);

        CrmActivity::create([
            'company_id' => company_id(),
            'crm_contact_id' => $contact->id,
            'type' => CrmActivity::TYPE_NOTE,
            'description' => trans('crm::general.activity_updated_contact', ['name' => $contact->name]),
            'user_id' => auth()->id(),
            'created_at' => now(),
        ]);

        flash(trans('messages.success.updated', ['type' => $contact->name]))->success();

        return redirect()->route('crm.contacts.show', $contact->id);
    }

    public function destroy(int $id): Response|mixed
    {
        $contact = $this->findContact($id);
        $name = $contact->name;

        $contact->delete();

        flash(trans('messages.success.deleted', ['type' => $name]))->success();

        return redirect()->route('crm.contacts.index');
    }

    public function import(): Response|mixed
    {
        return view('crm::contacts.import', $this->formOptions());
    }

    public function importStore(ImportContacts $request): Response|mixed
    {
        $file = new SplFileObject($request->file('file')->getRealPath());
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);

        $crmCompany = $this->selectedCrmCompany($request->get('crm_company_id'));
        $header = null;
        $created = 0;

        foreach ($file as $row) {
            if (! is_array($row) || $row === [null]) {
                continue;
            }

            if ($header === null) {
                $header = array_map(function ($value) {
                    $value = trim((string) $value);
                    $value = ltrim($value, "\xEF\xBB\xBF");

                    return strtolower($value);
                }, $row);
                continue;
            }

            $row = array_pad($row, count($header), null);
            $data = array_combine($header, $row);

            $name = trim((string) ($data['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            $contact = CrmContact::create([
                'company_id' => company_id(),
                'crm_company_id' => $crmCompany?->id,
                'name' => $name,
                'email' => $this->nullableValue($data['email'] ?? null),
                'phone' => $this->nullableValue($data['phone'] ?? null),
                'source' => $this->normalizeSource($data['source'] ?? $request->get('source')),
                'stage' => $this->normalizeStage($data['stage'] ?? $request->get('stage') ?: $crmCompany?->default_stage),
                'owner_user_id' => $request->get('owner_user_id') ?: null,
                'notes' => $this->nullableValue($data['notes'] ?? null),
            ]);

            $contact->load('crmCompany');
            $this->syncService->sync($contact);

            CrmActivity::create([
                'company_id' => company_id(),
                'crm_contact_id' => $contact->id,
                'type' => CrmActivity::TYPE_NOTE,
                'description' => trans('crm::general.activity_imported_contact', ['name' => $contact->name]),
                'user_id' => auth()->id(),
                'created_at' => now(),
            ]);

            $created++;
        }

        flash(trans('crm::general.import_success', ['count' => $created]))->success();

        return redirect()->route('crm.contacts.index');
    }

    protected function findContact(int $id, array $with = [])
    {
        return CrmContact::where('company_id', company_id())
            ->with($with)
            ->findOrFail($id);
    }

    protected function selectedCrmCompany($id): ?CrmCompany
    {
        if (empty($id)) {
            return null;
        }

        return CrmCompany::where('company_id', company_id())->find($id);
    }

    protected function formOptions(): array
    {
        $crmCompanies = CrmCompany::where('company_id', company_id())
            ->orderBy('name')
            ->pluck('name', 'id')
            ->prepend(trans('general.none'), '');

        $owners = User::orderBy('name')
            ->pluck('name', 'id')
            ->prepend(trans('general.none'), '');

        return [
            'crmCompanies' => $crmCompanies,
            'owners' => $owners,
            'stages' => $this->stageOptions(),
            'sources' => $this->sourceOptions(),
            'activityTypes' => $this->activityTypeOptions(),
        ];
    }

    protected function stageOptions(): array
    {
        return [
            CrmContact::STAGE_LEAD => trans('crm::general.stages.lead'),
            CrmContact::STAGE_SUBSCRIBER => trans('crm::general.stages.subscriber'),
            CrmContact::STAGE_OPPORTUNITY => trans('crm::general.stages.opportunity'),
            CrmContact::STAGE_CUSTOMER => trans('crm::general.stages.customer'),
        ];
    }

    protected function sourceOptions(): array
    {
        return [
            CrmContact::SOURCE_WEB => trans('crm::general.sources.web'),
            CrmContact::SOURCE_REFERRAL => trans('crm::general.sources.referral'),
            CrmContact::SOURCE_EMAIL => trans('crm::general.sources.email'),
            CrmContact::SOURCE_COLD => trans('crm::general.sources.cold'),
            CrmContact::SOURCE_PHONE => trans('crm::general.sources.phone'),
            CrmContact::SOURCE_OTHER => trans('crm::general.sources.other'),
        ];
    }

    protected function activityTypeOptions(): array
    {
        return [
            CrmActivity::TYPE_CALL => trans('crm::general.activity_types.call'),
            CrmActivity::TYPE_MEETING => trans('crm::general.activity_types.meeting'),
            CrmActivity::TYPE_EMAIL => trans('crm::general.activity_types.email'),
            CrmActivity::TYPE_NOTE => trans('crm::general.activity_types.note'),
            CrmActivity::TYPE_TASK => trans('crm::general.activity_types.task'),
        ];
    }

    protected function normalizeSource(?string $source): string
    {
        $source = strtolower(trim((string) $source));

        return array_key_exists($source, $this->sourceOptions()) ? $source : CrmContact::SOURCE_OTHER;
    }

    protected function normalizeStage(?string $stage): string
    {
        $stage = strtolower(trim((string) $stage));

        return array_key_exists($stage, $this->stageOptions()) ? $stage : CrmContact::STAGE_LEAD;
    }

    protected function nullableValue($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
