<div class="grid gap-6 md:grid-cols-2">
    <x-form.group.text name="name" label="{{ trans('bank-feeds::general.rule_name') }}" :value="old('name', $rule?->name)" />
    <x-form.group.text name="priority" label="{{ trans('bank-feeds::general.priority') }}" :value="old('priority', $rule?->priority ?? 0)" type="number" />
    <x-form.group.select name="field" label="{{ trans('bank-feeds::general.field') }}" :options="$fieldOptions" :value="old('field', $rule?->field ?? 'description')" />
    <x-form.group.select name="operator" label="{{ trans('bank-feeds::general.operator') }}" :options="$operatorOptions" :value="old('operator', $rule?->operator ?? 'contains')" />
    <x-form.group.text name="value" label="{{ trans('bank-feeds::general.value') }}" :value="old('value', $rule?->value)" />
    <x-form.group.text name="value_end" label="{{ trans('bank-feeds::general.value_end') }}" :value="old('value_end', $rule?->value_end)" not-required />
    <x-form.group.select name="category_id" label="{{ trans('bank-feeds::general.category_account') }}" :options="$categoryOptions" :value="old('category_id', $rule?->category_id)" not-required />
    <x-form.group.toggle name="enabled" label="{{ trans('general.enabled') }}" :checked="(bool) old('enabled', $rule?->enabled ?? true)" />
</div>
