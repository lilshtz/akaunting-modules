@extends('layouts.admin')

@section('title', trans('double-entry::general.account_defaults'))

@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between gap-3">
            <h1 class="text-xl font-semibold text-gray-900">{{ trans('double-entry::general.account_defaults') }}</h1>
        </div>

        <x-form.container>
            <x-form id="account-defaults" method="POST" route="double-entry.account-defaults.store">
                <x-form.section>
                    <x-slot name="head">
                        <x-form.section.head title="{{ trans('double-entry::general.account_defaults') }}" />
                    </x-slot>

                    <x-slot name="body">
                        @foreach ($defaultTypes as $key => $config)
                            <x-form.group.select
                                name="{{ $key }}"
                                label="{{ trans('double-entry::general.' . $key) }}"
                                :options="['' => trans('general.none')] + ($accountsByType[$config['type']] ?? [])"
                                :selected="old($key, $defaults[$key] ?? '')"
                                not-required
                            />
                        @endforeach
                    </x-slot>
                </x-form.section>

                <x-form.section>
                    <x-slot name="foot">
                        <x-form.buttons cancel-route="double-entry.accounts.index" />
                    </x-slot>
                </x-form.section>
            </x-form>
        </x-form.container>
    </div>
@endsection
