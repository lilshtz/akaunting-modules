<x-layouts.admin>
    <x-slot name="title">
        {{ trans('receipts::general.receipt_inbox') }}
    </x-slot>

    <x-slot name="favorite"
        title="{{ trans('receipts::general.receipt_inbox') }}"
        icon="receipt_long"
        route="receipts.receipts.index"
    ></x-slot>

    <x-slot name="buttons">
        <a href="{{ route('receipts.receipts.upload') }}" class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 mr-2">
            <span class="material-icons text-sm align-middle">cloud_upload</span>
            {{ trans('receipts::general.actions.upload') }}
        </a>
        <a href="{{ route('receipts.receipts.bulk-upload') }}" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 mr-2">
            <span class="material-icons text-sm align-middle">upload_file</span>
            {{ trans('receipts::general.actions.bulk_upload') }}
        </a>
        <form method="POST" action="{{ route('receipts.receipts.bulk-process') }}" class="inline">
            @csrf
            <button type="submit" class="px-4 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600">
                <span class="material-icons text-sm align-middle">playlist_play</span>
                {{ trans('receipts::general.actions.bulk_process') }}
            </button>
        </form>
    </x-slot>

    <x-slot name="content">
        {{-- Status Filter Tabs --}}
        <div class="flex space-x-2 mb-6">
            <a href="{{ route('receipts.receipts.index') }}"
               class="px-4 py-2 rounded-lg {{ !request('status') ? 'bg-purple-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                All ({{ array_sum($statusCounts) }})
            </a>
            @foreach(\Modules\Receipts\Models\Receipt::STATUSES as $status)
                <a href="{{ route('receipts.receipts.index', ['status' => $status]) }}"
                   class="px-4 py-2 rounded-lg {{ request('status') === $status ? 'bg-purple-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    {{ trans('receipts::general.statuses.' . $status) }} ({{ $statusCounts[$status] ?? 0 }})
                </a>
            @endforeach
        </div>

        {{-- Search --}}
        <form method="GET" action="{{ route('receipts.receipts.index') }}" class="mb-6">
            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
            <div class="flex">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="{{ trans('general.search') }}..."
                       class="flex-1 border border-gray-300 rounded-l-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                <button type="submit" class="bg-purple-500 text-white px-6 py-2 rounded-r-lg hover:bg-purple-600">
                    {{ trans('general.search') }}
                </button>
            </div>
        </form>

        {{-- Receipt Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
            @forelse ($receipts as $receipt)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
                    {{-- Thumbnail --}}
                    <div class="aspect-square bg-gray-100 relative">
                        @if($receipt->thumbnail_path)
                            <img src="{{ Storage::disk('public')->url($receipt->thumbnail_path) }}"
                                 alt="{{ $receipt->vendor_name }}"
                                 class="w-full h-full object-cover">
                        @elseif($receipt->image_path)
                            <img src="{{ Storage::disk('public')->url($receipt->image_path) }}"
                                 alt="{{ $receipt->vendor_name }}"
                                 class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center">
                                <span class="material-icons text-4xl text-gray-400">receipt_long</span>
                            </div>
                        @endif

                        {{-- Status Badge --}}
                        <span class="absolute top-2 right-2 px-2 py-1 text-xs font-semibold rounded-full
                            {{ $receipt->status === 'uploaded' ? 'bg-yellow-100 text-yellow-800' : '' }}
                            {{ $receipt->status === 'reviewed' ? 'bg-blue-100 text-blue-800' : '' }}
                            {{ $receipt->status === 'processed' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $receipt->status === 'matched' ? 'bg-purple-100 text-purple-800' : '' }}">
                            {{ trans('receipts::general.statuses.' . $receipt->status) }}
                        </span>
                    </div>

                    {{-- Info --}}
                    <div class="p-3">
                        <h3 class="font-semibold text-sm truncate">
                            {{ $receipt->vendor_name ?? trans('general.na') }}
                        </h3>
                        <p class="text-gray-600 text-sm">
                            {{ $receipt->receipt_date ? $receipt->receipt_date->format('M d, Y') : '-' }}
                        </p>
                        <p class="font-bold text-lg mt-1">
                            {{ $receipt->formatted_amount }}
                        </p>

                        {{-- Actions --}}
                        <div class="mt-2 flex space-x-1">
                            @if(in_array($receipt->status, ['uploaded', 'reviewed']))
                                <a href="{{ route('receipts.receipts.review', $receipt->id) }}"
                                   class="flex-1 text-center px-2 py-1 bg-blue-50 text-blue-700 rounded text-xs hover:bg-blue-100">
                                    {{ trans('receipts::general.review') }}
                                </a>
                            @endif
                            @if($receipt->status === 'reviewed')
                                <a href="{{ route('receipts.receipts.process', $receipt->id) }}"
                                   class="flex-1 text-center px-2 py-1 bg-green-50 text-green-700 rounded text-xs hover:bg-green-100">
                                    {{ trans('receipts::general.process') }}
                                </a>
                            @endif
                            <form method="POST" action="{{ route('receipts.receipts.destroy', $receipt->id) }}"
                                  onsubmit="return confirm('{{ trans('general.delete_confirm') }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-2 py-1 bg-red-50 text-red-700 rounded text-xs hover:bg-red-100">
                                    <span class="material-icons text-sm">delete</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-16">
                    <span class="material-icons text-6xl text-gray-300">receipt_long</span>
                    <p class="mt-4 text-gray-500">{{ trans('receipts::general.messages.no_receipts') }}</p>
                    <a href="{{ route('receipts.receipts.upload') }}"
                       class="mt-4 inline-block px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600">
                        {{ trans('receipts::general.actions.upload') }}
                    </a>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $receipts->links() }}
        </div>
    </x-slot>
</x-layouts.admin>
