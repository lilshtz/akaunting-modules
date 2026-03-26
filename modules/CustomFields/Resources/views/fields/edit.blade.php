<x-layouts.admin>
    <x-slot name="title">
        {{ trans('general.title.edit', ['type' => trans_choice('custom-fields::general.fields', 1)]) }}
    </x-slot>

    <x-slot name="content">
        <x-form.container>
            <x-form id="field" method="PATCH" :route="['custom-fields.fields.update', $field->id]" :model="$field">
                <x-form.section>
                    <x-slot name="head">
                        <x-form.section.head title="{{ trans('general.general') }}" />
                    </x-slot>

                    <x-slot name="body">
                        <x-form.group.select name="entity_type" label="{{ trans('custom-fields::general.entity_type') }}" :options="$entityTypes" :value="$field->entity_type" />

                        <x-form.group.text name="name" label="{{ trans('custom-fields::general.field_name') }}" :value="$field->name" />

                        <x-form.group.select name="field_type" label="{{ trans('custom-fields::general.field_type') }}" :options="$fieldTypes" :value="$field->field_type" />

                        <x-form.group.toggle name="required" label="{{ trans('custom-fields::general.required_field') }}" :value="$field->required" />

                        <x-form.group.text name="default_value" label="{{ trans('custom-fields::general.default_value') }}" :value="$field->default_value" not-required />

                        <x-form.group.textarea name="options_text" label="{{ trans('custom-fields::general.options') }}" not-required
                            :value="$field->options_json ? implode("\n", $field->options_json) : ''"
                            placeholder="{{ trans('custom-fields::general.options_hint') }}" />

                        <x-form.group.text name="position" label="{{ trans('custom-fields::general.position') }}" :value="$field->position" />

                        <x-form.group.toggle name="show_on_pdf" label="{{ trans('custom-fields::general.show_on_pdf') }}" :value="$field->show_on_pdf" />

                        <x-form.group.select name="width" label="{{ trans('custom-fields::general.width') }}" :options="[
                            'full' => trans('custom-fields::general.widths.full'),
                            'half' => trans('custom-fields::general.widths.half'),
                        ]" :value="$field->width" />

                        <x-form.group.toggle name="enabled" label="{{ trans('general.enabled') }}" :value="$field->enabled" />
                    </x-slot>
                </x-form.section>

                <x-form.section>
                    <x-slot name="foot">
                        <x-form.buttons cancel-route="custom-fields.fields.index" />
                    </x-slot>
                </x-form.section>
            </x-form>
        </x-form.container>
    </x-slot>
</x-layouts.admin>
