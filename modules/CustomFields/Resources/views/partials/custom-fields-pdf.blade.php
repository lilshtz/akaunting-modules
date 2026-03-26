{{-- Include in PDF templates: @include('custom-fields::partials.custom-fields-pdf', ['entity_type' => 'invoice', 'entity_id' => $invoice->id]) --}}

@php
    $definitions = \Modules\CustomFields\Models\FieldDefinition::where('company_id', company_id())
        ->entityType($entity_type)
        ->enabled()
        ->forPdf()
        ->orderBy('position')
        ->get();

    $existingValues = \Modules\CustomFields\Models\FieldValue::getValuesForEntity($entity_type, $entity_id);
@endphp

@if ($definitions->isNotEmpty())
    <table style="width: 100%; margin-top: 10px; border-collapse: collapse;">
        @foreach ($definitions as $definition)
            @php
                $value = isset($existingValues[$definition->id]) ? $existingValues[$definition->id]->value : null;
            @endphp
            @if ($value !== null && $value !== '')
                <tr>
                    <td style="padding: 4px 8px; font-weight: bold; width: 40%; font-size: 12px; border-bottom: 1px solid #eee;">
                        {{ $definition->name }}
                    </td>
                    <td style="padding: 4px 8px; font-size: 12px; border-bottom: 1px solid #eee;">
                        @if ($definition->field_type === 'toggle')
                            {{ $value ? trans('general.yes') : trans('general.no') }}
                        @else
                            {{ $value }}
                        @endif
                    </td>
                </tr>
            @endif
        @endforeach
    </table>
@endif
