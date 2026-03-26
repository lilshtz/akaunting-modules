<x-layouts.admin>
    <x-slot name="title">
        {{ trans('double-entry::general.journal_entry') }} — {{ $journal->reference ?? '#' . $journal->id }}
    </x-slot>

    <x-slot name="buttons">
        @if ($journal->status === 'draft')
            <x-link href="{{ route('double-entry.journals.edit', $journal->id) }}" kind="primary">
                {{ trans('general.edit') }}
            </x-link>
        @endif

        <form method="POST" action="{{ route('double-entry.journals.duplicate', $journal->id) }}" class="inline">
            @csrf
            <button type="submit" class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 text-sm">
                {{ trans('general.duplicate') }}
            </button>
        </form>
    </x-slot>

    <x-slot name="content">
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div>
                    <span class="block text-sm font-medium text-gray-500">{{ trans('general.date') }}</span>
                    <span class="text-sm">{{ $journal->date->format('Y-m-d') }}</span>
                </div>
                <div>
                    <span class="block text-sm font-medium text-gray-500">{{ trans('double-entry::general.reference') }}</span>
                    <span class="text-sm">{{ $journal->reference ?? '-' }}</span>
                </div>
                <div>
                    <span class="block text-sm font-medium text-gray-500">{{ trans('double-entry::general.basis') }}</span>
                    <span class="text-sm capitalize">{{ $journal->basis }}</span>
                </div>
                <div>
                    <span class="block text-sm font-medium text-gray-500">{{ trans('general.status') }}</span>
                    @if ($journal->status === 'posted')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            {{ trans('double-entry::general.statuses.posted') }}
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            {{ trans('double-entry::general.statuses.draft') }}
                        </span>
                    @endif
                </div>
            </div>

            @if ($journal->description)
                <div class="mb-6">
                    <span class="block text-sm font-medium text-gray-500">{{ trans('general.description') }}</span>
                    <span class="text-sm">{{ $journal->description }}</span>
                </div>
            @endif

            @if ($journal->creator)
                <div class="mb-6">
                    <span class="block text-sm font-medium text-gray-500">{{ trans('double-entry::general.created_by') }}</span>
                    <span class="text-sm">{{ $journal->creator->name }}</span>
                </div>
            @endif
        </div>

        <div class="bg-white rounded-xl shadow-sm">
            <table class="w-full">
                <thead>
                    <tr class="border-b">
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('double-entry::general.account') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('general.description') }}</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-gray-500">{{ trans('double-entry::general.debit') }}</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-gray-500">{{ trans('double-entry::general.credit') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($journal->lines as $line)
                        <tr class="border-b">
                            <td class="px-4 py-3 text-sm">{{ $line->account->display_name ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm">{{ $line->description ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-right">{{ $line->debit > 0 ? number_format($line->debit, 2) : '' }}</td>
                            <td class="px-4 py-3 text-sm text-right">{{ $line->credit > 0 ? number_format($line->credit, 2) : '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t-2 font-semibold">
                        <td colspan="2" class="px-4 py-3 text-sm text-right">{{ trans('general.total') }}</td>
                        <td class="px-4 py-3 text-sm text-right">{{ number_format($journal->total_debit, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-right">{{ number_format($journal->total_credit, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </x-slot>
</x-layouts.admin>
