<x-layouts.admin>
    <x-slot name="title">
        {{ trans('receipts::general.process') }} - {{ $receipt->vendor_name ?? trans('receipts::general.receipt') }} #{{ $receipt->id }}
    </x-slot>

    <x-slot name="content">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Receipt Summary --}}
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-semibold mb-4">{{ trans('receipts::general.receipt') }}</h2>

                @if($receipt->image_path)
                    <img src="{{ Storage::disk('public')->url($receipt->image_path) }}"
                         alt="{{ $receipt->vendor_name }}"
                         class="w-full rounded-lg shadow max-h-80 object-contain mb-4">
                @endif

                <dl class="space-y-2">
                    <div class="flex justify-between">
                        <dt class="text-gray-600">{{ trans('receipts::general.fields.vendor_name') }}</dt>
                        <dd class="font-medium">{{ $receipt->vendor_name ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-600">{{ trans('receipts::general.fields.receipt_date') }}</dt>
                        <dd class="font-medium">{{ $receipt->receipt_date ? $receipt->receipt_date->format('M d, Y') : '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-600">{{ trans('receipts::general.fields.amount') }}</dt>
                        <dd class="font-bold text-lg">{{ $receipt->formatted_amount }}</dd>
                    </div>
                    @if($receipt->tax_amount)
                        <div class="flex justify-between">
                            <dt class="text-gray-600">{{ trans('receipts::general.fields.tax_amount') }}</dt>
                            <dd class="font-medium">{{ money($receipt->tax_amount, $receipt->currency ?? setting('default.currency', 'USD')) }}</dd>
                        </div>
                    @endif
                    @if($receipt->category)
                        <div class="flex justify-between">
                            <dt class="text-gray-600">{{ trans('receipts::general.fields.category') }}</dt>
                            <dd class="font-medium">{{ $receipt->category->name }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            {{-- Process Form --}}
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-semibold mb-4">{{ trans('receipts::general.actions.process') }}</h2>
                <p class="text-gray-600 mb-4">{{ trans('receipts::general.messages.select_entity_type') }}</p>

                <form method="POST" action="{{ route('receipts.receipts.process.store', $receipt->id) }}">
                    @csrf

                    <div class="space-y-4">
                        {{-- Entity Type --}}
                        <div>
                            <label for="entity_type" class="block text-sm font-medium text-gray-700 mb-1">
                                {{ trans('receipts::general.fields.entity_type') }}
                            </label>
                            <select name="entity_type" id="entity_type"
                                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <option value="bill">{{ trans('receipts::general.entity_types.bill') }}</option>
                                <option value="payment">{{ trans('receipts::general.entity_types.payment') }}</option>
                            </select>
                        </div>

                        {{-- Vendor/Contact --}}
                        <div>
                            <label for="contact_id" class="block text-sm font-medium text-gray-700 mb-1">
                                {{ trans('receipts::general.fields.contact') }}
                            </label>
                            <select name="contact_id" id="contact_id"
                                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <option value="">-- Auto-create from "{{ $receipt->vendor_name }}" --</option>
                                @foreach($contacts as $contactId => $contactName)
                                    <option value="{{ $contactId }}">{{ $contactName }}</option>
                                @endforeach
                            </select>
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
                                    <option value="{{ $catId }}" {{ $receipt->category_id == $catId ? 'selected' : '' }}>
                                        {{ $catName }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Account (for payments) --}}
                        <div id="account-field">
                            <label for="account_id" class="block text-sm font-medium text-gray-700 mb-1">
                                {{ trans('receipts::general.fields.account') }}
                            </label>
                            <select name="account_id" id="account_id"
                                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <option value="">-- Default Account --</option>
                                @foreach($accounts as $accId => $accName)
                                    <option value="{{ $accId }}">{{ $accName }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="mt-6 flex justify-between">
                        <a href="{{ route('receipts.receipts.review', $receipt->id) }}"
                           class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                            {{ trans('general.cancel') }}
                        </a>
                        <button type="submit"
                                class="px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600">
                            <span class="material-icons text-sm align-middle">receipt</span>
                            {{ trans('receipts::general.actions.process') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const entityType = document.getElementById('entity_type');
                const accountField = document.getElementById('account-field');

                entityType.addEventListener('change', function() {
                    accountField.style.display = this.value === 'payment' ? 'block' : 'none';
                });

                // Initial state
                accountField.style.display = entityType.value === 'payment' ? 'block' : 'none';
            });
        </script>
    </x-slot>
</x-layouts.admin>
