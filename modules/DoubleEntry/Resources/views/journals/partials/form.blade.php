@php
    $journal = $journal ?? null;
    $lineItems = collect($lineItems ?? [])->map(function ($line) {
        return [
            'account_id' => $line['account_id'] ?? null,
            'debit' => $line['debit'] ?? null,
            'credit' => $line['credit'] ?? null,
            'description' => $line['description'] ?? null,
        ];
    })->values()->all();

    if (count($lineItems) < 2) {
        $lineItems[] = ['account_id' => null, 'debit' => null, 'credit' => null, 'description' => null];
        $lineItems[] = ['account_id' => null, 'debit' => null, 'credit' => null, 'description' => null];
    }
@endphp

<x-form.section>
    <x-slot name="head">
        <x-form.section.head title="{{ trans('double-entry::general.journal_entry') }}" />
    </x-slot>

    <x-slot name="body">
        <div class="grid gap-6 md:grid-cols-2">
            <x-form.group.date
                name="date"
                label="{{ trans('double-entry::general.date') }}"
                :value="old('date', optional($journal?->date)->format('Y-m-d') ?? now()->format('Y-m-d'))"
            />

            <x-form.group.text
                name="reference"
                label="{{ trans('double-entry::general.reference') }}"
                :value="old('reference', $journal->reference ?? $suggestedReference)"
                not-required
            />

            <x-form.group.select
                name="basis"
                label="{{ trans('double-entry::general.basis') }}"
                :options="['accrual' => trans('double-entry::general.accrual'), 'cash' => trans('double-entry::general.cash')]"
                :selected="old('basis', $journal->basis ?? 'accrual')"
            />

            <x-form.group.textarea
                name="description"
                label="{{ trans('general.description') }}"
                :value="old('description', $journal->description ?? '')"
                not-required
            />
        </div>
    </x-slot>
</x-form.section>

<x-form.section>
    <x-slot name="body">
        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="space-y-4">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-lg font-semibold text-gray-900">{{ trans('double-entry::general.line_items') }}</h2>

                <button type="button" id="add-line" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    {{ trans('double-entry::general.add_line') }}
                </button>
            </div>

            <div class="overflow-x-auto rounded-xl border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200" id="journal-lines-table">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ trans('double-entry::general.account_name') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ trans('double-entry::general.debit') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ trans('double-entry::general.credit') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ trans('general.description') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">{{ trans('general.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody id="journal-lines-body" class="divide-y divide-gray-200 bg-white">
                        @foreach ($lineItems as $index => $line)
                            <tr>
                                <td class="px-4 py-3">
                                    <select name="lines[{{ $index }}][account_id]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                        <option value="">{{ trans('general.select') }}</option>
                                        @foreach ($accountOptions as $accountId => $label)
                                            <option value="{{ $accountId }}" @selected((string) ($line['account_id'] ?? '') === (string) $accountId)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error("lines.$index.account_id")
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </td>
                                <td class="px-4 py-3">
                                    <input type="number" step="0.0001" min="0" name="lines[{{ $index }}][debit]" value="{{ $line['debit'] !== null ? number_format((float) $line['debit'], 4, '.', '') : '' }}" class="line-debit w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
                                    @error("lines.$index.debit")
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </td>
                                <td class="px-4 py-3">
                                    <input type="number" step="0.0001" min="0" name="lines[{{ $index }}][credit]" value="{{ $line['credit'] !== null ? number_format((float) $line['credit'], 4, '.', '') : '' }}" class="line-credit w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
                                    @error("lines.$index.credit")
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </td>
                                <td class="px-4 py-3">
                                    <input type="text" name="lines[{{ $index }}][description]" value="{{ $line['description'] }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button type="button" class="remove-line text-sm font-medium text-red-600 hover:text-red-800">
                                        {{ trans('double-entry::general.remove_line') }}
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @error('lines')
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror

            <div class="grid gap-4 md:grid-cols-4">
                <div class="rounded-xl bg-gray-50 px-4 py-3">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ trans('double-entry::general.total_debits') }}</div>
                    <div id="total-debits" class="mt-2 text-lg font-semibold text-gray-900">0.0000</div>
                </div>
                <div class="rounded-xl bg-gray-50 px-4 py-3">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ trans('double-entry::general.total_credits') }}</div>
                    <div id="total-credits" class="mt-2 text-lg font-semibold text-gray-900">0.0000</div>
                </div>
                <div class="rounded-xl bg-gray-50 px-4 py-3">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ trans('double-entry::general.difference') }}</div>
                    <div id="difference" class="mt-2 text-lg font-semibold text-gray-900">0.0000</div>
                </div>
                <div id="balance-indicator" class="rounded-xl border px-4 py-3">
                    <div class="text-xs font-semibold uppercase tracking-wide">{{ trans('double-entry::general.status') }}</div>
                    <div id="balance-status" class="mt-2 text-lg font-semibold">{{ trans('double-entry::general.unbalanced') }}</div>
                </div>
            </div>
        </div>
    </x-slot>

    <x-slot name="foot">
        <div class="flex flex-wrap items-center justify-end gap-3">
            <a href="{{ route('double-entry.journals.index') }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                {{ trans('general.cancel') }}
            </a>

            <button type="submit" name="status" value="draft" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                {{ trans('double-entry::general.save_as_draft') }}
            </button>

            <button type="submit" name="status" value="posted" class="inline-flex items-center rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
                {{ trans('double-entry::general.save_and_post') }}
            </button>
        </div>
    </x-slot>
</x-form.section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const lineBody = document.getElementById('journal-lines-body');
        const addLineButton = document.getElementById('add-line');
        const accountOptions = @json($accountOptions);
        let lineIndex = lineBody.querySelectorAll('tr').length;

        function renderAccountOptions(selectedValue = '') {
            const placeholder = `<option value="">{{ trans('general.select') }}</option>`;
            const options = Object.entries(accountOptions).map(([value, label]) => {
                const selected = String(selectedValue) === String(value) ? 'selected' : '';

                return `<option value="${value}" ${selected}>${label}</option>`;
            });

            return placeholder + options.join('');
        }

        function createRow() {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="px-4 py-3">
                    <select name="lines[${lineIndex}][account_id]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        ${renderAccountOptions()}
                    </select>
                </td>
                <td class="px-4 py-3">
                    <input type="number" step="0.0001" min="0" name="lines[${lineIndex}][debit]" class="line-debit w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
                </td>
                <td class="px-4 py-3">
                    <input type="number" step="0.0001" min="0" name="lines[${lineIndex}][credit]" class="line-credit w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
                </td>
                <td class="px-4 py-3">
                    <input type="text" name="lines[${lineIndex}][description]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
                </td>
                <td class="px-4 py-3 text-right">
                    <button type="button" class="remove-line text-sm font-medium text-red-600 hover:text-red-800">
                        {{ trans('double-entry::general.remove_line') }}
                    </button>
                </td>
            `;

            lineIndex += 1;
            lineBody.appendChild(row);
            syncTotals();
        }

        function syncTotals() {
            const debitInputs = lineBody.querySelectorAll('.line-debit');
            const creditInputs = lineBody.querySelectorAll('.line-credit');
            const totalDebits = Array.from(debitInputs).reduce((sum, input) => sum + (parseFloat(input.value) || 0), 0);
            const totalCredits = Array.from(creditInputs).reduce((sum, input) => sum + (parseFloat(input.value) || 0), 0);
            const difference = totalDebits - totalCredits;
            const balanced = Math.abs(difference) < 0.0001;

            document.getElementById('total-debits').textContent = totalDebits.toFixed(4);
            document.getElementById('total-credits').textContent = totalCredits.toFixed(4);
            document.getElementById('difference').textContent = difference.toFixed(4);

            const indicator = document.getElementById('balance-indicator');
            const status = document.getElementById('balance-status');

            indicator.className = balanced
                ? 'rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-green-700'
                : 'rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-700';

            status.textContent = balanced
                ? '{{ trans('double-entry::general.balanced') }}'
                : '{{ trans('double-entry::general.unbalanced') }}';
        }

        addLineButton.addEventListener('click', createRow);

        lineBody.addEventListener('click', function (event) {
            if (!event.target.classList.contains('remove-line')) {
                return;
            }

            event.target.closest('tr').remove();
            syncTotals();
        });

        lineBody.addEventListener('input', function (event) {
            if (event.target.classList.contains('line-debit') || event.target.classList.contains('line-credit')) {
                syncTotals();
            }
        });

        syncTotals();
    });
</script>
