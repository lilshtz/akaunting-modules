<x-layouts.admin>
    <x-slot name="title">
        {{ trans('estimates::general.estimates') }}
    </x-slot>

    <x-slot name="favorite"
        title="{{ trans('estimates::general.estimates') }}"
        icon="request_quote"
        route="estimates.estimates.index"
    ></x-slot>

    <x-slot name="buttons">
        <x-link href="{{ route('estimates.estimates.create') }}" kind="primary">
            {{ trans('general.title.new', ['type' => trans('estimates::general.estimate')]) }}
        </x-link>
    </x-slot>

    <x-slot name="content">
        {{-- Filters --}}
        <div class="mb-4 flex flex-wrap gap-3">
            <form method="GET" action="{{ route('estimates.estimates.index') }}" class="flex flex-wrap gap-3 w-full">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ trans('general.search') }}..."
                    class="rounded-lg border-gray-300 text-sm px-3 py-2" />

                <select name="status" class="rounded-lg border-gray-300 text-sm px-3 py-2" onchange="this.form.submit()">
                    <option value="">{{ trans('general.statuses') }}</option>
                    @foreach ($statuses as $key => $label)
                        <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>

                <select name="contact_id" class="rounded-lg border-gray-300 text-sm px-3 py-2" onchange="this.form.submit()">
                    <option value="">{{ trans('estimates::general.customer') }}</option>
                    @foreach ($customers as $id => $name)
                        <option value="{{ $id }}" {{ request('contact_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>

                <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-purple-700">
                    {{ trans('general.search') }}
                </button>
            </form>
        </div>

        {{-- Estimates Table --}}
        <div class="bg-white rounded-xl shadow-sm">
            <table class="w-full">
                <thead>
                    <tr class="border-b">
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('estimates::general.estimate_number') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('estimates::general.customer') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('estimates::general.estimate_date') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('estimates::general.expiry_date') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('general.status') }}</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-gray-500">{{ trans('estimates::general.amount') }}</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-gray-500">{{ trans('general.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($estimates as $estimate)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm">
                                <a href="{{ route('estimates.estimates.show', $estimate->id) }}" class="text-purple-700 hover:underline font-medium">
                                    {{ $estimate->document_number }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-sm">{{ $estimate->contact_name }}</td>
                            <td class="px-4 py-3 text-sm">{{ $estimate->issued_at?->format('M d, Y') }}</td>
                            <td class="px-4 py-3 text-sm">{{ $estimate->due_at?->format('M d, Y') ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $estimate->status_badge_color }}">
                                    {{ $estimate->status_display }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-right font-medium">
                                {{ money($estimate->amount, $estimate->currency_code) }}
                            </td>
                            <td class="px-4 py-3 text-sm text-right">
                                <x-dropdown id="dropdown-{{ $estimate->id }}">
                                    <x-dropdown.link href="{{ route('estimates.estimates.show', $estimate->id) }}">
                                        {{ trans('general.show') }}
                                    </x-dropdown.link>
                                    @if ($estimate->isEditable())
                                        <x-dropdown.link href="{{ route('estimates.estimates.edit', $estimate->id) }}">
                                            {{ trans('general.edit') }}
                                        </x-dropdown.link>
                                    @endif
                                    @if ($estimate->status === 'draft' || $estimate->status === 'refused')
                                        <x-dropdown.link href="{{ route('estimates.estimates.send', $estimate->id) }}"
                                            data-method="POST">
                                            {{ trans('estimates::general.actions.send') }}
                                        </x-dropdown.link>
                                    @endif
                                    <x-dropdown.link href="{{ route('estimates.estimates.duplicate', $estimate->id) }}"
                                        data-method="POST">
                                        {{ trans('general.duplicate') }}
                                    </x-dropdown.link>
                                    <x-dropdown.link href="{{ route('estimates.estimates.pdf', $estimate->id) }}" target="_blank">
                                        {{ trans('estimates::general.actions.download_pdf') }}
                                    </x-dropdown.link>
                                    @if ($estimate->isDeletable())
                                        <x-delete-link :model="$estimate" route="estimates.estimates.destroy" />
                                    @endif
                                </x-dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-gray-500">
                                {{ trans('estimates::general.no_estimates') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($estimates->hasPages())
            <div class="mt-4">
                {{ $estimates->withQueryString()->links() }}
            </div>
        @endif
    </x-slot>
</x-layouts.admin>
