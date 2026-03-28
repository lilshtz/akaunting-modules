@extends('layouts.admin')

@section('title', trans('double-entry::general.import_accounts'))

@section('content')
    <x-form.container>
        <x-form id="account-import" method="POST" route="double-entry.accounts.import.process" :files="true">
            <x-form.section>
                <x-slot name="head">
                    <x-form.section.head title="{{ trans('double-entry::general.import_accounts') }}" />
                </x-slot>

                <x-slot name="body">
                    <div class="col-span-6 rounded-lg bg-blue-50 px-4 py-3 text-sm text-blue-900">
                        {{ trans('double-entry::general.import_instructions') }}
                    </div>

                    <x-form.group.file name="file" label="CSV" />
                </x-slot>
            </x-form.section>

            <x-form.section>
                <x-slot name="foot">
                    <x-form.buttons cancel-route="double-entry.accounts.index" />
                </x-slot>
            </x-form.section>
        </x-form>
    </x-form.container>
@endsection
