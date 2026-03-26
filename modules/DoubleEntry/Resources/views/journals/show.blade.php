<x-layouts.admin>
    <x-slot name="title">
        {{ trans('double-entry::general.journal_entry') }}: {{ $journal->reference ?? '#' . $journal->id }}
    </x-slot>

    <x-slot name="buttons">
        @if ($journal->status === 'draft')
            <x-link href="{{ route('double-entry.journals.edit', $journal->id) }}" kind="primary">
                {{ trans('general.edit') }}
            </x-link>
        @endif

        <form method="POST" action="{{ route('double-entry.journals.duplicate', $journal->id) }}" class="inline">
            @csrf
            <button type="submit" class="px-4 py-2 bg-gray-200 text-gray-700 text-sm rounded-md hover:bg-gray-300">
                {{ trans('general.duplicate') }}
            </button>
        </form>

        <x-link href="{{ route('double-entry.journals.index') }}">
            {{ trans('general.back') }}
        </x-link>
    </x-slot>

    <x-slot name="content">
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <span class="text-sm text-gray-500">{{ trans('general.date') }}</span>
                    <p class="font-medium">{{ $journal->date->format('Y-m-d') }}</p>
                </div>
                <div>
                    <span class="text-sm text-gray-500">{{ trans('double-entry::general.reference') }}</span>
                    <p class="font-medium">{{ $journal->reference ?? '-' }}</p>
                </div>
                <div>
                    <span class="text-sm text-gray-500">{{ trans('double-entry::general.basis') }}</span>
                    <p class="font-medium capitalize">{{ $journal->basis }}</p>
                </div>
                <div>
                    <span class="text-sm text-gray-500">{{ trans('general.status') }}</span>
                    <p>
                        @if ($journal->status === 'posted')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                {{ trans('double-entry::general.statuses.posted') }}
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                {{ trans('double-entry::general.statuses.draft') }}
                            </span>
                        @endif
                    </p>
                </div>
            </div>

            @if ($journal->description)
                <div class="mt-4">
                    <span class="text-sm text-gray-500">{{ trans('general.description') }}</span>
                    <p class="font-medium">{{ $journal->description }}</p>
                </div>
            @endif

            @if ($journal->recurring_frequency)
                <div class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <span class="text-sm text-gray-500">{{ trans('double-entry::general.recurring_frequency') }}</span>
                        <p class="font-medium capitalize">{{ $journal->recurring_frequency }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">{{ trans('double-entry::general.next_recurring_date') }}</span>
                        <p class="font-medium">{{ $journal->next_recurring_date?->format('Y-m-d') ?? '-' }}</p>
                    </div>
                </div>
            @endif

            @if ($journal->creator)
                <div class="mt-4">
                    <span class="text-sm text-gray-500">{{ trans('double-entry::general.created_by') }}</span>
                    <p class="font-medium">{{ $journal->creator->name }}</p>
                </div>
            @endif
        </div>

        {{-- Journal Lines --}}
        <div class="bg-white rounded-xl shadow-sm">
            <table class="w-full">
                <thead>
                    <tr class="border-b">
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('double-entry::general.account') }}</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-gray-500">{{ trans('double-entry::general.debit') }}</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-gray-500">{{ trans('double-entry::general.credit') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('general.description') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($journal->lines as $line)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm">
                                {{ $line->account->code ?? '' }} - {{ $line->account->name ?? 'Unknown' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-right">
                                {{ $line->debit > 0 ? number_format($line->debit, 2) : '' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-right">
                                {{ $line->credit > 0 ? number_format($line->credit, 2) : '' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                {{ $line->description ?? '' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t font-semibold bg-gray-50">
                        <td class="px-4 py-3 text-sm text-right">{{ trans('general.total') }}</td>
                        <td class="px-4 py-3 text-sm text-right">{{ number_format($journal->total_debit, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-right">{{ number_format($journal->total_credit, 2) }}</td>
                        <td class="px-4 py-3 text-sm">
                            @if ($journal->is_balanced)
                                <span class="text-green-600">&#10003; {{ trans('double-entry::general.balanced') }}</span>
                            @else
                                <span class="text-red-600">&#10007; {{ trans('double-entry::general.unbalanced') }}</span>
                            @endif
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </x-slot>
</x-layouts.admin>
