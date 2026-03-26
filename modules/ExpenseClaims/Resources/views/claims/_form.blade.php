@csrf

<div class="card">
    <div class="card-body">
        <div class="form-group">
            <label>{{ trans('expense-claims::general.employee') }}</label>
            <select name="employee_id" class="form-control" required>
                <option value=""></option>
                @foreach($employees as $id => $name)
                    <option value="{{ $id }}" {{ (string) old('employee_id', $claim->employee_id ?? '') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label>{{ trans('expense-claims::general.approver') }}</label>
            <select name="approver_id" class="form-control">
                <option value=""></option>
                @foreach($approvers as $id => $name)
                    <option value="{{ $id }}" {{ (string) old('approver_id', $claim->approver_id ?? '') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label>{{ trans('expense-claims::general.due_date') }}</label>
            <input type="date" name="due_date" class="form-control" value="{{ old('due_date', optional($claim->due_date ?? null)->toDateString()) }}">
        </div>

        <div class="form-group">
            <label>{{ trans('expense-claims::general.description') }}</label>
            <textarea name="description" class="form-control" rows="3">{{ old('description', $claim->description ?? '') }}</textarea>
        </div>

        <h4>{{ trans('expense-claims::general.items') }}</h4>
        @php($items = old('items', isset($claim) ? $claim->items->toArray() : [['date' => now()->toDateString(), 'description' => '', 'amount' => '', 'category_id' => '', 'notes' => '', 'paid_by_employee' => 1]]))
        @foreach($items as $index => $item)
            <div class="border rounded p-3 mb-3">
                <div class="form-row">
                    <div class="form-group col-md-2">
                        <label>{{ trans('expense-claims::general.item_date') }}</label>
                        <input type="date" name="items[{{ $index }}][date]" class="form-control" value="{{ old("items.$index.date", isset($item['date']) ? \Illuminate\Support\Carbon::parse($item['date'])->toDateString() : '') }}" required>
                    </div>
                    <div class="form-group col-md-3">
                        <label>{{ trans('expense-claims::general.category') }}</label>
                        <select name="items[{{ $index }}][category_id]" class="form-control">
                            <option value=""></option>
                            @foreach($categories as $categoryId => $categoryName)
                                <option value="{{ $categoryId }}" {{ (string) old("items.$index.category_id", $item['category_id'] ?? '') === (string) $categoryId ? 'selected' : '' }}>{{ $categoryName }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>{{ trans('expense-claims::general.description') }}</label>
                        <input type="text" name="items[{{ $index }}][description]" class="form-control" value="{{ old("items.$index.description", $item['description'] ?? '') }}" required>
                    </div>
                    <div class="form-group col-md-3">
                        <label>{{ trans('expense-claims::general.item_amount') }}</label>
                        <input type="number" name="items[{{ $index }}][amount]" class="form-control" step="0.01" min="0" value="{{ old("items.$index.amount", $item['amount'] ?? '') }}" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-5">
                        <label>{{ trans('expense-claims::general.receipt') }}</label>
                        <input type="file" name="items[{{ $index }}][receipt]" class="form-control">
                    </div>
                    <div class="form-group col-md-5">
                        <label>{{ trans('general.notes') }}</label>
                        <input type="text" name="items[{{ $index }}][notes]" class="form-control" value="{{ old("items.$index.notes", $item['notes'] ?? '') }}">
                    </div>
                    <div class="form-group col-md-2">
                        <label>{{ trans('expense-claims::general.paid_by_employee') }}</label>
                        <select name="items[{{ $index }}][paid_by_employee]" class="form-control">
                            <option value="1" {{ (string) old("items.$index.paid_by_employee", $item['paid_by_employee'] ?? 1) === '1' ? 'selected' : '' }}>{{ trans('general.yes') }}</option>
                            <option value="0" {{ (string) old("items.$index.paid_by_employee", $item['paid_by_employee'] ?? 1) === '0' ? 'selected' : '' }}>{{ trans('general.no') }}</option>
                        </select>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<button type="submit" class="btn btn-primary mt-3">{{ trans('general.save') }}</button>
