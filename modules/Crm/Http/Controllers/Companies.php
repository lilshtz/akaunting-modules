<?php

namespace Modules\Crm\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Crm\Http\Requests\CompanyStore;
use Modules\Crm\Http\Requests\CompanyUpdate;
use Modules\Crm\Models\CrmCompany;
use Modules\Crm\Models\CrmContact;

class Companies extends Controller
{
    public function index(Request $request): Response
    {
        $query = CrmCompany::where('company_id', company_id())
            ->withCount('contacts');

        if ($request->filled('search')) {
            $search = $request->get('search');

            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('name', 'like', '%' . $search . '%')
                    ->orWhere('address', 'like', '%' . $search . '%')
                    ->orWhere('currency', 'like', '%' . $search . '%');
            });
        }

        $companies = $query->latest()->paginate(20);
        $stages = $this->stageOptions();

        return $this->response('crm::companies.index', compact('companies', 'stages'));
    }

    public function create(): Response
    {
        $stages = $this->stageOptions();

        return view('crm::companies.create', compact('stages'));
    }

    public function store(CompanyStore $request): Response
    {
        $company = CrmCompany::create([
            'company_id' => company_id(),
            'name' => $request->get('name'),
            'address' => $request->get('address'),
            'currency' => strtoupper((string) $request->get('currency')) ?: null,
            'default_stage' => $request->get('default_stage'),
        ]);

        flash(trans('messages.success.added', ['type' => $company->name]))->success();

        return redirect()->route('crm.companies.show', $company->id);
    }

    public function show(int $id): Response
    {
        $company = CrmCompany::where('company_id', company_id())
            ->with(['contacts.owner', 'contacts.akauntingContact'])
            ->findOrFail($id);

        return view('crm::companies.show', compact('company'));
    }

    public function edit(int $id): Response
    {
        $company = CrmCompany::where('company_id', company_id())->findOrFail($id);
        $stages = $this->stageOptions();

        return view('crm::companies.edit', compact('company', 'stages'));
    }

    public function update(int $id, CompanyUpdate $request): Response
    {
        $company = CrmCompany::where('company_id', company_id())->findOrFail($id);

        $company->update([
            'name' => $request->get('name'),
            'address' => $request->get('address'),
            'currency' => strtoupper((string) $request->get('currency')) ?: null,
            'default_stage' => $request->get('default_stage'),
        ]);

        flash(trans('messages.success.updated', ['type' => $company->name]))->success();

        return redirect()->route('crm.companies.show', $company->id);
    }

    public function destroy(int $id): Response
    {
        $company = CrmCompany::where('company_id', company_id())->findOrFail($id);
        $name = $company->name;

        CrmContact::where('company_id', company_id())
            ->where('crm_company_id', $company->id)
            ->update(['crm_company_id' => null]);

        $company->delete();

        flash(trans('messages.success.deleted', ['type' => $name]))->success();

        return redirect()->route('crm.companies.index');
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
}
