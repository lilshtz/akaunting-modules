<?php

namespace Modules\Appointments\Http\Controllers;

use App\Abstracts\Http\Controller;
use App\Models\Auth\User;
use App\Models\Common\Contact;
use Illuminate\Http\Request;
use Modules\Appointments\Models\Appointment;
use Modules\Appointments\Models\AppointmentForm;

class PublicBooking extends Controller
{
    public function show(string $token)
    {
        $form = AppointmentForm::where('public_link', $token)
            ->where('enabled', true)
            ->firstOrFail();

        return view('appointments::public.book', compact('form'));
    }

    public function store(Request $request, string $token)
    {
        $form = AppointmentForm::where('public_link', $token)
            ->where('enabled', true)
            ->firstOrFail();

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ];

        foreach ($form->fields_json ?? [] as $field) {
            $rules['custom.' . $field['name']] = ($field['required'] ? 'required' : 'nullable') . '|string|max:500';
        }

        $payload = $request->validate($rules);

        $contactId = null;

        if (! empty($payload['email'])) {
            $contact = Contact::firstOrCreate(
                [
                    'company_id' => $form->company_id,
                    'type' => Contact::CUSTOMER_TYPE,
                    'email' => $payload['email'],
                ],
                [
                    'name' => $payload['name'],
                    'enabled' => 1,
                ]
            );

            $contactId = $contact->id;
        }

        $userId = User::query()
            ->whereHas('companies', fn ($query) => $query->where('companies.id', $form->company_id))
            ->enabled()
            ->value('id');

        abort_if(empty($userId), 422, trans('appointments::general.messages.no_assignable_user'));

        $notes = trim((string) ($payload['notes'] ?? ''));

        if (! empty($payload['custom'])) {
            $details = collect($form->fields_json ?? [])
                ->map(function (array $field) use ($payload) {
                    $value = $payload['custom'][$field['name']] ?? null;

                    if ($value === null || $value === '') {
                        return null;
                    }

                    return $field['label'] . ': ' . $value;
                })
                ->filter()
                ->implode(PHP_EOL);

            $notes = trim($notes . PHP_EOL . $details);
        }

        $appointment = Appointment::create([
            'company_id' => $form->company_id,
            'contact_id' => $contactId,
            'user_id' => $userId,
            'date' => $payload['date'],
            'start_time' => $payload['start_time'],
            'end_time' => $payload['end_time'],
            'location' => $payload['location'] ?? null,
            'status' => Appointment::STATUS_SCHEDULED,
            'notes' => $notes,
            'reminder_sent' => false,
        ]);

        return view('appointments::public.confirm', compact('form', 'appointment'));
    }
}
