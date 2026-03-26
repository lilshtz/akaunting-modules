<x-layouts.admin>
    <x-slot name="title">
        {{ trans('general.title.edit', ['type' => trans('credit-debit-notes::general.credit_note')]) }}
    </x-slot>

    <x-slot name="content">
        <x-form.container>
            <x-form id="credit-note" method="PATCH" :route="['credit-debit-notes.credit-notes.update', $creditNote->id]">
                {{-- Note Details --}}
                <x-form.section>
                    <x-slot name="head">
                        <x-form.section.head title="{{ trans('credit-debit-notes::general.credit_note') }}" />
                    </x-slot>

                    <x-slot name="body">
                        <x-form.group.select name="parent_id" label="{{ trans('credit-debit-notes::general.linked_invoice') }}" :options="$invoices" :selected="$creditNote->parent_id" />

                        <x-form.group.date name="issued_at" label="{{ trans('credit-debit-notes::general.note_date') }}" value="{{ $creditNote->issued_at?->format('Y-m-d') }}" />

                        <x-form.group.date name="due_at" label="{{ trans('credit-debit-notes::general.due_date') }}" not-required value="{{ $creditNote->due_at?->format('Y-m-d') }}" />

                        <x-form.group.select name="currency_code" label="{{ trans('credit-debit-notes::general.currency') }}" :options="$currencies" :selected="$creditNote->currency_code" />
                    </x-slot>
                </x-form.section>

                {{-- Line Items --}}
                <x-form.section>
                    <x-slot name="head">
                        <x-form.section.head title="{{ trans('credit-debit-notes::general.line_items') }}" />
                    </x-slot>

                    <x-slot name="body">
                        <div class="col-span-6" id="line-items-container">
                            <table class="w-full mb-4">
                                <thead>
                                    <tr class="border-b">
                                        <th class="px-2 py-2 text-left text-sm font-medium text-gray-500">{{ trans('credit-debit-notes::general.item_name') }}</th>
                                        <th class="px-2 py-2 text-left text-sm font-medium text-gray-500">{{ trans('credit-debit-notes::general.description') }}</th>
                                        <th class="px-2 py-2 text-right text-sm font-medium text-gray-500 w-24">{{ trans('credit-debit-notes::general.quantity') }}</th>
                                        <th class="px-2 py-2 text-right text-sm font-medium text-gray-500 w-28">{{ trans('credit-debit-notes::general.price') }}</th>
                                        <th class="px-2 py-2 text-left text-sm font-medium text-gray-500 w-36">{{ trans('credit-debit-notes::general.tax') }}</th>
                                        <th class="px-2 py-2 text-right text-sm font-medium text-gray-500 w-24">{{ trans('credit-debit-notes::general.discount') }} %</th>
                                        <th class="px-2 py-2 w-10"></th>
                                    </tr>
                                </thead>
                                <tbody id="line-items-body">
                                    @foreach ($creditNote->items as $index => $item)
                                        <tr class="line-item border-b" data-index="{{ $index }}">
                                            <td class="px-2 py-2"><input type="text" name="items[{{ $index }}][name]" value="{{ $item->name }}" required class="w-full rounded-lg border-gray-300 text-sm" /></td>
                                            <td class="px-2 py-2"><input type="text" name="items[{{ $index }}][description]" value="{{ $item->description }}" class="w-full rounded-lg border-gray-300 text-sm" /></td>
                                            <td class="px-2 py-2"><input type="number" name="items[{{ $index }}][quantity]" value="{{ $item->quantity }}" step="any" min="0.01" required class="w-full rounded-lg border-gray-300 text-sm text-right" /></td>
                                            <td class="px-2 py-2"><input type="number" name="items[{{ $index }}][price]" value="{{ $item->price }}" step="any" min="0" required class="w-full rounded-lg border-gray-300 text-sm text-right" /></td>
                                            <td class="px-2 py-2">
                                                <select name="items[{{ $index }}][tax_id]" class="w-full rounded-lg border-gray-300 text-sm">
                                                    @foreach ($taxes as $id => $label)
                                                        <option value="{{ $id }}" {{ optional($item->taxes->first())->tax_id == $id ? 'selected' : '' }}>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="px-2 py-2"><input type="number" name="items[{{ $index }}][discount_rate]" value="{{ $item->discount_rate }}" step="any" min="0" max="100" class="w-full rounded-lg border-gray-300 text-sm text-right" /></td>
                                            <td class="px-2 py-2">
                                                <button type="button" onclick="removeItem(this)" class="text-red-500 hover:text-red-700">
                                                    <span class="material-icons text-sm">close</span>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <button type="button" onclick="addItem()" class="text-purple-600 hover:text-purple-800 text-sm font-medium">
                                + {{ trans('credit-debit-notes::general.add_item') }}
                            </button>
                        </div>
                    </x-slot>
                </x-form.section>

                {{-- Discount --}}
                <x-form.section>
                    <x-slot name="head">
                        <x-form.section.head title="{{ trans('credit-debit-notes::general.discount') }}" />
                    </x-slot>

                    <x-slot name="body">
                        <x-form.group.select name="discount_type" label="{{ trans('credit-debit-notes::general.discount_type') }}" :options="['percentage' => 'Percentage', 'fixed' => 'Fixed Amount']" :selected="$creditNote->discount_type" not-required />
                        <x-form.group.text name="discount_rate" label="{{ trans('credit-debit-notes::general.discount_rate') }}" value="{{ $creditNote->discount_rate }}" not-required />
                    </x-slot>
                </x-form.section>

                {{-- Notes --}}
                <x-form.section>
                    <x-slot name="head">
                        <x-form.section.head title="{{ trans('credit-debit-notes::general.notes') }}" />
                    </x-slot>

                    <x-slot name="body">
                        <x-form.group.textarea name="notes" label="{{ trans('credit-debit-notes::general.reason') }}" :value="$creditNote->notes" not-required />
                        <x-form.group.textarea name="footer" label="{{ trans('credit-debit-notes::general.footer') }}" :value="$creditNote->footer" not-required />
                    </x-slot>
                </x-form.section>

                <x-form.section>
                    <x-slot name="foot">
                        <x-form.buttons cancel-route="credit-debit-notes.credit-notes.index" />
                    </x-slot>
                </x-form.section>
            </x-form>
        </x-form.container>

        <script>
            let itemIndex = {{ count($creditNote->items) }};

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
