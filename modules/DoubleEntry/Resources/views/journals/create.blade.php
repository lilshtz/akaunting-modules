<x-layouts.admin>
    <x-slot name="title">
        {{ trans('general.title.new', ['type' => trans('double-entry::general.journal_entry')]) }}
    </x-slot>

    <x-slot name="content">
        <x-form.container>
            <form id="journal-form" method="POST" action="{{ route('double-entry.journals.store') }}">
                @csrf

                <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                    <h3 class="text-lg font-semibold mb-4">{{ trans('general.general') }}</h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('general.date') }} *</label>
                            <input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required
                                class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                            @error('date') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('double-entry::general.reference') }}</label>
                            <input type="text" name="reference" value="{{ old('reference') }}" maxlength="100"
                                class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('double-entry::general.basis') }}</label>
                            <select name="basis" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                                <option value="accrual" @selected(old('basis', 'accrual') === 'accrual')>{{ trans('double-entry::general.accrual') }}</option>
                                <option value="cash" @selected(old('basis') === 'cash')>{{ trans('double-entry::general.cash') }}</option>
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('general.description') }}</label>
                            <textarea name="description" rows="2" class="w-full rounded-md border-gray-300 shadow-sm text-sm">{{ old('description') }}</textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('general.status') }}</label>
                            <select name="status" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                                <option value="posted" @selected(old('status', 'posted') === 'posted')>{{ trans('double-entry::general.statuses.posted') }}</option>
                                <option value="draft" @selected(old('status') === 'draft')>{{ trans('double-entry::general.statuses.draft') }}</option>
                            </select>
                        </div>
                    </div>

                    {{-- Recurring Section --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('double-entry::general.recurring_frequency') }}</label>
                            <select name="recurring_frequency" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                                <option value="">{{ trans('general.none') }}</option>
                                <option value="weekly" @selected(old('recurring_frequency') === 'weekly')>{{ trans('double-entry::general.frequencies.weekly') }}</option>
                                <option value="monthly" @selected(old('recurring_frequency') === 'monthly')>{{ trans('double-entry::general.frequencies.monthly') }}</option>
                                <option value="quarterly" @selected(old('recurring_frequency') === 'quarterly')>{{ trans('double-entry::general.frequencies.quarterly') }}</option>
                                <option value="yearly" @selected(old('recurring_frequency') === 'yearly')>{{ trans('double-entry::general.frequencies.yearly') }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('double-entry::general.next_recurring_date') }}</label>
                            <input type="date" name="next_recurring_date" value="{{ old('next_recurring_date') }}"
                                class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                        </div>
                    </div>
                </div>

                {{-- Journal Lines --}}
                <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">{{ trans('double-entry::general.line_items') }}</h3>
                        <button type="button" onclick="addLine()" class="px-3 py-1 bg-purple-600 text-white text-sm rounded-md hover:bg-purple-700">
                            + {{ trans('double-entry::general.add_line') }}
                        </button>
                    </div>

                    @error('lines') <p class="text-red-600 text-sm mb-2">{{ $message }}</p> @enderror

                    <table class="w-full" id="lines-table">
                        <thead>
                            <tr class="border-b">
                                <th class="px-2 py-2 text-left text-sm font-medium text-gray-500">{{ trans('double-entry::general.account') }}</th>
                                <th class="px-2 py-2 text-right text-sm font-medium text-gray-500 w-36">{{ trans('double-entry::general.debit') }}</th>
                                <th class="px-2 py-2 text-right text-sm font-medium text-gray-500 w-36">{{ trans('double-entry::general.credit') }}</th>
                                <th class="px-2 py-2 text-left text-sm font-medium text-gray-500">{{ trans('general.description') }}</th>
                                <th class="px-2 py-2 w-10"></th>
                            </tr>
                        </thead>
                        <tbody id="lines-body">
                            {{-- Rows added via JS --}}
                        </tbody>
                        <tfoot>
                            <tr class="border-t font-semibold">
                                <td class="px-2 py-2 text-sm text-right">{{ trans('general.total') }}</td>
                                <td class="px-2 py-2 text-sm text-right" id="total-debit">0.00</td>
                                <td class="px-2 py-2 text-sm text-right" id="total-credit">0.00</td>
                                <td class="px-2 py-2 text-sm" id="balance-indicator"></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('double-entry.journals.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 text-sm rounded-md hover:bg-gray-300">
                        {{ trans('general.cancel') }}
                    </a>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-700">
                        {{ trans('general.save') }}
                    </button>
                </div>
            </form>
        </x-form.container>

        <script>
            const accounts = @json($accounts);
            let lineIndex = 0;

            function addLine(accountId = '', debit = '', credit = '', description = '') {
                const tbody = document.getElementById('lines-body');
                const idx = lineIndex++;

                let options = '<option value="">-- {{ trans("double-entry::general.select_account") }} --</option>';
                for (const [id, name] of Object.entries(accounts)) {
                    const selected = id == accountId ? 'selected' : '';
                    options += `<option value="${id}" ${selected}>${name}</option>`;
                }

                const row = document.createElement('tr');
                row.className = 'border-b';
                row.innerHTML = `
                    <td class="px-2 py-2">
                        <select name="lines[${idx}][account_id]" required class="w-full rounded-md border-gray-300 shadow-sm text-sm">${options}</select>
                    </td>
                    <td class="px-2 py-2">
                        <input type="number" name="lines[${idx}][debit]" value="${debit}" step="0.0001" min="0" required
                            class="w-full text-right rounded-md border-gray-300 shadow-sm text-sm" onchange="updateTotals()" oninput="updateTotals()">
                    </td>
                    <td class="px-2 py-2">
                        <input type="number" name="lines[${idx}][credit]" value="${credit}" step="0.0001" min="0" required
                            class="w-full text-right rounded-md border-gray-300 shadow-sm text-sm" onchange="updateTotals()" oninput="updateTotals()">
                    </td>
                    <td class="px-2 py-2">
                        <input type="text" name="lines[${idx}][description]" value="${description}"
                            class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                    </td>
                    <td class="px-2 py-2 text-center">
                        <button type="button" onclick="removeLine(this)" class="text-red-500 hover:text-red-700">&times;</button>
                    </td>
                `;

                tbody.appendChild(row);
                updateTotals();
            }

            function removeLine(btn) {
                btn.closest('tr').remove();
                updateTotals();
            }

            function updateTotals() {
                let totalDebit = 0, totalCredit = 0;
                document.querySelectorAll('#lines-body tr').forEach(row => {
                    const debitInput = row.querySelector('input[name*="[debit]"]');
                    const creditInput = row.querySelector('input[name*="[credit]"]');
                    totalDebit += parseFloat(debitInput?.value || 0);
                    totalCredit += parseFloat(creditInput?.value || 0);
                });
                document.getElementById('total-debit').textContent = totalDebit.toFixed(2);
                document.getElementById('total-credit').textContent = totalCredit.toFixed(2);

                const indicator = document.getElementById('balance-indicator');
                const diff = Math.abs(totalDebit - totalCredit);
                if (diff < 0.0001 && totalDebit > 0) {
                    indicator.innerHTML = '<span class="text-green-600 text-sm">&#10003; Balanced</span>';
                } else if (totalDebit > 0 || totalCredit > 0) {
                    indicator.innerHTML = `<span class="text-red-600 text-sm">&#10007; Difference: ${diff.toFixed(2)}</span>`;
                } else {
                    indicator.innerHTML = '';
                }
            }

            // Initialize with 2 empty lines
            addLine();
            addLine();
        </script>
    </x-slot>
</x-layouts.admin>
