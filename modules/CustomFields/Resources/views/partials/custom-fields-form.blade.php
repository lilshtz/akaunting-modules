{{-- Include in entity create/edit forms: @include('custom-fields::partials.custom-fields-form', ['entity_type' => 'invoice', 'entity' => $invoice ?? null]) --}}

@php
    $definitions = \Modules\CustomFields\Models\FieldDefinition::where('company_id', company_id())
        ->entityType($entity_type)
        ->enabled()
        ->orderBy('position')
        ->get();

    $existingValues = [];
    if (isset($entity) && $entity && $entity->id) {
        $existingValues = \Modules\CustomFields\Models\FieldValue::getValuesForEntity($entity_type, $entity->id);
    }
@endphp

@if ($definitions->isNotEmpty())
    <x-form.section>
        <x-slot name="head">
            <x-form.section.head title="{{ trans('custom-fields::general.custom_fields') }}" />
        </x-slot>

        <x-slot name="body">
            @foreach ($definitions as $definition)
                @php
                    $fieldName = "custom_fields[{$definition->id}]";
                    $currentValue = isset($existingValues[$definition->id]) ? $existingValues[$definition->id]->value : $definition->default_value;
                    $isRequired = $definition->required;
                @endphp

                @switch($definition->field_type)
                    @case('text')
                        <x-form.group.text
                            name="{{ $fieldName }}"
                            label="{{ $definition->name }}"
                            :value="$currentValue"
                            :not-required="!$isRequired"
                        />
                        @break

                    @case('textarea')
                        <x-form.group.textarea
                            name="{{ $fieldName }}"
                            label="{{ $definition->name }}"
                            :value="$currentValue"
                            :not-required="!$isRequired"
                        />
                        @break

                    @case('number')
                        <x-form.group.text
                            name="{{ $fieldName }}"
                            label="{{ $definition->name }}"
                            :value="$currentValue"
                            type="number"
                            :not-required="!$isRequired"
                        />
                        @break

                    @case('date')
                        <x-form.group.date
                            name="{{ $fieldName }}"
                            label="{{ $definition->name }}"
                            :value="$currentValue"
                            :not-required="!$isRequired"
                        />
                        @break

                    @case('datetime')
                        <x-form.group.date
                            name="{{ $fieldName }}"
                            label="{{ $definition->name }}"
                            :value="$currentValue"
                            :not-required="!$isRequired"
                        />
                        @break

                    @case('time')
                        <x-form.group.text
                            name="{{ $fieldName }}"
                            label="{{ $definition->name }}"
                            :value="$currentValue"
                            type="time"
                            :not-required="!$isRequired"
                        />
                        @break

                    @case('select')
                        @php
                            $options = [];
                            if ($definition->options_json) {
                                foreach ($definition->options_json as $opt) {
                                    $options[$opt] = $opt;
                                }
                            }
                        @endphp
                        <x-form.group.select
                            name="{{ $fieldName }}"
                            label="{{ $definition->name }}"
                            :options="$options"
                            :value="$currentValue"
                            :not-required="!$isRequired"
                        />
                        @break

                    @case('checkbox')
                        @php
                            $checkboxOptions = [];
                            if ($definition->options_json) {
                                foreach ($definition->options_json as $opt) {
                                    $checkboxOptions[$opt] = $opt;
                                }
                            }
                        @endphp
                        <x-form.group.select
                            name="{{ $fieldName }}[]"
                            label="{{ $definition->name }}"
                            :options="$checkboxOptions"
                            :value="$currentValue ? explode(',', $currentValue) : []"
                            multiple
                            :not-required="!$isRequired"
                        />
                        @break

                    @case('toggle')
                        <x-form.group.toggle
                            name="{{ $fieldName }}"
                            label="{{ $definition->name }}"
                            :value="(bool) $currentValue"
                        />
                        @break

                    @case('url')
                        <x-form.group.text
                            name="{{ $fieldName }}"
                            label="{{ $definition->name }}"
                            :value="$currentValue"
                            type="url"
                            :not-required="!$isRequired"
                        />
                        @break

                    @case('email')
                        <x-form.group.text
                            name="{{ $fieldName }}"
                            label="{{ $definition->name }}"
                            :value="$currentValue"
                            type="email"
                            :not-required="!$isRequired"
                        />
                        @break
                @endswitch
            @endforeach
        </x-slot>
    </x-form.section>
@endif
