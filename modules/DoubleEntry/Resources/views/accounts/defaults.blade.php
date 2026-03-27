<x-layouts.admin>
    <x-slot name="title">{{ trans('double-entry::general.account_defaults') }}</x-slot>

    <x-slot name="content">
        <form method="POST" action="{{ route('double-entry.account-defaults.update') }}" class="space-y-6 rounded-xl bg-white p-6 shadow-sm">
            @csrf

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                @foreach($types as $type => $label)
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700" for="{{ $type }}">{{ $label }}</label>
                        <select name="{{ $type }}" id="{{ $type }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">-- {{ trans('general.form.select.field', ['field' => trans('double-entry::general.account')]) }} --</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}" {{ ($defaults[$type] ?? null) == $account->id ? 'selected' : '' }}>
                                    {{ $account->code }} - {{ $account->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endforeach
            </div>

            <div class="flex justify-end">
                <button type="submit" class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">{{ trans('general.save') }}</button>
            </div>
        </form>
    </x-slot>
</x-layouts.admin>
