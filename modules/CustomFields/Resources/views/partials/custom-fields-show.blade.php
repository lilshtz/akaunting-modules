{{-- Include in entity show views: @include('custom-fields::partials.custom-fields-show', ['entity_type' => 'invoice', 'entity_id' => $invoice->id]) --}}

@php
    $definitions = \Modules\CustomFields\Models\FieldDefinition::where('company_id', company_id())
        ->entityType($entity_type)
        ->enabled()
        ->orderBy('position')
        ->get();

    $existingValues = \Modules\CustomFields\Models\FieldValue::getValuesForEntity($entity_type, $entity_id);
@endphp

@if ($definitions->isNotEmpty())
    <div class="mb-6">
        <h3 class="text-lg font-semibold mb-3">{{ trans('custom-fields::general.custom_fields') }}</h3>
        <div class="bg-white rounded-xl shadow-sm p-4">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach ($definitions as $definition)
                    @php
                        $value = isset($existingValues[$definition->id]) ? $existingValues[$definition->id]->value : null;
                    @endphp
                    @if ($value !== null && $value !== '')
                        <div class="{{ $definition->width === 'full' ? 'sm:col-span-2' : '' }}">
                            <dt class="text-sm font-medium text-gray-500">{{ $definition->name }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                @if ($definition->field_type === 'toggle')
                                    {{ $value ? trans('general.yes') : trans('general.no') }}
                                @elseif ($definition->field_type === 'url')
                                    <a href="{{ $value }}" target="_blank" class="text-purple-700 hover:underline">{{ $value }}</a>
                                @elseif ($definition->field_type === 'email')
                                    <a href="mailto:{{ $value }}" class="text-purple-700 hover:underline">{{ $value }}</a>
                                @else
                                    {{ $value }}
                                @endif
                            </dd>
                        </div>
                    @endif
                @endforeach
            </dl>
        </div>
    </div>
@endif
