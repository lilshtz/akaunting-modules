<x-layouts.admin>
    <x-slot name="title">{{ trans('double-entry::general.import_accounts') }}</x-slot>

    <x-slot name="content">
        <form method="POST" action="{{ route('double-entry.accounts.import') }}" enctype="multipart/form-data" class="space-y-6 rounded-xl bg-white p-6 shadow-sm">
            @csrf

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700" for="file">{{ trans('general.form.select.file') }} <span class="text-red-500">*</span></label>
                <input type="file" name="file" id="file" accept=".csv,.txt" class="w-full rounded-lg border-gray-300 shadow-sm" required>
                <p class="mt-1 text-sm text-gray-500">CSV format: Code, Name, Type, Description, Opening Balance</p>
                @error('file')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end gap-2">
                <a href="{{ route('double-entry.accounts.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">{{ trans('general.cancel') }}</a>
                <button type="submit" class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">{{ trans('import.import') }}</button>
            </div>
        </form>
    </x-slot>
</x-layouts.admin>
