<?php

namespace Modules\CustomFields\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\CustomFields\Http\Requests\FieldStore;
use Modules\CustomFields\Http\Requests\FieldUpdate;
use Modules\CustomFields\Models\FieldDefinition;

class Fields extends Controller
{
    public function index(): Response
    {
        $definitions = FieldDefinition::where('company_id', company_id())
            ->orderBy('entity_type')
            ->orderBy('position')
            ->get();

        $groupedFields = $definitions->groupBy('entity_type');

        $entityTypes = FieldDefinition::entityTypes();

        return $this->response('custom-fields::fields.index', compact('groupedFields', 'entityTypes'));
    }

    public function create(): Response
    {
        $entityTypes = FieldDefinition::entityTypes();
        $fieldTypes = FieldDefinition::fieldTypes();

        return view('custom-fields::fields.create', compact('entityTypes', 'fieldTypes'));
    }

    public function store(FieldStore $request): Response
    {
        $optionsJson = $this->parseOptionsText($request->get('options_text'));

        $definition = FieldDefinition::create([
            'company_id' => company_id(),
            'entity_type' => $request->get('entity_type'),
            'name' => $request->get('name'),
            'field_type' => $request->get('field_type'),
            'required' => $request->get('required', false),
            'default_value' => $request->get('default_value'),
            'options_json' => $optionsJson ?: $request->get('options_json'),
            'position' => $request->get('position', 0),
            'show_on_pdf' => $request->get('show_on_pdf', false),
            'width' => $request->get('width', 'full'),
            'enabled' => $request->get('enabled', true),
        ]);

        $message = trans('messages.success.added', ['type' => $definition->name]);

        flash($message)->success();

        return redirect()->route('custom-fields.fields.index');
    }

    public function show(int $id): Response
    {
        return redirect()->route('custom-fields.fields.edit', $id);
    }

    public function edit(int $id): Response
    {
        $field = FieldDefinition::where('company_id', company_id())->findOrFail($id);

        $entityTypes = FieldDefinition::entityTypes();
        $fieldTypes = FieldDefinition::fieldTypes();

        return view('custom-fields::fields.edit', compact('field', 'entityTypes', 'fieldTypes'));
    }

    public function update(int $id, FieldUpdate $request): Response
    {
        $field = FieldDefinition::where('company_id', company_id())->findOrFail($id);

        $optionsJson = $this->parseOptionsText($request->get('options_text'));

        $field->update([
            'entity_type' => $request->get('entity_type'),
            'name' => $request->get('name'),
            'field_type' => $request->get('field_type'),
            'required' => $request->get('required', false),
            'default_value' => $request->get('default_value'),
            'options_json' => $optionsJson ?: $request->get('options_json'),
            'position' => $request->get('position', 0),
            'show_on_pdf' => $request->get('show_on_pdf', false),
            'width' => $request->get('width', 'full'),
            'enabled' => $request->get('enabled', true),
        ]);

        $message = trans('messages.success.updated', ['type' => $field->name]);

        flash($message)->success();

        return redirect()->route('custom-fields.fields.index');
    }

    protected function parseOptionsText(?string $optionsText): ?array
    {
        if (empty($optionsText)) {
            return null;
        }

        $lines = array_filter(array_map('trim', explode("\n", $optionsText)));

        return !empty($lines) ? array_values($lines) : null;
    }

    public function destroy(int $id): Response
    {
        $field = FieldDefinition::where('company_id', company_id())->findOrFail($id);

        $field->values()->delete();
        $field->delete();

        $message = trans('messages.success.deleted', ['type' => $field->name]);

        flash($message)->success();

        return redirect()->route('custom-fields.fields.index');
    }
}
