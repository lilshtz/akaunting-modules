<?php

namespace Modules\Appointments\Http\Controllers;

use App\Abstracts\Http\Controller;
use App\Models\Auth\User;
use App\Models\Common\Contact;
use Illuminate\Http\Request;
use Modules\Appointments\Http\Requests\AppointmentStore;
use Modules\Appointments\Http\Requests\AppointmentUpdate;
use Modules\Appointments\Models\Appointment;
use Modules\Appointments\Services\AppointmentCalendarService;
use Modules\Appointments\Services\AppointmentReminderService;

class Appointments extends Controller
{
    public function __construct(
        protected AppointmentCalendarService $calendar,
        protected AppointmentReminderService $reminders
    ) {
    }

    public function index(Request $request)
    {
        $calendar = $this->calendar->build(
            company_id(),
            $request->get('date'),
            (string) $request->get('view', 'month')
        );

        $users = $this->users();
        $contacts = Contact::where('company_id', company_id())->orderBy('name')->pluck('name', 'id');

        return view('appointments::appointments.index', array_merge($calendar, compact('users', 'contacts')));
    }

    public function create()
    {
        return view('appointments::appointments.create', $this->formData());
    }

    public function store(AppointmentStore $request)
    {
        $appointment = Appointment::create([
            'company_id' => company_id(),
            'contact_id' => $request->integer('contact_id') ?: null,
            'user_id' => $request->integer('user_id'),
            'date' => $request->get('date'),
            'start_time' => $request->get('start_time'),
            'end_time' => $request->get('end_time'),
            'location' => $request->get('location'),
            'status' => $request->get('status', Appointment::STATUS_SCHEDULED),
            'notes' => $request->get('notes'),
            'reminder_sent' => false,
        ]);

        flash(trans('messages.success.added', ['type' => trans('appointments::general.appointment')]))->success();

        return redirect()->route('appointments.show', $appointment->id);
    }

    public function show(int $id)
    {
        $appointment = Appointment::where('company_id', company_id())
            ->with(['contact', 'user'])
            ->findOrFail($id);

        return view('appointments::appointments.show', compact('appointment'));
    }

    public function edit(int $id)
    {
        $appointment = Appointment::where('company_id', company_id())->findOrFail($id);

        return view('appointments::appointments.edit', array_merge($this->formData(), compact('appointment')));
    }

    public function update(int $id, AppointmentUpdate $request)
    {
        $appointment = Appointment::where('company_id', company_id())->findOrFail($id);

        $appointment->update([
            'contact_id' => $request->integer('contact_id') ?: null,
            'user_id' => $request->integer('user_id'),
            'date' => $request->get('date'),
            'start_time' => $request->get('start_time'),
            'end_time' => $request->get('end_time'),
            'location' => $request->get('location'),
            'status' => $request->get('status', Appointment::STATUS_SCHEDULED),
            'notes' => $request->get('notes'),
            'reminder_sent' => false,
        ]);

        flash(trans('messages.success.updated', ['type' => trans('appointments::general.appointment')]))->success();

        return redirect()->route('appointments.show', $appointment->id);
    }

    public function destroy(int $id)
    {
        $appointment = Appointment::where('company_id', company_id())->findOrFail($id);
        $appointment->delete();

        flash(trans('messages.success.deleted', ['type' => trans('appointments::general.appointment')]))->success();

        return redirect()->route('appointments.index');
    }

    public function sendReminders()
    {
        $sent = $this->reminders->sendDueReminders(company_id());

        flash(trans('appointments::general.messages.reminders_sent', ['count' => $sent]))->success();

        return redirect()->route('appointments.index');
    }

    protected function formData(): array
    {
        $contacts = Contact::where('company_id', company_id())->orderBy('name')->pluck('name', 'id');
        $users = $this->users();
        $statuses = collect(Appointment::STATUSES)
            ->mapWithKeys(fn (string $status) => [$status => trans('appointments::general.statuses.' . $status)]);

        return compact('contacts', 'users', 'statuses');
    }

    protected function users()
    {
        return User::query()
            ->whereHas('companies', fn ($query) => $query->where('companies.id', company_id()))
            ->enabled()
            ->orderBy('name')
            ->pluck('name', 'id');
    }
}
