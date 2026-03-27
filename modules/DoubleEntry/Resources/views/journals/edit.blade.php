<x-layouts.admin>
    <x-slot name="title">{{ trans('general.title.edit', ['type' => $journal->number]) }}</x-slot>

    <x-slot name="content">
        <form method="POST" action="{{ route('double-entry.journals.update', $journal->id) }}" id="journal-form" class="space-y-6 rounded-xl bg-white p-6 shadow-sm">
            @csrf
            @method('PATCH')

            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="number">{{ trans('double-entry::general.number') }} <span class="text-red-500">*</span></label>
                    <input type="text" name="number" id="number" value="{{ old('number', $journal->number) }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="date">{{ trans('double-entry::general.date') }} <span class="text-red-500">*</span></label>
                    <input type="date" name="date" id="date" value="{{ old('date', $journal->date->format('Y-m-d')) }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="status">{{ trans('double-entry::general.status') }}</label>
                    <select name="status" id="status" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="draft" {{ $journal->status === 'draft' ? 'selected' : '' }}>{{ trans('double-entry::general.statuses.draft') }}</option>
                        <option value="posted" {{ $journal->status === 'posted' ? 'selected' : '' }}>{{ trans('double-entry::general.statuses.posted') }}</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="description">{{ trans('double-entry::general.description') }}</label>
                    <input type="text" name="description" id="description" value="{{ old('description', $journal->description) }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="reference">{{ trans('double-entry::general.reference') }}</label>
                    <input type="text" name="reference" id="reference" value="{{ old('reference', $journal->reference) }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>

            {{-- Journal Lines --}}
            <div>
                <h3 class="mb-3 text-sm font-semibold text-gray-900">{{ trans('double-entry::general.journal_entries') }}</h3>

                @error('lines')
                    <p class="mb-2 text-sm text-red-600">{{ $message }}</p>
                @enderror

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm" id="lines-table">
                        <thead>
                            <tr class="border-b text-gray-500">
                                <th class="pb-2 pr-3">{{ trans('double-entry::general.account') }}</th>
                                <th class="pb-2 pr-3">{{ trans('double-entry::general.description') }}</th>
                                <th class="pb-2 pr-3 text-right">{{ trans('double-entry::general.debit') }}</th>
                                <th class="pb-2 pr-3 text-right">{{ trans('double-entry::general.credit') }}</th>
                                <th class="pb-2"></th>
                            </tr>
                        </thead>
                        <tbody id="lines-body">
                            @foreach($journal->lines as $i => $line)
                                <tr class="line-row border-b border-gray-100">
                                    <td class="py-2 pr-3">
                                        <select name="lines[{{ $i }}][account_id]" class="w-full rounded-lg border-gray-300 text-sm shadow-sm" required>
                                            <option value="">--</option>
                                            @foreach($accounts as $account)
                                                <option value="{{ $account->id }}" {{ $line->account_id == $account->id ? 'selected' : '' }}>{{ $account->code }} - {{ $account->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="py-2 pr-3">
                                        <input type="text" name="lines[{{ $i }}][description]" value="{{ $line->description }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm">
                                    </td>
                                    <td class="py-2 pr-3">
                                        <input type="number" name="lines[{{ $i }}][debit]" step="0.0001" min="0" value="{{ $line->debit }}" class="w-full rounded-lg border-gray-300 text-right text-sm shadow-sm debit-input">
                                    </td>
                                    <td class="py-2 pr-3">
                                        <input type="number" name="lines[{{ $i }}][credit]" step="0.0001" min="0" value="{{ $line->credit }}" class="w-full rounded-lg border-gray-300 text-right text-sm shadow-sm credit-input">
                                    </td>
                                    <td class="py-2">
                                        <button type="button" onclick="removeLine(this)" class="text-red-400 hover:text-red-600">
                                            <span class="material-icons text-lg">close</span>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t font-semibold">
                                <td colspan="2" class="py-2 pr-3 text-right">{{ trans('general.total') }}</td>
                                <td class="py-2 pr-3 text-right" id="total-debit">0.00</td>
                                <td class="py-2 pr-3 text-right" id="total-credit">0.00</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <button type="button" onclick="addLine()" class="mt-3 rounded-lg border border-dashed border-gray-300 px-4 py-2 text-sm text-gray-600 hover:border-blue-400 hover:text-blue-600">
                    + {{ trans('double-entry::general.add_line') }}
                </button>
            </div>

            <div class="flex justify-end gap-2">
                <a href="{{ route('double-entry.journals.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">{{ trans('general.cancel') }}</a>
                <button type="submit" class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">{{ trans('general.save') }}</button>
            </div>
        </form>

        <script>
            let lineIndex = {{ count($journal->lines) }};

            function addLine() {
                const tbody = document.getElementById('lines-body');
                const accountOptions = tbody.querySelector('select').innerHTML;
                const row = document.createElement('tr');
                row.className = 'line-row border-b border-gray-100';
                row.innerHTML = `
                    <td class="py-2 pr-3"><select name="lines[${lineIndex}][account_id]" class="w-full rounded-lg border-gray-300 text-sm shadow-sm" required>${accountOptions}</select></td>
                    <td class="py-2 pr-3"><input type="text" name="lines[${lineIndex}][description]" class="w-full rounded-lg border-gray-300 text-sm shadow-sm"></td>
                    <td class="py-2 pr-3"><input type="number" name="lines[${lineIndex}][debit]" step="0.0001" min="0" value="0" class="w-full rounded-lg border-gray-300 text-right text-sm shadow-sm debit-input"></td>
                    <td class="py-2 pr-3"><input type="number" name="lines[${lineIndex}][credit]" step="0.0001" min="0" value="0" class="w-full rounded-lg border-gray-300 text-right text-sm shadow-sm credit-input"></td>
                    <td class="py-2"><button type="button" onclick="removeLine(this)" class="text-red-400 hover:text-red-600"><span class="material-icons text-lg">close</span></button></td>
                `;
                tbody.appendChild(row);
                lineIndex++;
                updateTotals();
            }

            function removeLine(btn) {
                const rows = document.querySelectorAll('.line-row');
                if (rows.length <= 2) return;
                btn.closest('tr').remove();
                updateTotals();
            }

            function updateTotals() {
                let totalDebit = 0, totalCredit = 0;
                document.querySelectorAll('.debit-input').forEach(el => totalDebit += parseFloat(el.value) || 0);
                document.querySelectorAll('.credit-input').forEach(el => totalCredit += parseFloat(el.value) || 0);
                document.getElementById('total-debit').textContent = totalDebit.toFixed(2);
                document.getElementById('total-credit').textContent = totalCredit.toFixed(2);
            }

            document.addEventListener('input', function(e) {
                if (e.target.classList.contains('debit-input') || e.target.classList.contains('credit-input')) {
                    updateTotals();
                }
            });

            updateTotals();
        </script>
    </x-slot>
</x-layouts.admin>
