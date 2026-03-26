<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ trans('estimates::general.portal.title') }} - {{ $estimate->document_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-4xl mx-auto py-8 px-4">
        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <p class="text-green-700">{{ session('success') }}</p>
            </div>
        @endif
        @if (session('error'))
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <p class="text-red-700">{{ session('error') }}</p>
            </div>
        @endif

        {{-- Status Banner --}}
        @if ($estimate->status === 'approved')
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6 text-center">
                <span class="material-icons text-green-600 align-middle">check_circle</span>
                <span class="text-green-700 font-medium">{{ trans('estimates::general.portal.already_approved') }}</span>
            </div>
        @elseif ($estimate->status === 'refused')
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6 text-center">
                <span class="material-icons text-red-600 align-middle">cancel</span>
                <span class="text-red-700 font-medium">{{ trans('estimates::general.portal.already_refused') }}</span>
            </div>
        @elseif ($estimate->status === 'converted')
            <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 mb-6 text-center">
                <span class="material-icons text-purple-600 align-middle">receipt</span>
                <span class="text-purple-700 font-medium">{{ trans('estimates::general.portal.already_converted') }}</span>
            </div>
        @elseif ($estimate->status === 'expired')
            <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 mb-6 text-center">
                <span class="material-icons text-orange-600 align-middle">schedule</span>
                <span class="text-orange-700 font-medium">{{ trans('estimates::general.portal.estimate_expired') }}</span>
            </div>
        @endif

        {{-- Estimate Document --}}
        <div class="bg-white rounded-xl shadow-sm p-8 mb-6">
            {{-- Header --}}
            <div class="flex justify-between items-start mb-8">
                <div>
                    @if ($estimate->company)
                        <h1 class="text-2xl font-bold text-purple-700">{{ $estimate->company->name }}</h1>
                        @if ($estimate->company->address)
                            <p class="text-sm text-gray-600">{{ $estimate->company->address }}</p>
                        @endif
                        @if ($estimate->company->email)
                            <p class="text-sm text-gray-600">{{ $estimate->company->email }}</p>
                        @endif
                    @endif
                </div>
                <div class="text-right">
                    <h2 class="text-3xl font-bold text-purple-700">ESTIMATE</h2>
                    <p class="text-gray-600 mt-1">{{ $estimate->document_number }}</p>
                </div>
            </div>

            {{-- Addresses --}}
            <div class="grid grid-cols-2 gap-8 mb-8">
                <div>
                    <h4 class="text-xs text-gray-400 uppercase tracking-wider mb-2">{{ trans('estimates::general.portal.from') }}</h4>
                    @if ($estimate->company)
                        <p class="font-semibold">{{ $estimate->company->name }}</p>
                        @if ($estimate->company->phone)
                            <p class="text-sm text-gray-600">{{ $estimate->company->phone }}</p>
                        @endif
                    @endif
                </div>
                <div>
                    <h4 class="text-xs text-gray-400 uppercase tracking-wider mb-2">{{ trans('estimates::general.portal.to') }}</h4>
                    <p class="font-semibold">{{ $estimate->contact_name }}</p>
                    @if ($estimate->contact_email)
                        <p class="text-sm text-gray-600">{{ $estimate->contact_email }}</p>
                    @endif
                    @if ($estimate->contact_address)
                        <p class="text-sm text-gray-600">{{ $estimate->contact_address }}</p>
                    @endif
                </div>
            </div>

            {{-- Meta --}}
            <div class="grid grid-cols-3 gap-4 mb-8 p-4 bg-gray-50 rounded-lg">
                <div>
                    <span class="text-xs text-gray-400 uppercase">{{ trans('estimates::general.estimate_date') }}</span>
                    <p class="font-medium">{{ $estimate->issued_at->format('M d, Y') }}</p>
                </div>
                <div>
                    <span class="text-xs text-gray-400 uppercase">{{ trans('estimates::general.expiry_date') }}</span>
                    <p class="font-medium">{{ $estimate->due_at?->format('M d, Y') ?? '-' }}</p>
                </div>
                <div>
                    <span class="text-xs text-gray-400 uppercase">{{ trans('estimates::general.amount') }}</span>
                    <p class="font-bold text-lg text-purple-700">{{ money($estimate->amount, $estimate->currency_code) }}</p>
                </div>
            </div>

            {{-- Line Items --}}
            <table class="w-full mb-6">
                <thead>
                    <tr class="border-b-2 border-gray-200">
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ trans('estimates::general.item_name') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ trans('estimates::general.quantity') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ trans('estimates::general.price') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ trans('estimates::general.amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($estimate->items as $item)
                        <tr class="border-b">
                            <td class="px-4 py-3">
                                <div class="font-medium text-sm">{{ $item->name }}</div>
                                @if ($item->description)
                                    <div class="text-gray-500 text-xs">{{ $item->description }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-right">{{ $item->quantity }}</td>
                            <td class="px-4 py-3 text-sm text-right">{{ money($item->price, $estimate->currency_code) }}</td>
                            <td class="px-4 py-3 text-sm text-right font-medium">{{ money($item->total, $estimate->currency_code) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Totals --}}
            <div class="flex justify-end">
                <div class="w-72">
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
        </div>

        {{-- Action Buttons --}}
        @if (in_array($estimate->status, ['sent', 'viewed']))
            <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <form method="POST" action="{{ route('estimates.portal.approve', $token) }}">
                        @csrf
                        <button type="submit" class="w-full sm:w-auto bg-green-600 text-white px-8 py-3 rounded-lg text-lg font-medium hover:bg-green-700 transition flex items-center justify-center gap-2">
                            <span class="material-icons">check_circle</span>
                            {{ trans('estimates::general.portal.approve_estimate') }}
                        </button>
                    </form>

                    <div x-data="{ showRefuse: false }">
                        <button type="button" onclick="document.getElementById('refuse-form').classList.toggle('hidden')" class="w-full sm:w-auto bg-red-600 text-white px-8 py-3 rounded-lg text-lg font-medium hover:bg-red-700 transition flex items-center justify-center gap-2">
                            <span class="material-icons">cancel</span>
                            {{ trans('estimates::general.portal.refuse_estimate') }}
                        </button>

                        <form id="refuse-form" method="POST" action="{{ route('estimates.portal.refuse', $token) }}" class="hidden mt-4">
                            @csrf
                            <textarea name="reason" rows="3" placeholder="{{ trans('estimates::general.portal.refuse_reason') }}"
                                class="w-full rounded-lg border-gray-300 text-sm mb-3"></textarea>
                            <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded-lg text-sm hover:bg-red-700">
                                {{ trans('estimates::general.portal.refuse_estimate') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        {{-- Footer --}}
        <div class="text-center text-gray-400 text-sm py-4">
            @if ($estimate->company)
                {{ trans('estimates::general.portal.powered_by') }} {{ $estimate->company->name }}
            @endif
        </div>
    </div>
</body>
</html>
