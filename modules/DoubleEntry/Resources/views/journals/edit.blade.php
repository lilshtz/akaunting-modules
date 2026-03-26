<x-layouts.admin>
    <x-slot name="title">
        {{ trans('general.title.edit', ['type' => trans('double-entry::general.journal_entry')]) }}
    </x-slot>

    <x-slot name="content">
        <x-form.container>
            <form id="journal-form" method="POST" action="{{ route('double-entry.journals.update', $journal->id) }}">
                @csrf
                @method('PATCH')

                <x-form.section>
                    <x-slot name="head">
                        <x-form.section.head title="{{ trans('general.general') }}" />
                    </x-slot>

                    <x-slot name="body">
                        <x-form.group.date name="date" label="{{ trans('general.date') }}" value="{{ old('date', $journal->date->format('Y-m-d')) }}" />

                        <x-form.group.text name="reference" label="{{ trans('double-entry::general.reference') }}" value="{{ old('reference', $journal->reference) }}" not-required />

                        <x-form.group.textarea name="description" label="{{ trans('general.description') }}" value="{{ old('description', $journal->description) }}" not-required />

                        <x-form.group.select name="basis" label="{{ trans('double-entry::general.basis') }}" :options="['accrual' => trans('double-entry::general.bases.accrual'), 'cash' => trans('double-entry::general.bases.cash')]" :selected="old('basis', $journal->basis)" />

                        <x-form.group.select name="status" label="{{ trans('general.status') }}" :options="['posted' => trans('double-entry::general.statuses.posted'), 'draft' => trans('double-entry::general.statuses.draft')]" :selected="old('status', $journal->status)" />

                        <x-form.group.select name="recurring_frequency" label="{{ trans('double-entry::general.recurring_frequency') }}" :options="['' => trans('general.none'), 'weekly' => trans('double-entry::general.frequencies.weekly'), 'monthly' => trans('double-entry::general.frequencies.monthly'), 'quarterly' => trans('double-entry::general.frequencies.quarterly'), 'yearly' => trans('double-entry::general.frequencies.yearly')]" :selected="old('recurring_frequency', $journal->recurring_frequency ?? '')" not-required />

                        <x-form.group.date name="next_recurring_date" label="{{ trans('double-entry::general.next_recurring_date') }}" value="{{ old('next_recurring_date', $journal->next_recurring_date?->format('Y-m-d')) }}" not-required />
                    </x-slot>
                </x-form.section>

                <x-form.section>
                    <x-slot name="head">
                        <x-form.section.head title="{{ trans('double-entry::general.journal_lines') }}" />
                    </x-slot>

                    <x-slot name="body">
                        <div class="w-full" id="journal-lines">
                            <table class="w-full mb-4">
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
                                    @foreach ($journal->lines as $i => $line)
                                        <tr class="line-row border-b">
                                            <td class="px-2 py-2">
                                                <select name="lines[{{ $i }}][account_id]" class="w-full border rounded px-2 py-1.5 text-sm" required>
                                                    <option value="">--</option>
                                                    @foreach ($accounts as $id => $name)
                                                        <option value="{{ $id }}" {{ $line->account_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="px-2 py-2"><input type="number" name="lines[{{ $i }}][debit]" step="0.01" min="0" value="{{ $line->debit }}" class="w-full border rounded px-2 py-1.5 text-sm text-right debit-input" onchange="updateTotals()"></td>
                                            <td class="px-2 py-2"><input type="number" name="lines[{{ $i }}][credit]" step="0.01" min="0" value="{{ $line->credit }}" class="w-full border rounded px-2 py-1.5 text-sm text-right credit-input" onchange="updateTotals()"></td>
                                            <td class="px-2 py-2"><input type="text" name="lines[{{ $i }}][description]" value="{{ $line->description }}" class="w-full border rounded px-2 py-1.5 text-sm"></td>
                                            <td class="px-2 py-2">
                                                @if ($i >= 2)
                                                    <button type="button" onclick="removeLine(this)" class="text-red-500 hover:text-red-700 text-sm">&times;</button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="border-t-2 font-semibold">
                                        <td class="px-2 py-2 text-sm text-right">{{ trans('general.total') }}</td>
                                        <td class="px-2 py-2 text-sm text-right" id="total-debit">0.00</td>
                                        <td class="px-2 py-2 text-sm text-right" id="total-credit">0.00</td>
                                        <td class="px-2 py-2 text-sm" id="balance-status"></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>

                            <button type="button" onclick="addLine()" class="text-purple-700 hover:text-purple-900 text-sm font-medium">
                                + {{ trans('double-entry::general.add_line') }}
                            </button>
                        </div>
                    </x-slot>
                </x-form.section>

                <x-form.section>
                    <x-slot name="foot">
                        <x-form.buttons cancel-route="double-entry.journals.index" />
                    </x-slot>
                </x-form.section>
            </form>
        </x-form.container>

        <script>
            let lineIndex = {{ count($journal->lines) }};

            function addLine() {
                const tbody = document.getElementById('lines-body');
                const accountOptions = document.querySelector('select[name="lines[0][account_id]"]').innerHTML;
                const tr = document.createElement('tr');
                tr.className = 'line-row border-b';
                tr.innerHTML = `
                    <td class="px-2 py-2">
                        <select name="lines[${lineIndex}][account_id]" class="w-full border rounded px-2 py-1.5 text-sm" required>
                            ${accountOptions}
                        </select>
                    </td>
                    <td class="px-2 py-2"><input type="number" name="lines[${lineIndex}][debit]" step="0.01" min="0" value="0" class="w-full border rounded px-2 py-1.5 text-sm text-right debit-input" onchange="updateTotals()"></td>
                    <td class="px-2 py-2"><input type="number" name="lines[${lineIndex}][credit]" step="0.01" min="0" value="0" class="w-full border rounded px-2 py-1.5 text-sm text-right credit-input" onchange="updateTotals()"></td>
                    <td class="px-2 py-2"><input type="text" name="lines[${lineIndex}][description]" class="w-full border rounded px-2 py-1.5 text-sm"></td>
                    <td class="px-2 py-2"><button type="button" onclick="removeLine(this)" class="text-red-500 hover:text-red-700 text-sm">&times;</button></td>
                `;
                tbody.appendChild(tr);
                lineIndex++;
            }

            function removeLine(btn) {
                const rows = document.querySelectorAll('.line-row');
                if (rows.length > 2) {
                    btn.closest('tr').remove();
                    updateTotals();
                }
            }

            function updateTotals() {
                let totalDebit = 0, totalCredit = 0;
                document.querySelectorAll('.debit-input').forEach(el => totalDebit += parseFloat(el.value) || 0);
                document.querySelectorAll('.credit-input').forEach(el => totalCredit += parseFloat(el.value) || 0);
                document.getElementById('total-debit').textContent = totalDebit.toFixed(2);
                document.getElementById('total-credit').textContent = totalCredit.toFixed(2);

                const status = document.getElementById('balance-status');
                const diff = Math.abs(totalDebit - totalCredit);
                if (diff < 0.005) {
                    status.innerHTML = '<span class="text-green-600">Balanced</span>';
                } else {
                    status.innerHTML = '<span class="text-red-600">Difference: ' + diff.toFixed(2) + '</span>';
                }
            }

            updateTotals();
        </script>
    </x-slot>
</x-layouts.admin>
