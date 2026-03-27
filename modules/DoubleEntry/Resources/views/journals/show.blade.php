<x-layouts.admin>
    <x-slot name="title">{{ trans('double-entry::general.journal_entry') }} #{{ $journal->number }}</x-slot>

    <x-slot name="buttons">
        <x-link href="{{ route('double-entry.journals.edit', $journal->id) }}" kind="primary">
            {{ trans('general.edit') }}
        </x-link>
    </x-slot>

    <x-slot name="content">
        <div class="space-y-6">
            {{-- Header --}}
            <div class="rounded-xl bg-white p-6 shadow-sm">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div>
                        <span class="text-sm text-gray-500">{{ trans('double-entry::general.number') }}</span>
                        <p class="font-medium">{{ $journal->number }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">{{ trans('double-entry::general.date') }}</span>
                        <p class="font-medium">{{ $journal->date->format('Y-m-d') }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">{{ trans('double-entry::general.status') }}</span>
                        <p>
                            @if($journal->status === 'posted')
                                <span class="inline-flex rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700">{{ trans('double-entry::general.statuses.posted') }}</span>
                            @elseif($journal->status === 'voided')
                                <span class="inline-flex rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-700">{{ trans('double-entry::general.statuses.voided') }}</span>
                            @else
                                <span class="inline-flex rounded-full bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-700">{{ trans('double-entry::general.statuses.draft') }}</span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">{{ trans('double-entry::general.reference') }}</span>
                        <p class="font-medium">{{ $journal->reference ?? '-' }}</p>
                    </div>
                </div>
                @if($journal->description)
                    <div class="mt-4 border-t pt-4">
                        <span class="text-sm text-gray-500">{{ trans('double-entry::general.description') }}</span>
                        <p class="text-gray-700">{{ $journal->description }}</p>
                    </div>
                @endif
            </div>

            {{-- Lines --}}
            <div class="rounded-xl bg-white p-6 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b text-gray-500">
                                <th class="pb-3 pr-6">{{ trans('double-entry::general.account') }}</th>
                                <th class="pb-3 pr-6">{{ trans('double-entry::general.description') }}</th>
                                <th class="pb-3 pr-6 text-right">{{ trans('double-entry::general.debit') }}</th>
                                <th class="pb-3 text-right">{{ trans('double-entry::general.credit') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($journal->lines as $line)
                                <tr class="border-b border-gray-100">
                                    <td class="py-3 pr-6">
                                        <span class="font-mono text-xs text-gray-500">{{ $line->account->code }}</span>
                                        {{ $line->account->name }}
                                    </td>
                                    <td class="py-3 pr-6 text-gray-600">{{ $line->description ?? '-' }}</td>
                                    <td class="py-3 pr-6 text-right font-mono">{{ $line->debit > 0 ? number_format($line->debit, 2) : '' }}</td>
                                    <td class="py-3 text-right font-mono">{{ $line->credit > 0 ? number_format($line->credit, 2) : '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t font-semibold">
                                <td colspan="2" class="py-3 pr-6 text-right">{{ trans('general.total') }}</td>
                                <td class="py-3 pr-6 text-right font-mono">{{ number_format($journal->total_debits, 2) }}</td>
                                <td class="py-3 text-right font-mono">{{ number_format($journal->total_credits, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </x-slot>
</x-layouts.admin>
