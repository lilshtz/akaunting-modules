<x-layouts.admin>
    <x-slot name="title">
        {{ $estimate->document_number }}
    </x-slot>

    <x-slot name="buttons">
        @if ($estimate->isEditable())
            <x-link href="{{ route('estimates.estimates.edit', $estimate->id) }}" kind="primary">
                {{ trans('general.edit') }}
            </x-link>
        @endif
    </x-slot>

    <x-slot name="content">
        {{-- Status & Actions Bar --}}
        <div class="bg-white rounded-xl shadow-sm p-4 mb-6 flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-4">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $estimate->status_badge_color }}">
                    {{ $estimate->status_display }}
                </span>
                <span class="text-gray-500 text-sm">{{ $estimate->document_number }}</span>
            </div>
            <div class="flex gap-2 flex-wrap">
                @if (in_array($estimate->status, ['draft', 'refused']))
                    <form method="POST" action="{{ route('estimates.estimates.send', $estimate->id) }}" class="inline">
                        @csrf
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">
                            {{ trans('estimates::general.actions.send') }}
                        </button>
                    </form>
                @endif

                @if (in_array($estimate->status, ['sent', 'viewed']))
                    <form method="POST" action="{{ route('estimates.estimates.approve', $estimate->id) }}" class="inline">
                        @csrf
                        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-700">
                            {{ trans('estimates::general.actions.mark_approved') }}
                        </button>
                    </form>

                    <form method="POST" action="{{ route('estimates.estimates.refuse', $estimate->id) }}" class="inline">
                        @csrf
                        <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-red-700">
                            {{ trans('estimates::general.actions.mark_refused') }}
                        </button>
                    </form>
                @endif

                @if ($estimate->status === 'approved')
                    <form method="POST" action="{{ route('estimates.estimates.convert', $estimate->id) }}" class="inline">
                        @csrf
                        <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-purple-700">
                            {{ trans('estimates::general.actions.convert') }}
                        </button>
                    </form>
                @endif

                <form method="POST" action="{{ route('estimates.estimates.duplicate', $estimate->id) }}" class="inline">
                    @csrf
                    <button type="submit" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-300">
                        {{ trans('estimates::general.actions.duplicate') }}
                    </button>
                </form>

                <a href="{{ route('estimates.estimates.pdf', $estimate->id) }}" target="_blank" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-300 inline-block">
                    {{ trans('estimates::general.actions.download_pdf') }}
                </a>
            </div>
        </div>

        {{-- Converted Invoice Link --}}
        @if ($estimate->status === 'converted' && $estimate->convertedInvoice)
            <div class="bg-purple-50 border border-purple-200 rounded-xl p-4 mb-6">
                <div class="flex items-center gap-2">
                    <span class="material-icons text-purple-600">receipt</span>
                    <span class="text-purple-800 font-medium">{{ trans('estimates::general.converted_invoice') }}:</span>
                    <span class="text-purple-700">{{ $estimate->convertedInvoice->document_number }}</span>
                </div>
            </div>
        @endif

        {{-- Portal Link --}}
        @if ($estimate->portalToken)
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="material-icons text-blue-600">link</span>
                        <span class="text-blue-800 font-medium">{{ trans('estimates::general.portal_link') }}:</span>
                        <span class="text-blue-700 text-sm break-all">{{ route('estimates.portal.show', $estimate->portalToken->token) }}</span>
                    </div>
                </div>
            </div>
        @endif

        {{-- Estimate Document --}}
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <div class="grid grid-cols-2 gap-6 mb-8">
                {{-- From --}}
                <div>
                    <h4 class="text-xs text-gray-500 uppercase tracking-wide mb-2">{{ trans('estimates::general.portal.from') }}</h4>
                    @if ($estimate->company)
                        <p class="font-semibold text-gray-900">{{ $estimate->company->name }}</p>
                        @if ($estimate->company->address)
                            <p class="text-sm text-gray-600">{{ $estimate->company->address }}</p>
                        @endif
                        @if ($estimate->company->email)
                            <p class="text-sm text-gray-600">{{ $estimate->company->email }}</p>
                        @endif
                    @endif
                </div>
                {{-- To --}}
                <div class="text-right">
                    <h4 class="text-xs text-gray-500 uppercase tracking-wide mb-2">{{ trans('estimates::general.portal.to') }}</h4>
                    <p class="font-semibold text-gray-900">{{ $estimate->contact_name }}</p>
                    @if ($estimate->contact_email)
                        <p class="text-sm text-gray-600">{{ $estimate->contact_email }}</p>
                    @endif
                    @if ($estimate->contact_address)
                        <p class="text-sm text-gray-600">{{ $estimate->contact_address }}</p>
                    @endif
                    @if ($estimate->contact_phone)
                        <p class="text-sm text-gray-600">{{ $estimate->contact_phone }}</p>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4 mb-8">
                <div>
                    <span class="text-xs text-gray-500 uppercase">{{ trans('estimates::general.estimate_number') }}</span>
                    <p class="font-medium">{{ $estimate->document_number }}</p>
                </div>
                <div>
                    <span class="text-xs text-gray-500 uppercase">{{ trans('estimates::general.estimate_date') }}</span>
                    <p class="font-medium">{{ $estimate->issued_at->format('M d, Y') }}</p>
                </div>
                <div>
                    <span class="text-xs text-gray-500 uppercase">{{ trans('estimates::general.expiry_date') }}</span>
                    <p class="font-medium">{{ $estimate->due_at?->format('M d, Y') ?? '-' }}</p>
                </div>
            </div>

            {{-- Line Items --}}
            <table class="w-full mb-6">
                <thead>
                    <tr class="border-b-2 border-gray-200">
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">{{ trans('estimates::general.item_name') }}</th>
                        <th class="px-4 py-2 text-right text-sm font-medium text-gray-500">{{ trans('estimates::general.quantity') }}</th>
                        <th class="px-4 py-2 text-right text-sm font-medium text-gray-500">{{ trans('estimates::general.price') }}</th>
                        <th class="px-4 py-2 text-right text-sm font-medium text-gray-500">{{ trans('estimates::general.discount') }}</th>
                        <th class="px-4 py-2 text-right text-sm font-medium text-gray-500">{{ trans('estimates::general.tax') }}</th>
                        <th class="px-4 py-2 text-right text-sm font-medium text-gray-500">{{ trans('estimates::general.amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($estimate->items as $item)
                        <tr class="border-b">
                            <td class="px-4 py-3 text-sm">
                                <div class="font-medium">{{ $item->name }}</div>
                                @if ($item->description)
                                    <div class="text-gray-500 text-xs">{{ $item->description }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-right">{{ $item->quantity }}</td>
                            <td class="px-4 py-3 text-sm text-right">{{ money($item->price, $estimate->currency_code) }}</td>
                            <td class="px-4 py-3 text-sm text-right">{{ $item->discount_rate ? $item->discount_rate . '%' : '-' }}</td>
                            <td class="px-4 py-3 text-sm text-right">{{ $item->tax ? money($item->tax, $estimate->currency_code) : '-' }}</td>
                            <td class="px-4 py-3 text-sm text-right font-medium">{{ money($item->total, $estimate->currency_code) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Totals --}}
            <div class="flex justify-end">
                <div class="w-64">
                    @foreach ($estimate->totals->sortBy('sort_order') as $total)
                        <div class="flex justify-between py-2 {{ $total->code === 'total' ? 'border-t-2 border-gray-300 font-bold text-lg' : '' }} {{ $total->code === 'discount' ? 'text-red-600' : '' }}">
                            <span class="text-gray-600">{{ trans($total->name) }}</span>
                            <span>{{ $total->code === 'discount' ? '-' : '' }}{{ money($total->amount, $estimate->currency_code) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Notes --}}
            @if ($estimate->notes)
                <div class="mt-8 pt-4 border-t">
                    <h4 class="text-sm font-medium text-gray-500 mb-2">{{ trans('estimates::general.notes') }}</h4>
                    <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $estimate->notes }}</p>
                </div>
            @endif

            @if ($estimate->footer)
                <div class="mt-4 pt-4 border-t">
                    <p class="text-sm text-gray-500 whitespace-pre-wrap">{{ $estimate->footer }}</p>
                </div>
            @endif
        </div>

        {{-- Timeline --}}
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold mb-4">{{ trans('estimates::general.timeline') }}</h3>

            @if ($estimate->estimateHistories->isNotEmpty())
                <div class="space-y-4">
                    @foreach ($estimate->estimateHistories->sortByDesc('created_at') as $history)
                        <div class="flex gap-4">
                            <div class="flex-shrink-0 w-2 h-2 mt-2 rounded-full
                                @switch($history->status)
                                    @case('draft') bg-gray-400 @break
                                    @case('sent') bg-blue-400 @break
                                    @case('viewed') bg-yellow-400 @break
                                    @case('approved') bg-green-400 @break
                                    @case('refused') bg-red-400 @break
                                    @case('converted') bg-purple-400 @break
                                    @case('expired') bg-orange-400 @break
                                    @default bg-gray-400
                                @endswitch
                            "></div>
                            <div class="flex-1">
                                <p class="text-sm text-gray-700">{{ $history->description }}</p>
                                <p class="text-xs text-gray-400 mt-1">{{ $history->created_at->format('M d, Y H:i') }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 text-sm">{{ trans('estimates::general.no_history') }}</p>
            @endif
        </div>
    </x-slot>
</x-layouts.admin>
