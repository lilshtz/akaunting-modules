<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">{{ trans('general.name') }}</label>
        <input type="text" name="name" value="{{ old('name', $contact->name ?? '') }}" required class="w-full rounded-lg border-gray-300" />
    </div>
    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">{{ trans('general.email') }}</label>
        <input type="email" name="email" value="{{ old('email', $contact->email ?? '') }}" class="w-full rounded-lg border-gray-300" />
    </div>
    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">{{ trans('general.phone') }}</label>
        <input type="text" name="phone" value="{{ old('phone', $contact->phone ?? '') }}" class="w-full rounded-lg border-gray-300" />
    </div>
    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">{{ trans('crm::general.crm_company') }}</label>
        <select name="crm_company_id" class="w-full rounded-lg border-gray-300">
            @foreach ($crmCompanies as $id => $name)
                <option value="{{ $id }}" {{ (string) old('crm_company_id', $contact->crm_company_id ?? '') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">{{ trans('crm::general.source') }}</label>
        <select name="source" class="w-full rounded-lg border-gray-300">
            @foreach ($sources as $key => $label)
                <option value="{{ $key }}" {{ old('source', $contact->source ?? 'other') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">{{ trans('crm::general.stage') }}</label>
        <select name="stage" class="w-full rounded-lg border-gray-300">
            @foreach ($stages as $key => $label)
                <option value="{{ $key }}" {{ old('stage', $contact->stage ?? 'lead') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-gray-700">{{ trans('crm::general.owner') }}</label>
        <select name="owner_user_id" class="w-full rounded-lg border-gray-300">
            @foreach ($owners as $id => $name)
                <option value="{{ $id }}" {{ (string) old('owner_user_id', $contact->owner_user_id ?? '') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
            @endforeach
        </select>
    </div>
    <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-gray-700">{{ trans('general.notes') }}</label>
        <textarea name="notes" rows="5" class="w-full rounded-lg border-gray-300">{{ old('notes', $contact->notes ?? '') }}</textarea>
    </div>
</div>
