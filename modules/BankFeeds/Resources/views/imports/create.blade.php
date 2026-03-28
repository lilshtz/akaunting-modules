@extends('layouts.admin')

@section('title', trans('bank-feeds::general.import_transactions'))

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">{{ trans('bank-feeds::general.import_transactions') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ trans('bank-feeds::general.help.csv_upload') }}</p>
        </div>

        <div class="rounded-xl bg-white p-6 shadow-sm">
            <form method="POST" action="{{ route('bank-feeds.imports.upload') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <div class="grid gap-6 md:grid-cols-2">
                    <x-form.group.file name="file" label="{{ trans('bank-feeds::general.fields.file') }}" accept=".csv" />
                    <x-form.group.select
                        name="bank_account_id"
                        label="{{ trans('bank-feeds::general.bank_account') }}"
                        :options="$bankAccounts"
                        not-required
                    />
                </div>

                @if (empty($bankAccounts))
                    <p class="text-sm text-amber-600">{{ trans('bank-feeds::general.no_bank_accounts') }}</p>
                @endif

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('bank-feeds.imports.index') }}" class="inline-flex rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        {{ trans('general.cancel') }}
                    </a>
                    <button type="submit" class="inline-flex rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
                        {{ trans('bank-feeds::general.upload_csv') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
