<x-layouts.admin>
    <x-slot name="title">
        {{ trans('receipts::general.name') }} - {{ trans('receipts::general.settings') }}
    </x-slot>

    <x-slot name="content">
        <div class="max-w-4xl mx-auto space-y-6">
            {{-- OCR Settings --}}
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-semibold mb-4">OCR {{ trans('receipts::general.settings') }}</h2>

                <form method="POST" action="{{ route('receipts.settings.update') }}">
                    @csrf

                    <div class="space-y-4">
                        <div>
                            <label for="ocr_provider" class="block text-sm font-medium text-gray-700 mb-1">
                                {{ trans('receipts::general.fields.ocr_provider') }}
                            </label>
                            <select name="ocr_provider" id="ocr_provider"
                                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                                @foreach(['tesseract', 'taggun', 'mindee'] as $provider)
                                    <option value="{{ $provider }}" {{ $ocrProvider === $provider ? 'selected' : '' }}>
                                        {{ trans('receipts::general.ocr_providers.' . $provider) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div id="api-key-field" class="{{ $ocrProvider === 'tesseract' ? 'hidden' : '' }}">
                            <label for="ocr_api_key" class="block text-sm font-medium text-gray-700 mb-1">
                                {{ trans('receipts::general.fields.api_key') }}
                            </label>
                            <input type="password" name="ocr_api_key" id="ocr_api_key"
                                   value=""
                                   placeholder="{{ $ocrApiKey ? '********' : 'Enter API key' }}"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                            <p class="text-sm text-gray-500 mt-1">API key is encrypted at rest.</p>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="px-6 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600">
                            {{ trans('general.save') }}
                        </button>
                    </div>
                </form>

                <script>
                    document.getElementById('ocr_provider').addEventListener('change', function() {
                        document.getElementById('api-key-field').classList.toggle('hidden', this.value === 'tesseract');
                    });
                </script>
            </div>

            {{-- Auto-Categorization Rules --}}
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-semibold mb-4">Auto-Categorization Rules</h2>
                <p class="text-gray-600 mb-4">
                    Define rules to automatically assign categories based on vendor name patterns.
                    Use * as wildcard (e.g., "*amazon*" matches any vendor containing "amazon").
                </p>

                {{-- Existing Rules --}}
                @if($rules->isNotEmpty())
                    <table class="w-full mb-6">
                        <thead>
                            <tr class="border-b text-left">
                                <th class="pb-2">{{ trans('receipts::general.fields.vendor_pattern') }}</th>
                                <th class="pb-2">{{ trans('receipts::general.fields.category') }}</th>
                                <th class="pb-2">{{ trans('receipts::general.fields.account') }}</th>
                                <th class="pb-2">{{ trans('receipts::general.fields.priority') }}</th>
                                <th class="pb-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rules as $rule)
                                <tr class="border-b">
                                    <td class="py-2 font-mono text-sm">{{ $rule->vendor_pattern }}</td>
                                    <td class="py-2">{{ $categories[$rule->category_id] ?? '-' }}</td>
                                    <td class="py-2">{{ $accounts[$rule->account_id] ?? '-' }}</td>
                                    <td class="py-2">{{ $rule->priority }}</td>
                                    <td class="py-2 text-right">
                                        <form method="POST" action="{{ route('receipts.settings.rules.destroy', $rule->id) }}"
                                              onsubmit="return confirm('Delete this rule?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-500 hover:text-red-700">
                                                <span class="material-icons text-sm">delete</span>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                {{-- Add New Rule --}}
                <form method="POST" action="{{ route('receipts.settings.rules.store') }}">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                {{ trans('receipts::general.fields.vendor_pattern') }}
                            </label>
                            <input type="text" name="vendor_pattern" required
                                   placeholder="e.g., *Home Depot*"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                {{ trans('receipts::general.fields.category') }}
                            </label>
                            <select name="category_id" required
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <option value="">--</option>
                                @foreach($categories as $catId => $catName)
                                    <option value="{{ $catId }}">{{ $catName }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                {{ trans('receipts::general.fields.account') }}
                            </label>
                            <select name="account_id"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <option value="">--</option>
                                @foreach($accounts as $accId => $accName)
                                    <option value="{{ $accId }}">{{ $accName }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                {{ trans('receipts::general.fields.priority') }}
                            </label>
                            <input type="number" name="priority" value="0" min="0"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>
                        <div>
                            <button type="submit" class="w-full px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 text-sm">
                                Add Rule
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </x-slot>
</x-layouts.admin>
