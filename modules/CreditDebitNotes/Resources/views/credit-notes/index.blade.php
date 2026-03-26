<x-layouts.admin>
    <x-slot name="title">
        {{ trans('credit-debit-notes::general.credit_notes') }}
    </x-slot>

    <x-slot name="favorite"
        title="{{ trans('credit-debit-notes::general.credit_notes') }}"
        icon="note_add"
        route="credit-debit-notes.credit-notes.index"
    ></x-slot>

    <x-slot name="buttons">
        <x-link href="{{ route('credit-debit-notes.credit-notes.create') }}" kind="primary">
            {{ trans('general.title.new', ['type' => trans('credit-debit-notes::general.credit_note')]) }}
        </x-link>
    </x-slot>

    <x-slot name="content">
        {{-- Filters --}}
        <div class="mb-4 flex flex-wrap gap-3">
            <form method="GET" action="{{ route('credit-debit-notes.credit-notes.index') }}" class="flex flex-wrap gap-3 w-full">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ trans('general.search') }}..."
                    class="rounded-lg border-gray-300 text-sm px-3 py-2" />

                <select name="status" class="rounded-lg border-gray-300 text-sm px-3 py-2" onchange="this.form.submit()">
                    <option value="">{{ trans('general.statuses') }}</option>
                    @foreach ($statuses as $key => $label)
                        <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>

                <select name="contact_id" class="rounded-lg border-gray-300 text-sm px-3 py-2" onchange="this.form.submit()">
                    <option value="">{{ trans('credit-debit-notes::general.customer') }}</option>
                    @foreach ($customers as $id => $name)
                        <option value="{{ $id }}" {{ request('contact_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>

                <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-purple-700">
                    {{ trans('general.search') }}
                </button>
            </form>
        </div>

        {{-- Credit Notes Table --}}
        <div class="bg-white rounded-xl shadow-sm">
            <table class="w-full">
                <thead>
                    <tr class="border-b">
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('credit-debit-notes::general.note_number') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('credit-debit-notes::general.customer') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('credit-debit-notes::general.linked_invoice') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('credit-debit-notes::general.note_date') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('general.status') }}</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-gray-500">{{ trans('credit-debit-notes::general.amount') }}</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-gray-500">{{ trans('general.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($creditNotes as $note)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm">
                                <a href="{{ route('credit-debit-notes.credit-notes.show', $note->id) }}" class="text-purple-700 hover:underline font-medium">
                                    {{ $note->document_number }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-sm">{{ $note->contact_name }}</td>
                            <td class="px-4 py-3 text-sm">{{ $note->parent_id ? optional($note->linkedInvoice)->document_number : '-' }}</td>
                            <td class="px-4 py-3 text-sm">{{ $note->issued_at?->format('M d, Y') }}</td>
                            <td class="px-4 py-3 text-sm">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $note->status_badge_color }}">
                                    {{ $note->status_display }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-right font-medium">
                                {{ money($note->amount, $note->currency_code) }}
                            </td>
                            <td class="px-4 py-3 text-sm text-right">
                                <x-dropdown id="dropdown-{{ $note->id }}">
                                    <x-dropdown.link href="{{ route('credit-debit-notes.credit-notes.show', $note->id) }}">
                                        {{ trans('general.show') }}
                                    </x-dropdown.link>
                                    @if ($note->isEditable())
                                        <x-dropdown.link href="{{ route('credit-debit-notes.credit-notes.edit', $note->id) }}">
                                            {{ trans('general.edit') }}
                                        </x-dropdown.link>
                                    @endif
                                    @if ($note->status === 'draft')
                                        <x-dropdown.link href="{{ route('credit-debit-notes.credit-notes.send', $note->id) }}" data-method="POST">
                                            {{ trans('credit-debit-notes::general.actions.send') }}
                                        </x-dropdown.link>
                                    @endif
                                    <x-dropdown.link href="{{ route('credit-debit-notes.credit-notes.pdf', $note->id) }}" target="_blank">
                                        {{ trans('credit-debit-notes::general.actions.download_pdf') }}
                                    </x-dropdown.link>
                                    @if ($note->isDeletable())
                                        <x-delete-link :model="$note" route="credit-debit-notes.credit-notes.destroy" />
                                    @endif
                                </x-dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-gray-500">
                                {{ trans('credit-debit-notes::general.no_credit_notes') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($creditNotes->hasPages())
            <div class="mt-4">
                {{ $creditNotes->withQueryString()->links() }}
            </div>
        @endif
    </x-slot>
</x-layouts.admin>
