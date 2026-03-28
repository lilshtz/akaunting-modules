@extends('layouts.admin')

@section('title', trans('bank-feeds::general.new_reconciliation'))

@section('content')
    <x-form.container>
        <x-form id="bank-feed-reconciliation" method="POST" route="bank-feeds.reconciliation.store">
            <x-form.section>
                <x-slot name="head">
                    <x-form.section.head
                        title="{{ trans('bank-feeds::general.new_reconciliation') }}"
                        description="{{ trans('bank-feeds::general.help.reconciliation') }}"
                    />
                </x-slot>

                <x-form.group.select
                    name="bank_account_id"
                    label="{{ trans('bank-feeds::general.bank_account') }}"
                    :options="$bankAccountOptions"
                    :selected="old('bank_account_id')"
                />
                <x-form.group.date name="period_start" label="{{ trans('bank-feeds::general.period_start') }}" :value="old('period_start')" />
                <x-form.group.date name="period_end" label="{{ trans('bank-feeds::general.period_end') }}" :value="old('period_end')" />
                <x-form.group.text name="opening_balance" label="{{ trans('bank-feeds::general.opening_balance') }}" :value="old('opening_balance')" type="number" step="0.0001" />
                <x-form.group.text name="closing_balance" label="{{ trans('bank-feeds::general.closing_balance') }}" :value="old('closing_balance')" type="number" step="0.0001" />
            </x-form.section>

            <x-form.section>
                <x-slot name="foot">
                    <x-form.buttons cancel-route="bank-feeds.reconciliation.index" />
                </x-slot>
            </x-form.section>
        </x-form>
    </x-form.container>
@endsection
