@extends('layouts.admin')

@section('title', trans('double-entry::general.accounts'))

@section('content')
    @php
        $typeLabels = [
            'asset' => trans('double-entry::general.assets'),
            'liability' => trans('double-entry::general.liabilities'),
            'equity' => trans('double-entry::general.equity'),
            'income' => trans('double-entry::general.income'),
            'expense' => trans('double-entry::general.expenses'),
        ];
    @endphp

    <div class="space-y-6">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">{{ trans('double-entry::general.accounts') }}</h1>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('double-entry.accounts.import') }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    {{ trans('general.import') }}
                </a>

                <a href="{{ route('double-entry.accounts.create') }}" class="inline-flex items-center rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
                    {{ trans('general.title.new', ['type' => trans('double-entry::general.account')]) }}
                </a>
            </div>
        </div>

        <div class="rounded-xl bg-white shadow-sm">
            <x-table>
                <x-table.thead>
                    <x-table.tr>
                        <x-table.th>{{ trans('general.code') }}</x-table.th>
                        <x-table.th>{{ trans('general.name') }}</x-table.th>
                        <x-table.th>{{ trans('general.type') }}</x-table.th>
                        <x-table.th>{{ trans('general.balance') }}</x-table.th>
                        <x-table.th>{{ trans('double-entry::general.status') }}</x-table.th>
                        <x-table.th>{{ trans('general.actions') }}</x-table.th>
                    </x-table.tr>
                </x-table.thead>

                <x-table.tbody>
                    @foreach ($groupedAccounts as $type => $rows)
                        <x-table.tr>
                            <x-table.td colspan="6" class="bg-gray-50 text-sm font-semibold uppercase tracking-wide text-gray-600">
                                {{ $typeLabels[$type] }}
                            </x-table.td>
                        </x-table.tr>

                        @forelse ($rows as $row)
                            @php($account = $row['account'])
                            <x-table.tr>
                                <x-table.td>{{ $account->code }}</x-table.td>
                                <x-table.td>
                                    <div style="padding-left: {{ $row['depth'] * 1.5 }}rem;">
                                        @if ($row['depth'] > 0)
                                            <span class="text-gray-400">{{ str_repeat('— ', $row['depth']) }}</span>
                                        @endif
                                        {{ $account->name }}
                                    </div>
                                </x-table.td>
                                <x-table.td>{{ trans('double-entry::general.' . $account->type) }}</x-table.td>
                                <x-table.td>{{ number_format((float) $account->balance, 4) }}</x-table.td>
                                <x-table.td>
                                    <form action="{{ route('double-entry.accounts.toggle', $account->id) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium {{ $account->enabled ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                            {{ $account->enabled ? trans('general.enabled') : trans('general.disabled') }}
                                        </button>
                                    </form>
                                </x-table.td>
                                <x-table.td>
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('double-entry.accounts.edit', $account->id) }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">
                                            {{ trans('general.edit') }}
                                        </a>

                                        <form action="{{ route('double-entry.accounts.destroy', $account->id) }}" method="POST" onsubmit="return confirm('{{ trans('messages.warning.confirm.delete') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-800">
                                                {{ trans('general.delete') }}
                                            </button>
                                        </form>
                                    </div>
                                </x-table.td>
                            </x-table.tr>
                        @empty
                            <x-table.tr>
                                <x-table.td colspan="6" class="py-6 text-center text-sm text-gray-500">
                                    {{ trans('double-entry::general.no_accounts') }}
                                </x-table.td>
                            </x-table.tr>
                        @endforelse
                    @endforeach
                </x-table.tbody>
            </x-table>
        </div>
    </div>
@endsection
