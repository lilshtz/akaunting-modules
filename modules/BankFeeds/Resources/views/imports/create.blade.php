@extends('layouts.admin')

@section('title', trans('bank-feeds::general.import_transactions'))

@section('content')
    <x-form.container>
        <x-form id="bank-feed-import" method="POST" route="bank-feeds.imports.upload" :files="true">
            <x-form.section>
                <x-slot name="head">
                    <x-form.section.head
                        title="{{ trans('bank-feeds::general.import_transactions') }}"
                        description="{{ trans('bank-feeds::general.help.csv_upload') }}"
                    />
                </x-slot>

                <x-slot name="body">
                    <x-form.group.file name="file" label="{{ trans('bank-feeds::general.fields.file') }}" accept=".csv,.ofx,.qfx" />
                    <x-form.group.select
                        name="bank_account_id"
                        label="{{ trans('bank-feeds::general.bank_account') }}"
                        :options="$bankAccounts"
                        not-required
                    />

                    @if (empty($bankAccounts))
                        <p class="text-sm text-amber-600">{{ trans('bank-feeds::general.no_bank_accounts') }}</p>
                    @endif
                </x-slot>
            </x-form.section>

            <x-form.section>
                <x-slot name="foot">
                    <x-form.buttons cancel-route="bank-feeds.imports.index" />
                </x-slot>
            </x-form.section>
        </x-form>
    </x-form.container>
@endsection
