<div class="grid grid-cols-1 gap-4">
    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">{{ trans('general.name') }}</label>
        <input type="text" name="name" value="{{ old('name', $company->name ?? '') }}" required class="w-full rounded-lg border-gray-300" />
    </div>
    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">{{ trans('general.address') }}</label>
        <textarea name="address" rows="4" class="w-full rounded-lg border-gray-300">{{ old('address', $company->address ?? '') }}</textarea>
    </div>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">{{ trans('general.currency') }}</label>
            <input type="text" name="currency" maxlength="3" value="{{ old('currency', $company->currency ?? '') }}" class="w-full rounded-lg border-gray-300 uppercase" />
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">{{ trans('crm::general.default_stage') }}</label>
            <select name="default_stage" class="w-full rounded-lg border-gray-300">
                @foreach ($stages as $key => $label)
                    <option value="{{ $key }}" {{ old('default_stage', $company->default_stage ?? 'lead') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>
