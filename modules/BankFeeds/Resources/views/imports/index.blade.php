@extends('layouts.admin')

@section('title', trans('bank-feeds::general.import_history'))

@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">{{ trans('bank-feeds::general.import_history') }}</h1>
            </div>
            <a href="{{ route('bank-feeds.imports.create') }}" class="inline-flex rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
                {{ trans('bank-feeds::general.import_transactions') }}
            </a>
        </div>

        <div class="rounded-xl bg-white shadow-sm">
            <x-table>
                <x-table.thead>
                    <x-table.tr>
                        <x-table.th>{{ trans('general.date') }}</x-table.th>
                        <x-table.th>{{ trans('bank-feeds::general.original_filename') }}</x-table.th>
                        <x-table.th>{{ trans('bank-feeds::general.format') }}</x-table.th>
                        <x-table.th>{{ trans('bank-feeds::general.rows') }}</x-table.th>
                        <x-table.th>{{ trans('bank-feeds::general.status') }}</x-table.th>
                        <x-table.th>{{ trans('general.actions') }}</x-table.th>
                    </x-table.tr>
                </x-table.thead>
                <x-table.tbody>
                    @forelse ($imports as $import)
                        <x-table.tr>
                            <x-table.td>{{ $import->created_at?->format('Y-m-d H:i') }}</x-table.td>
                            <x-table.td>{{ $import->original_filename }}</x-table.td>
                            <x-table.td>{{ strtoupper($import->format) }}</x-table.td>
                            <x-table.td>{{ $import->row_count }}</x-table.td>
                            <x-table.td>{{ trans('bank-feeds::general.statuses.' . $import->status) }}</x-table.td>
                            <x-table.td>
                                <div class="flex items-center gap-3">
                                    @if ($import->status === 'pending')
                                        <a href="{{ route('bank-feeds.imports.map', $import->id) }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">
                                            {{ trans('bank-feeds::general.map_columns') }}
                                        </a>
                                    @endif
                                    <form method="POST" action="{{ route('bank-feeds.imports.destroy', $import->id) }}" onsubmit="return confirm('{{ trans('messages.warning.confirm.delete') }}');">
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
                                {{ trans('bank-feeds::general.imports_empty') }}
                            </x-table.td>
                        </x-table.tr>
                    @endforelse
                </x-table.tbody>
            </x-table>
        </div>

        {{ $imports->links() }}
    </div>
@endsection
