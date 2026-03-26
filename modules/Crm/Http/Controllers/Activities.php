<?php

namespace Modules\Crm\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Crm\Http\Requests\ActivityStore;
use Modules\Crm\Models\CrmActivity;
use Modules\Crm\Models\CrmContact;

class Activities extends Controller
{
    public function index(Request $request): Response|mixed
    {
        $query = CrmActivity::where('company_id', company_id())
            ->with(['contact', 'user']);

        if ($request->filled('type')) {
            $query->where('type', $request->get('type'));
        }

        $activities = $query->latest('scheduled_at')
            ->latest('created_at')
            ->paginate(30);

        return $this->response('crm::activities.index', compact('activities'));
    }

    public function store(int $contactId, ActivityStore $request): Response|mixed
    {
        $contact = CrmContact::where('company_id', company_id())->findOrFail($contactId);

        CrmActivity::create([
            'company_id' => company_id(),
            'crm_contact_id' => $contact->id,
            'type' => $request->get('type'),
            'description' => $request->get('description'),
            'scheduled_at' => $request->get('scheduled_at'),
            'completed_at' => $request->get('completed_at'),
            'user_id' => auth()->id(),
            'created_at' => now(),
        ]);

        flash(trans('messages.success.added', ['type' => trans('crm::general.activity')]))->success();

        return redirect()->route('crm.contacts.show', $contact->id);
    }

    public function destroy(int $id): Response|mixed
    {
        $activity = CrmActivity::where('company_id', company_id())->findOrFail($id);
        $contactId = $activity->crm_contact_id;

        $activity->delete();

        flash(trans('messages.success.deleted', ['type' => trans('crm::general.activity')]))->success();

        return redirect()->route('crm.contacts.show', $contactId);
    }
}
