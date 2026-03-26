<x-layouts.admin>
    <x-slot name="title">
        {{ trans('general.title.new', ['type' => trans('estimates::general.estimate')]) }}
    </x-slot>

    <x-slot name="content">
        <x-form.container>
            <x-form id="estimate" method="POST" route="estimates.estimates.store">
                {{-- Estimate Details --}}
                <x-form.section>
                    <x-slot name="head">
                        <x-form.section.head title="{{ trans('estimates::general.estimate_details') }}" />
                    </x-slot>

                    <x-slot name="body">
                        <x-form.group.select name="contact_id" label="{{ trans('estimates::general.customer') }}" :options="$customers" />

                        <x-form.group.date name="issued_at" label="{{ trans('estimates::general.estimate_date') }}" value="{{ now()->format('Y-m-d') }}" />

                        <x-form.group.date name="due_at" label="{{ trans('estimates::general.expiry_date') }}" not-required value="{{ now()->addDays(30)->format('Y-m-d') }}" />

                        <x-form.group.select name="currency_code" label="{{ trans('estimates::general.currency') }}" :options="$currencies" :selected="$defaultCurrency?->code ?? setting('default.currency')" />

                        <x-form.group.select name="category_id" label="{{ trans('estimates::general.category') }}" :options="$categories" not-required />

                        <x-form.group.text name="title" label="{{ trans('estimates::general.title') }}" not-required />

                        <x-form.group.text name="subheading" label="{{ trans('estimates::general.subheading') }}" not-required />
                    </x-slot>
                </x-form.section>

                {{-- Line Items --}}
                <x-form.section>
                    <x-slot name="head">
                        <x-form.section.head title="{{ trans('estimates::general.line_items') }}" />
                    </x-slot>

                    <x-slot name="body">
                        <div class="col-span-6" id="line-items-container">
                            <table class="w-full mb-4">
                                <thead>
                                    <tr class="border-b">
                                        <th class="px-2 py-2 text-left text-sm font-medium text-gray-500">{{ trans('estimates::general.item_name') }}</th>
                                        <th class="px-2 py-2 text-left text-sm font-medium text-gray-500">{{ trans('estimates::general.description') }}</th>
                                        <th class="px-2 py-2 text-right text-sm font-medium text-gray-500 w-24">{{ trans('estimates::general.quantity') }}</th>
                                        <th class="px-2 py-2 text-right text-sm font-medium text-gray-500 w-28">{{ trans('estimates::general.price') }}</th>
                                        <th class="px-2 py-2 text-left text-sm font-medium text-gray-500 w-36">{{ trans('estimates::general.tax') }}</th>
                                        <th class="px-2 py-2 text-right text-sm font-medium text-gray-500 w-24">{{ trans('estimates::general.discount') }} %</th>
                                        <th class="px-2 py-2 w-10"></th>
                                    </tr>
                                </thead>
                                <tbody id="line-items-body">
                                    <tr class="line-item border-b" data-index="0">
                                        <td class="px-2 py-2">
                                            <input type="text" name="items[0][name]" required class="w-full rounded-lg border-gray-300 text-sm" />
                                        </td>
                                        <td class="px-2 py-2">
                                            <input type="text" name="items[0][description]" class="w-full rounded-lg border-gray-300 text-sm" />
                                        </td>
                                        <td class="px-2 py-2">
                                            <input type="number" name="items[0][quantity]" value="1" step="any" min="0.01" required class="w-full rounded-lg border-gray-300 text-sm text-right" />
                                        </td>
                                        <td class="px-2 py-2">
                                            <input type="number" name="items[0][price]" value="0" step="any" min="0" required class="w-full rounded-lg border-gray-300 text-sm text-right" />
                                        </td>
                                        <td class="px-2 py-2">
                                            <select name="items[0][tax_id]" class="w-full rounded-lg border-gray-300 text-sm">
                                                @foreach ($taxes as $id => $label)
                                                    <option value="{{ $id }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="px-2 py-2">
                                            <input type="number" name="items[0][discount_rate]" value="0" step="any" min="0" max="100" class="w-full rounded-lg border-gray-300 text-sm text-right" />
                                        </td>
                                        <td class="px-2 py-2">
                                            <button type="button" onclick="removeItem(this)" class="text-red-500 hover:text-red-700">
                                                <span class="material-icons text-sm">close</span>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <button type="button" onclick="addItem()" class="text-purple-600 hover:text-purple-800 text-sm font-medium">
                                + {{ trans('estimates::general.add_item') }}
                            </button>
                        </div>
                    </x-slot>
                </x-form.section>

                {{-- Discount --}}
                <x-form.section>
                    <x-slot name="head">
                        <x-form.section.head title="{{ trans('estimates::general.discount') }}" />
                    </x-slot>

                    <x-slot name="body">
                        <x-form.group.select name="discount_type" label="{{ trans('estimates::general.discount_type') }}" :options="['percentage' => 'Percentage', 'fixed' => 'Fixed Amount']" not-required />

                        <x-form.group.text name="discount_rate" label="{{ trans('estimates::general.discount_rate') }}" value="0" not-required />
                    </x-slot>
                </x-form.section>

                {{-- Notes --}}
                <x-form.section>
                    <x-slot name="head">
                        <x-form.section.head title="{{ trans('estimates::general.notes') }}" />
                    </x-slot>

                    <x-slot name="body">
                        <x-form.group.textarea name="notes" label="{{ trans('estimates::general.notes') }}" :value="$settings->default_terms" not-required />

                        <x-form.group.textarea name="footer" label="{{ trans('estimates::general.footer') }}" not-required />
                    </x-slot>
                </x-form.section>

                <x-form.section>
                    <x-slot name="foot">
                        <x-form.buttons cancel-route="estimates.estimates.index" />
                    </x-slot>
                </x-form.section>
            </x-form>
        </x-form.container>

        <script>
            let itemIndex = 1;

            function addItem() {
                const tbody = document.getElementById('line-items-body');
                const taxOptions = @json($taxes);

                let taxSelect = '';
                for (const [id, label] of Object.entries(taxOptions)) {
                    taxSelect += `<option value="${id}">${label}</option>`;
                }

                const row = document.createElement('tr');
                row.className = 'line-item border-b';
                row.dataset.index = itemIndex;
                row.innerHTML = `
                    <td class="px-2 py-2"><input type="text" name="items[${itemIndex}][name]" required class="w-full rounded-lg border-gray-300 text-sm" /></td>
                    <td class="px-2 py-2"><input type="text" name="items[${itemIndex}][description]" class="w-full rounded-lg border-gray-300 text-sm" /></td>
                    <td class="px-2 py-2"><input type="number" name="items[${itemIndex}][quantity]" value="1" step="any" min="0.01" required class="w-full rounded-lg border-gray-300 text-sm text-right" /></td>
                    <td class="px-2 py-2"><input type="number" name="items[${itemIndex}][price]" value="0" step="any" min="0" required class="w-full rounded-lg border-gray-300 text-sm text-right" /></td>
                    <td class="px-2 py-2"><select name="items[${itemIndex}][tax_id]" class="w-full rounded-lg border-gray-300 text-sm">${taxSelect}</select></td>
                    <td class="px-2 py-2"><input type="number" name="items[${itemIndex}][discount_rate]" value="0" step="any" min="0" max="100" class="w-full rounded-lg border-gray-300 text-sm text-right" /></td>
                    <td class="px-2 py-2"><button type="button" onclick="removeItem(this)" class="text-red-500 hover:text-red-700"><span class="material-icons text-sm">close</span></button></td>
                `;
                tbody.appendChild(row);
                itemIndex++;
            }

            function removeItem(btn) {
                const tbody = document.getElementById('line-items-body');
                if (tbody.querySelectorAll('.line-item').length > 1) {
                    btn.closest('tr').remove();
                }
            }
        </script>
    </x-slot>
</x-layouts.admin>
