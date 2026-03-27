<?php

namespace Modules\Appointments\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Support\Str;
use Modules\Appointments\Http\Requests\AppointmentFormStore;
use Modules\Appointments\Http\Requests\AppointmentFormUpdate;
use Modules\Appointments\Models\AppointmentForm;

class AppointmentForms extends Controller
{
    public function index()
    {
        $forms = AppointmentForm::where('company_id', company_id())->orderBy('name')->paginate(25);

        return view('appointments::forms.index', compact('forms'));
    }

    public function create()
    {
        return view('appointments::forms.create');
    }

    public function store(AppointmentFormStore $request)
    {
        $form = AppointmentForm::create([
            'company_id' => company_id(),
            'name' => $request->get('name'),
            'fields_json' => $this->normalizeFields($request->get('fields_json')),
            'public_link' => Str::random(32),
            'enabled' => $request->boolean('enabled', true),
        ]);

        flash(trans('messages.success.added', ['type' => trans('appointments::general.form')]))->success();

        return redirect()->route('appointments.forms.show', $form->id);
    }

    public function show(int $id)
    {
        $form = AppointmentForm::where('company_id', company_id())->findOrFail($id);

        return view('appointments::forms.show', compact('form'));
    }

    public function edit(int $id)
    {
        $form = AppointmentForm::where('company_id', company_id())->findOrFail($id);

        return view('appointments::forms.edit', compact('form'));
    }

    public function update(int $id, AppointmentFormUpdate $request)
    {
        $form = AppointmentForm::where('company_id', company_id())->findOrFail($id);

        $form->update([
            'name' => $request->get('name'),
            'fields_json' => $this->normalizeFields($request->get('fields_json')),
            'enabled' => $request->boolean('enabled'),
        ]);

        flash(trans('messages.success.updated', ['type' => trans('appointments::general.form')]))->success();

        return redirect()->route('appointments.forms.show', $form->id);
    }

    public function destroy(int $id)
    {
        $form = AppointmentForm::where('company_id', company_id())->findOrFail($id);
        $form->delete();

        flash(trans('messages.success.deleted', ['type' => trans('appointments::general.form')]))->success();

        return redirect()->route('appointments.forms.index');
    }

    protected function normalizeFields(?string $fields): array
    {
        $rows = preg_split('/\r\n|\r|\n/', trim((string) $fields)) ?: [];
        $normalized = [];

        foreach ($rows as $row) {
            $parts = array_values(array_filter(array_map('trim', explode('|', $row))));

            if (! isset($parts[0])) {
                continue;
            }

            $type = 'text';
            $required = false;

            foreach (array_slice($parts, 1) as $part) {
                if (in_array($part, ['text', 'textarea', 'email', 'phone'], true)) {
                    $type = $part;
                }

                if ($part === 'required') {
                    $required = true;
                }
            }

            $normalized[] = [
                'name' => Str::snake($parts[0]),
                'label' => $parts[0],
                'type' => $type,
                'required' => $required,
            ];
        }

        return $normalized;
    }
}
