@extends('layouts.admin')

@section('title', trans('bank-feeds::general.map_columns'))

@section('content')
    @php
        $columnOptions = ['' => ''];
        foreach ($headers as $index => $header) {
            $columnOptions[$index] = $header;
        }
    @endphp

    <div class="space-y-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">{{ trans('bank-feeds::general.map_columns') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ trans('bank-feeds::general.help.column_mapping') }}</p>
        </div>

        <div class="rounded-xl bg-white p-6 shadow-sm">
            <form method="POST" action="{{ route('bank-feeds.imports.process', $import->id) }}" class="space-y-6">
                @csrf

                <div class="grid gap-6 md:grid-cols-2">
                    <x-form.group.select name="mapping.date" label="{{ trans('bank-feeds::general.date') }}" :options="$columnOptions" :value="old('mapping.date', $savedMapping['date'] ?? '')" />
                    <x-form.group.select name="mapping.description" label="{{ trans('bank-feeds::general.description') }}" :options="$columnOptions" :value="old('mapping.description', $savedMapping['description'] ?? '')" />
                    <x-form.group.select name="mapping.amount" label="{{ trans('bank-feeds::general.amount') }}" :options="$columnOptions" :value="old('mapping.amount', $savedMapping['amount'] ?? '')" not-required />
                    <x-form.group.select name="mapping.type" label="{{ trans('bank-feeds::general.type') }}" :options="$columnOptions" :value="old('mapping.type', $savedMapping['type'] ?? '')" not-required />
                    <x-form.group.select name="mapping.debit" label="{{ trans('bank-feeds::general.fields.debit') }}" :options="$columnOptions" :value="old('mapping.debit', $savedMapping['debit'] ?? '')" not-required />
                    <x-form.group.select name="mapping.credit" label="{{ trans('bank-feeds::general.fields.credit') }}" :options="$columnOptions" :value="old('mapping.credit', $savedMapping['credit'] ?? '')" not-required />
                </div>

                <div class="rounded-lg border border-blue-100 bg-blue-50 p-4 text-sm text-blue-900">
                    <p>{{ trans('bank-feeds::general.amount_mapping_help') }}</p>
                    <p class="mt-1">{{ trans('bank-feeds::general.type_optional_help') }}</p>
                </div>

                <div class="rounded-lg border border-gray-200 p-4">
                    <h2 class="text-sm font-semibold text-gray-900">{{ trans('bank-feeds::general.sample_headers') }}</h2>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($headers as $header)
                            <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700">{{ $header }}</span>
                        @endforeach
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('bank-feeds.imports.create') }}" class="inline-flex rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        {{ trans('general.cancel') }}
                    </a>
                    <button type="submit" class="inline-flex rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
                        {{ trans('bank-feeds::general.process_import') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
