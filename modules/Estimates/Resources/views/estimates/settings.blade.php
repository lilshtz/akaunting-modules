<x-layouts.admin>
    <x-slot name="title">
        {{ trans('estimates::general.settings') }}
    </x-slot>

    <x-slot name="content">
        <x-form.container>
            <x-form id="estimate-settings" method="POST" route="estimates.settings">
                <x-form.section>
                    <x-slot name="head">
                        <x-form.section.head title="{{ trans('estimates::general.settings') }}" />
                    </x-slot>

                    <x-slot name="body">
                        <x-form.group.text name="prefix" label="{{ trans('estimates::general.prefix') }}" :value="$settings->prefix" />

                        <x-form.group.text name="next_number" label="{{ trans('estimates::general.next_number') }}" :value="$settings->next_number" />

                        <x-form.group.textarea name="default_terms" label="{{ trans('estimates::general.default_terms') }}" :value="$settings->default_terms" not-required />

                        <x-form.group.select name="template" label="{{ trans('estimates::general.template') }}" :options="['default' => 'Default']" :selected="$settings->template" />

                        <x-form.group.select name="approval_required" label="{{ trans('estimates::general.approval_required') }}" :options="['1' => trans('general.yes'), '0' => trans('general.no')]" :selected="$settings->approval_required ? '1' : '0'" />
                    </x-slot>
                </x-form.section>

                <x-form.section>
                    <x-slot name="foot">
                        <x-form.buttons cancel-route="estimates.estimates.index" />
                    </x-slot>
                </x-form.section>
            </x-form>
        </x-form.container>
    </x-slot>
</x-layouts.admin>
