<div class="bg-white rounded-xl shadow-sm p-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold">{{ trans('receipts::general.receipt_summary') }}</h3>
        <a href="{{ route('receipts.receipts.index') }}" class="text-purple-500 hover:text-purple-700 text-sm">
            {{ trans('general.show_more') }}
        </a>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div class="text-center p-3 bg-yellow-50 rounded-lg">
            <p class="text-2xl font-bold text-yellow-600">{{ $stats['uploaded'] }}</p>
            <p class="text-sm text-gray-600">{{ trans('receipts::general.statuses.uploaded') }}</p>
        </div>
        <div class="text-center p-3 bg-blue-50 rounded-lg">
            <p class="text-2xl font-bold text-blue-600">{{ $stats['reviewed'] }}</p>
            <p class="text-sm text-gray-600">{{ trans('receipts::general.statuses.reviewed') }}</p>
        </div>
        <div class="text-center p-3 bg-green-50 rounded-lg">
            <p class="text-2xl font-bold text-green-600">{{ $stats['processed'] }}</p>
            <p class="text-sm text-gray-600">{{ trans('receipts::general.statuses.processed') }}</p>
        </div>
        <div class="text-center p-3 bg-purple-50 rounded-lg">
            <p class="text-2xl font-bold text-purple-600">{{ $stats['total_pending'] }}</p>
            <p class="text-sm text-gray-600">Pending</p>
        </div>
    </div>

    @if($stats['total_pending'] > 0)
        <div class="mt-4">
            <a href="{{ route('receipts.receipts.index', ['status' => 'uploaded']) }}"
               class="block w-full text-center px-4 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600 text-sm">
                {{ trans('receipts::general.review') }} Pending Receipts
            </a>
        </div>
    @endif
</div>
