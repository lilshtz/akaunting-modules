<x-layouts.admin>
    <x-slot name="title">
        {{ trans('receipts::general.review') }} - {{ $receipt->vendor_name ?? trans('receipts::general.receipt') }} #{{ $receipt->id }}
    </x-slot>

    <x-slot name="content">
        {{-- Duplicate Warning --}}
        @if($duplicates->isNotEmpty())
            <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 mb-6">
                <div class="flex items-center">
                    <span class="material-icons text-yellow-600 mr-2">warning</span>
                    <strong class="text-yellow-800">
                        {{ trans('receipts::general.messages.duplicate_warning', ['count' => $duplicates->count()]) }}
                    </strong>
                </div>
                <ul class="mt-2 ml-8 text-sm text-yellow-700">
                    @foreach($duplicates as $dup)
                        <li>
                            #{{ $dup->id }} - {{ $dup->vendor_name }} -
                            {{ $dup->formatted_amount }} -
                            {{ $dup->receipt_date ? $dup->receipt_date->format('M d, Y') : '-' }}
                            ({{ trans('receipts::general.statuses.' . $dup->status) }})
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Original Image --}}
            <div class="bg-white rounded-xl shadow-sm p-4">
                <h2 class="text-lg font-semibold mb-4">{{ trans('receipts::general.fields.image') }}</h2>
                @if($receipt->image_path)
                    <img src="{{ Storage::disk('public')->url($receipt->image_path) }}"
                         alt="{{ $receipt->vendor_name }}"
                         class="w-full rounded-lg shadow max-h-[600px] object-contain">
                @else
                    <div class="text-center py-16 text-gray-400">
                        <span class="material-icons text-6xl">image_not_supported</span>
                        <p>No image available</p>
                    </div>
                @endif
            </div>

            {{-- Editable Extracted Data --}}
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-semibold mb-4">
                    {{ trans('receipts::general.messages.review_extracted') }}
                </h2>

                <form method="POST" action="{{ route('receipts.receipts.update', $receipt->id) }}">
                    @csrf
                    @method('PUT')

                    <div class="space-y-4">
                        {{-- Vendor Name --}}
                        <div>
                            <label for="vendor_name" class="block text-sm font-medium text-gray-700 mb-1">
                                {{ trans('receipts::general.fields.vendor_name') }}
                            </label>
                            <input type="text" name="vendor_name" id="vendor_name"
                                   value="{{ old('vendor_name', $receipt->vendor_name) }}"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>

                        {{-- Receipt Date --}}
                        <div>
                            <label for="receipt_date" class="block text-sm font-medium text-gray-700 mb-1">
                                {{ trans('receipts::general.fields.receipt_date') }}
                            </label>
                            <input type="date" name="receipt_date" id="receipt_date"
                                   value="{{ old('receipt_date', $receipt->receipt_date?->format('Y-m-d')) }}"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>

                        {{-- Amount --}}
                        <div>
                            <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">
                                {{ trans('receipts::general.fields.amount') }}
                            </label>
                            <input type="number" name="amount" id="amount" step="0.01" min="0"
                                   value="{{ old('amount', $receipt->amount) }}"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>

                        {{-- Tax Amount --}}
                        <div>
                            <label for="tax_amount" class="block text-sm font-medium text-gray-700 mb-1">
                                {{ trans('receipts::general.fields.tax_amount') }}
                            </label>
                            <input type="number" name="tax_amount" id="tax_amount" step="0.01" min="0"
                                   value="{{ old('tax_amount', $receipt->tax_amount) }}"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>

                        {{-- Currency --}}
                        <div>
                            <label for="currency" class="block text-sm font-medium text-gray-700 mb-1">
                                {{ trans('receipts::general.fields.currency') }}
                            </label>
                            <input type="text" name="currency" id="currency" maxlength="3"
                                   value="{{ old('currency', $receipt->currency) }}"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500"
                                   placeholder="USD">
                        </div>

                        {{-- Category --}}
                        <div>
                            <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">
                                {{ trans('receipts::general.fields.category') }}
                            </label>
                            <select name="category_id" id="category_id"
                                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <option value="">-- {{ trans('general.form.select.field', ['field' => trans('receipts::general.fields.category')]) }} --</option>
                                @foreach($categories as $catId => $catName)
                                    <option value="{{ $catId }}" {{ old('category_id', $receipt->category_id) == $catId ? 'selected' : '' }}>
                                        {{ $catName }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Notes --}}
                        <div>
                            <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">
                                {{ trans('receipts::general.fields.notes') }}
                            </label>
                            <textarea name="notes" id="notes" rows="3"
                                      class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">{{ old('notes', $receipt->notes) }}</textarea>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="mt-6 flex justify-between">
                        <a href="{{ route('receipts.receipts.index') }}"
                           class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                            {{ trans('general.cancel') }}
                        </a>
                        <div class="flex space-x-3">
                            <button type="submit"
                                    class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                                <span class="material-icons text-sm align-middle">save</span>
                                {{ trans('general.save') }}
                            </button>
                            @if(in_array($receipt->status, ['uploaded', 'reviewed']))
                                <a href="{{ route('receipts.receipts.process', $receipt->id) }}"
                                   class="px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600">
                                    <span class="material-icons text-sm align-middle">receipt</span>
                                    {{ trans('receipts::general.actions.process') }}
                                </a>
                            @endif
                        </div>
                    </div>
                </form>

                {{-- OCR Raw Data (collapsible) --}}
                @if($receipt->ocr_raw_json)
                    <details class="mt-6">
                        <summary class="cursor-pointer text-sm text-gray-500 hover:text-gray-700">
                            OCR Raw Data
                        </summary>
                        <pre class="mt-2 bg-gray-50 rounded-lg p-4 text-xs overflow-auto max-h-64">{{ json_encode($receipt->ocr_raw_json, JSON_PRETTY_PRINT) }}</pre>
                    </details>
                @endif
            </div>
        </div>
    </x-slot>
</x-layouts.admin>
