<x-layouts.admin>
    <x-slot name="title">{{ trans('crm::general.import_contacts') }}</x-slot>

    <x-slot name="content">
        <form method="POST" action="{{ route('crm.contacts.import.store') }}" enctype="multipart/form-data" class="space-y-4 rounded-xl bg-white p-6 shadow-sm">
            @csrf
            <p class="text-sm text-gray-500">{{ trans('crm::general.import_help') }}</p>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">CSV</label>
                <input type="file" name="file" accept=".csv,.txt" required class="w-full rounded-lg border-gray-300" />
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">{{ trans('crm::general.crm_company') }}</label>
                    <select name="crm_company_id" class="w-full rounded-lg border-gray-300">
                        @foreach ($crmCompanies as $id => $name)
                            <option value="{{ $id }}" {{ (string) old('crm_company_id', '') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">{{ trans('crm::general.source') }}</label>
                    <select name="source" class="w-full rounded-lg border-gray-300">
                        @foreach ($sources as $key => $label)
                            <option value="{{ $key }}" {{ old('source', 'other') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">{{ trans('crm::general.stage') }}</label>
                    <select name="stage" class="w-full rounded-lg border-gray-300">
                        @foreach ($stages as $key => $label)
                            <option value="{{ $key }}" {{ old('stage', 'lead') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="flex justify-end gap-2">
                <x-link href="{{ route('crm.contacts.index') }}" kind="secondary">{{ trans('general.cancel') }}</x-link>
                <button type="submit" class="rounded-lg bg-sky-600 px-4 py-2 text-sm text-white">{{ trans('crm::general.import_contacts') }}</button>
            </div>
        </form>
    </x-slot>
</x-layouts.admin>
