<x-layouts.admin>
    <x-slot name="title">{{ trans('double-entry::general.journal_entries') }}</x-slot>

    <x-slot name="buttons">
        <x-link href="{{ route('double-entry.journals.create') }}" kind="primary">
            {{ trans('general.title.new', ['type' => trans('double-entry::general.journal_entry')]) }}
        </x-link>
    </x-slot>

    <x-slot name="content">
        <div class="rounded-xl bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b text-gray-500">
                            <th class="px-6 py-3">{{ trans('double-entry::general.date') }}</th>
                            <th class="px-6 py-3">{{ trans('double-entry::general.number') }}</th>
                            <th class="px-6 py-3">{{ trans('double-entry::general.description') }}</th>
                            <th class="px-6 py-3 text-right">{{ trans('double-entry::general.debit') }}</th>
                            <th class="px-6 py-3 text-right">{{ trans('double-entry::general.credit') }}</th>
                            <th class="px-6 py-3 text-center">{{ trans('double-entry::general.status') }}</th>
                            <th class="px-6 py-3 text-right">{{ trans('general.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($journals as $journal)
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="px-6 py-3">{{ $journal->date->format('Y-m-d') }}</td>
                                <td class="px-6 py-3">
                                    <a href="{{ route('double-entry.journals.show', $journal->id) }}" class="font-medium text-blue-600 hover:text-blue-800">
                                        {{ $journal->number }}
                                    </a>
                                </td>
                                <td class="px-6 py-3 text-gray-600">{{ Str::limit($journal->description, 50) }}</td>
                                <td class="px-6 py-3 text-right font-mono">{{ number_format($journal->total_debits, 2) }}</td>
                                <td class="px-6 py-3 text-right font-mono">{{ number_format($journal->total_credits, 2) }}</td>
                                <td class="px-6 py-3 text-center">
                                    @if($journal->status === 'posted')
                                        <span class="inline-flex rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700">{{ trans('double-entry::general.statuses.posted') }}</span>
                                    @elseif($journal->status === 'voided')
                                        <span class="inline-flex rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-700">{{ trans('double-entry::general.statuses.voided') }}</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-700">{{ trans('double-entry::general.statuses.draft') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('double-entry.journals.show', $journal->id) }}" class="text-gray-400 hover:text-gray-600">
                                            <span class="material-icons text-lg">visibility</span>
                                        </a>
                                        <a href="{{ route('double-entry.journals.edit', $journal->id) }}" class="text-gray-400 hover:text-gray-600">
                                            <span class="material-icons text-lg">edit</span>
                                        </a>
                                        @if($journal->status !== 'posted')
                                            <form action="{{ route('double-entry.journals.destroy', $journal->id) }}" method="POST" class="inline" onsubmit="return confirm('{{ trans('general.delete_confirm', ['name' => $journal->number]) }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-gray-400 hover:text-red-600">
                                                    <span class="material-icons text-lg">delete</span>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    {{ trans('general.no_records') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($journals->hasPages())
                <div class="border-t px-6 py-3">
                    {{ $journals->links() }}
                </div>
            @endif
        </div>
    </x-slot>
</x-layouts.admin>
