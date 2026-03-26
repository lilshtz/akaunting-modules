<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">{{ trans('general.name') }}</label>
        <input type="text" name="name" value="{{ old('name', optional($deal ?? null)->name) }}" required class="w-full rounded-lg border-gray-300" />
    </div>
    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">{{ trans('crm::general.contact') }}</label>
        <select name="crm_contact_id" class="w-full rounded-lg border-gray-300">
            @foreach ($contacts as $id => $name)
                <option value="{{ $id }}" {{ (string) old('crm_contact_id', optional($deal ?? null)->crm_contact_id ?? request('crm_contact_id', '')) === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">{{ trans('crm::general.deal_value') }}</label>
        <input type="number" step="0.01" min="0" name="value" value="{{ old('value', optional($deal ?? null)->value) }}" required class="w-full rounded-lg border-gray-300" />
    </div>
    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">{{ trans('crm::general.stage') }}</label>
        <select name="stage_id" class="w-full rounded-lg border-gray-300">
            @foreach ($stages as $stage)
                <option value="{{ $stage->id }}" {{ (string) old('stage_id', optional($deal ?? null)->stage_id ?? $stages->first()?->id) === (string) $stage->id ? 'selected' : '' }}>{{ $stage->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">{{ trans('crm::general.expected_close') }}</label>
        <input type="date" name="expected_close" value="{{ old('expected_close', optional(optional($deal ?? null)->expected_close)->format('Y-m-d')) }}" class="w-full rounded-lg border-gray-300" />
    </div>
    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">{{ trans('crm::general.linked_invoice') }}</label>
        <select name="invoice_id" class="w-full rounded-lg border-gray-300">
            @foreach ($invoiceOptions as $id => $label)
                <option value="{{ $id }}" {{ (string) old('invoice_id', optional($deal ?? null)->invoice_id) === (string) $id ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-gray-700">{{ trans('general.notes') }}</label>
        <textarea name="notes" rows="5" class="w-full rounded-lg border-gray-300">{{ old('notes', optional($deal ?? null)->notes) }}</textarea>
    </div>
</div>
