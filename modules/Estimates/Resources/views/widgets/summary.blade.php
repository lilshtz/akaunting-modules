<div class="bg-white rounded-xl shadow-sm p-6">
    <h3 class="text-lg font-semibold mb-4">{{ trans('estimates::general.estimate_summary') }}</h3>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="text-center p-3 bg-gray-50 rounded-lg">
            <p class="text-2xl font-bold text-gray-900">{{ $totalEstimates }}</p>
            <p class="text-xs text-gray-500">{{ trans('estimates::general.report.total_estimates') }}</p>
        </div>
        <div class="text-center p-3 bg-blue-50 rounded-lg">
            <p class="text-2xl font-bold text-blue-600">{{ $totalSent }}</p>
            <p class="text-xs text-gray-500">{{ trans('estimates::general.report.total_sent') }}</p>
        </div>
        <div class="text-center p-3 bg-green-50 rounded-lg">
            <p class="text-2xl font-bold text-green-600">{{ $approvalRate }}%</p>
            <p class="text-xs text-gray-500">{{ trans('estimates::general.report.approval_rate') }}</p>
        </div>
        <div class="text-center p-3 bg-purple-50 rounded-lg">
            <p class="text-2xl font-bold text-purple-600">{{ $conversionRate }}%</p>
            <p class="text-xs text-gray-500">{{ trans('estimates::general.report.conversion_rate') }}</p>
        </div>
    </div>

    {{-- Status Breakdown --}}
    <div class="mb-6">
        <h4 class="text-sm font-medium text-gray-500 mb-3">By Status</h4>
        <div class="flex flex-wrap gap-2">
            @foreach ($byStatus as $status => $count)
                @if ($count > 0)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                        @switch($status)
                            @case('draft') bg-gray-100 text-gray-800 @break
                            @case('sent') bg-blue-100 text-blue-800 @break
                            @case('viewed') bg-yellow-100 text-yellow-800 @break
                            @case('approved') bg-green-100 text-green-800 @break
                            @case('refused') bg-red-100 text-red-800 @break
                            @case('converted') bg-purple-100 text-purple-800 @break
                            @case('expired') bg-orange-100 text-orange-800 @break
                        @endswitch
                    ">
                        {{ trans('estimates::general.statuses.' . $status) }}: {{ $count }}
                    </span>
                @endif
            @endforeach
        </div>
    </div>

    {{-- Recent Estimates --}}
    @if ($recentEstimates->isNotEmpty())
        <h4 class="text-sm font-medium text-gray-500 mb-3">Recent Estimates</h4>
        <div class="space-y-2">
            @foreach ($recentEstimates as $estimate)
                <div class="flex items-center justify-between text-sm py-1">
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-purple-700">{{ $estimate->document_number }}</span>
                        <span class="text-gray-500">{{ $estimate->contact_name }}</span>
                    </div>
                    <span class="font-medium">{{ money($estimate->amount, $estimate->currency_code) }}</span>
                </div>
            @endforeach
        </div>
    @endif
</div>
