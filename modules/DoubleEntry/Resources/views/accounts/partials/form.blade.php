@php
    $parentOptions = $parentOptionsByType[$selectedType] ?? [];
@endphp

<x-form.section>
    <x-slot name="head">
        <x-form.section.head title="{{ trans('double-entry::general.account') }}" />
    </x-slot>

    <x-slot name="body">
        <x-form.group.text name="code" label="{{ trans('general.code') }}" :value="old('code', $account->code ?? '')" />

        <x-form.group.text name="name" label="{{ trans('general.name') }}" :value="old('name', $account->name ?? '')" />

        <x-form.group.select
            name="type"
            label="{{ trans('general.type') }}"
            :options="$types"
            :selected="$selectedType"
        />

        <x-form.group.select
            name="parent_id"
            label="{{ trans('double-entry::general.parent_account') }}"
            :options="$parentOptions"
            :selected="old('parent_id', $account->parent_id ?? null)"
            not-required
        />

        <x-form.group.textarea
            name="description"
            label="{{ trans('general.description') }}"
            :value="old('description', $account->description ?? '')"
            not-required
        />

        <x-form.group.text
            name="opening_balance"
            label="{{ trans('double-entry::general.opening_balance') }}"
            :value="old('opening_balance', isset($account) ? number_format((float) $account->opening_balance, 4, '.', '') : '0.0000')"
            not-required
        />

        <x-form.group.toggle
            name="enabled"
            label="{{ trans('general.enabled') }}"
            :value="(bool) old('enabled', $account->enabled ?? true)"
        />
    </x-slot>
</x-form.section>

<x-form.section>
    <x-slot name="foot">
        <x-form.buttons cancel-route="double-entry.accounts.index" />
    </x-slot>
</x-form.section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const typeSelect = document.querySelector('[name="type"]');
        const parentSelect = document.querySelector('[name="parent_id"]');
        const optionsByType = @json($parentOptionsByType);
        const selectedParent = @json((string) old('parent_id', $account->parent_id ?? ''));

        function renderParentOptions(type) {
            const options = optionsByType[type] || {};
            parentSelect.innerHTML = '';

            const blankOption = document.createElement('option');
            blankOption.value = '';
            blankOption.textContent = '{{ trans('general.none') }}';
            parentSelect.appendChild(blankOption);

            Object.entries(options).forEach(([value, label]) => {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = label;

                if (selectedParent && selectedParent === value) {
                    option.selected = true;
                }

                parentSelect.appendChild(option);
            });
        }

        renderParentOptions(typeSelect.value);
        typeSelect.addEventListener('change', function () {
            renderParentOptions(this.value);
        });
    });
</script>
