@php
    $selectedField = old('field', $rule?->field ?? 'description');
    $selectedOperator = old('operator', $rule?->operator ?? 'contains');
@endphp

<x-form.section>
    <x-slot name="head">
        <x-form.section.head
            title="{{ trans('bank-feeds::general.rule') }}"
            description="{{ trans('bank-feeds::general.help.rule_value') }}"
        />
    </x-slot>

    <x-slot name="body">
        <x-form.group.text name="name" label="{{ trans('bank-feeds::general.rule_name') }}" :value="old('name', $rule?->name)" />
        <x-form.group.text name="priority" label="{{ trans('bank-feeds::general.priority') }}" :value="old('priority', $rule?->priority ?? 0)" type="number" />
        <x-form.group.select name="field" label="{{ trans('bank-feeds::general.field') }}" :options="$fieldOptions" :selected="$selectedField" />
        <x-form.group.select name="operator" label="{{ trans('bank-feeds::general.operator') }}" :options="$operatorOptions[$selectedField] ?? []" :selected="$selectedOperator" />
        <x-form.group.text name="value" label="{{ trans('bank-feeds::general.value') }}" :value="old('value', $rule?->value)" />
        <div id="value-end-wrapper">
            <x-form.group.text name="value_end" label="{{ trans('bank-feeds::general.value_end') }}" :value="old('value_end', $rule?->value_end)" not-required />
        </div>
        <x-form.group.select name="category_id" label="{{ trans('bank-feeds::general.category_account') }}" :options="$categoryOptions" :selected="old('category_id', $rule?->category_id)" />
        <x-form.group.toggle name="enabled" label="{{ trans('general.enabled') }}" :value="(bool) old('enabled', $rule?->enabled ?? true)" />
    </x-slot>
</x-form.section>

<x-form.section>
    <x-slot name="foot">
        <x-form.buttons cancel-route="bank-feeds.rules.index" />
    </x-slot>
</x-form.section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const fieldSelect = document.querySelector('[name="field"]');
        const operatorSelect = document.querySelector('[name="operator"]');
        const valueEndWrapper = document.getElementById('value-end-wrapper');
        const operatorOptions = @json($operatorOptions);
        const selectedOperator = @json($selectedOperator);

        function renderOperators() {
            const field = fieldSelect.value;
            const options = operatorOptions[field] || {};
            const previousValue = operatorSelect.value || selectedOperator;

            operatorSelect.innerHTML = '';

            Object.entries(options).forEach(([value, label]) => {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = label;

                if (value === previousValue || (! previousValue && operatorSelect.options.length === 0)) {
                    option.selected = true;
                }

                operatorSelect.appendChild(option);
            });

            toggleValueEnd();
        }

        function toggleValueEnd() {
            valueEndWrapper.style.display = operatorSelect.value === 'between' ? 'block' : 'none';
        }

        fieldSelect.addEventListener('change', renderOperators);
        operatorSelect.addEventListener('change', toggleValueEnd);

        renderOperators();
    });
</script>
