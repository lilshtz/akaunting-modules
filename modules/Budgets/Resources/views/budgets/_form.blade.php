@php
    $lines = old('lines', $lineItems ?? []);

    if (empty($lines)) {
        $lines = [['account_id' => '', 'amount' => '']];
    }
@endphp

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">{{ trans('general.name') }}</label>
        <input type="text" name="name" value="{{ old('name', $budget->name) }}" class="form-control" required>
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">{{ trans('budgets::general.period_type') }}</label>
        <select name="period_type" class="form-control" required>
            @foreach($periodTypes as $value => $label)
                <option value="{{ $value }}" @selected(old('period_type', $budget->period_type) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">{{ trans('budgets::general.scenario') }}</label>
        <select name="scenario" class="form-control">
            <option value="">{{ trans('general.na') }}</option>
            @foreach($scenarios as $value => $label)
                <option value="{{ $value }}" @selected(old('scenario', $budget->scenario) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="row">
    <div class="col-md-3 mb-3">
        <label class="form-label">{{ trans('budgets::general.period_start') }}</label>
        <input type="date" name="period_start" value="{{ old('period_start', optional($budget->period_start)->toDateString() ?: $budget->period_start) }}" class="form-control" required>
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">{{ trans('budgets::general.period_end') }}</label>
        <input type="date" name="period_end" value="{{ old('period_end', optional($budget->period_end)->toDateString() ?: $budget->period_end) }}" class="form-control" required>
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">{{ trans('budgets::general.status') }}</label>
        <select name="status" class="form-control" required>
            @foreach($statuses as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $budget->status) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">{{ trans('budgets::general.copy_previous') }}</label>
        <select id="copy_budget_id" class="form-control">
            <option value="">{{ trans('general.na') }}</option>
            @foreach($copyOptions as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="mb-0">{{ trans('budgets::general.line_items') }}</h3>
        <button type="button" class="btn btn-outline-primary btn-sm" id="add-budget-line">{{ trans('budgets::general.add_line') }}</button>
    </div>
    <div class="card-body">
        <table class="table table-sm" id="budget-lines-table">
            <thead>
            <tr>
                <th>{{ trans('budgets::general.account') }}</th>
                <th>{{ trans('budgets::general.amount') }}</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach($lines as $index => $line)
                <tr>
                    <td>
                        <select name="lines[{{ $index }}][account_id]" class="form-control" required>
                            <option value="">{{ trans('general.select') }}</option>
                            @foreach($accountOptions as $value => $label)
                                <option value="{{ $value }}" @selected((string) ($line['account_id'] ?? '') === (string) $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <input type="number" step="0.0001" name="lines[{{ $index }}][amount]" value="{{ $line['amount'] ?? '' }}" class="form-control" required>
                    </td>
                    <td class="text-end">
                        <button type="button" class="btn btn-outline-danger btn-sm remove-budget-line">{{ trans('budgets::general.remove_line') }}</button>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tbody = document.querySelector('#budget-lines-table tbody');
        const addButton = document.getElementById('add-budget-line');
        const copySelect = document.getElementById('copy_budget_id');
        const accountOptions = @json($accountOptions);

        function optionMarkup(selectedValue) {
            let html = '<option value="">{{ trans('general.select') }}</option>';
            Object.entries(accountOptions).forEach(([value, label]) => {
                const selected = String(selectedValue) === String(value) ? ' selected' : '';
                html += `<option value="${value}"${selected}>${label}</option>`;
            });

            return html;
        }

        function addRow(values = {account_id: '', amount: ''}) {
            const index = tbody.querySelectorAll('tr').length;
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><select name="lines[${index}][account_id]" class="form-control" required>${optionMarkup(values.account_id)}</select></td>
                <td><input type="number" step="0.0001" name="lines[${index}][amount]" value="${values.amount ?? ''}" class="form-control" required></td>
                <td class="text-end"><button type="button" class="btn btn-outline-danger btn-sm remove-budget-line">{{ trans('budgets::general.remove_line') }}</button></td>
            `;
            tbody.appendChild(row);
        }

        addButton?.addEventListener('click', function () {
            addRow();
        });

        tbody?.addEventListener('click', function (event) {
            if (!event.target.classList.contains('remove-budget-line')) {
                return;
            }

            if (tbody.querySelectorAll('tr').length === 1) {
                tbody.querySelector('select').value = '';
                tbody.querySelector('input').value = '';
                return;
            }

            event.target.closest('tr').remove();
        });

        copySelect?.addEventListener('change', function () {
            if (!this.value) {
                return;
            }

            const url = new URL(window.location.href);
            url.searchParams.set('copy_budget_id', this.value);
            window.location.href = url.toString();
        });
    });
</script>
