<x-layouts.admin>
    <x-slot name="title">
        {{ $employee->name }}
    </x-slot>

    <x-slot name="buttons">
        <x-link href="{{ route('employees.employees.edit', $employee->id) }}" kind="primary">
            {{ trans('general.edit') }}
        </x-link>
    </x-slot>

    <x-slot name="content">
        {{-- Header Card --}}
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <div class="flex items-start gap-6">
                @if ($employee->photo_path)
                    <img src="{{ Storage::url($employee->photo_path) }}" alt="" class="w-20 h-20 rounded-full object-cover" />
                @else
                    <div class="w-20 h-20 rounded-full bg-purple-100 flex items-center justify-center text-purple-700 text-2xl font-bold">
                        {{ strtoupper(substr($employee->name, 0, 2)) }}
                    </div>
                @endif

                <div class="flex-1">
                    <h2 class="text-2xl font-bold text-gray-900">{{ $employee->name }}</h2>
                    <p class="text-gray-500 mt-1">{{ $employee->contact?->email }}</p>
                    <div class="flex gap-3 mt-3">
                        @if ($employee->status === 'active')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                {{ $employee->status_label }}
                            </span>
                        @elseif ($employee->status === 'terminated')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                {{ $employee->status_label }}
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                                {{ $employee->status_label }}
                            </span>
                        @endif

                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                            {{ $employee->type_label }}
                        </span>

                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800">
                            {{ $employee->classification_label }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Details Tab --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            {{-- Employment Details --}}
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-semibold mb-4">{{ trans('employees::general.employment_info') }}</h3>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">{{ trans('employees::general.department') }}</dt>
                        <dd class="text-sm font-medium">{{ $employee->department?->name ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">{{ trans('employees::general.hire_date') }}</dt>
                        <dd class="text-sm font-medium">{{ $employee->hire_date?->format('M d, Y') ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">{{ trans('employees::general.birthday') }}</dt>
                        <dd class="text-sm font-medium">{{ $employee->birthday?->format('M d, Y') ?? '-' }}</dd>
                    </div>
                    @if ($employee->terminated_at)
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">{{ trans('employees::general.terminated_at') }}</dt>
                            <dd class="text-sm font-medium text-red-600">{{ $employee->terminated_at->format('M d, Y') }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            {{-- Salary Details --}}
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-semibold mb-4">{{ trans('employees::general.salary_info') }}</h3>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">{{ trans('employees::general.salary') }}</dt>
                        <dd class="text-sm font-medium">{{ $employee->salary_display }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">{{ trans('employees::general.bank_name') }}</dt>
                        <dd class="text-sm font-medium">{{ $employee->bank_name ?? '-' }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- Notes --}}
        @if ($employee->notes)
            <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                <h3 class="text-lg font-semibold mb-2">{{ trans('employees::general.notes') }}</h3>
                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $employee->notes }}</p>
            </div>
        @endif

        {{-- Documents Section --}}
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold">{{ trans('employees::general.documents') }}</h3>
            </div>

            {{-- Upload Form --}}
            <form method="POST" action="{{ route('employees.employees.documents.store', $employee->id) }}" enctype="multipart/form-data" class="mb-6 p-4 bg-gray-50 rounded-lg">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('general.name') }}</label>
                        <input type="text" name="name" required class="w-full rounded-lg border-gray-300 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('employees::general.document_type') }}</label>
                        <select name="type" required class="w-full rounded-lg border-gray-300 text-sm">
                            @foreach ($documentTypes as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('general.file') }}</label>
                        <input type="file" name="file" required class="w-full text-sm" />
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-purple-700">
                            {{ trans('employees::general.upload_document') }}
                        </button>
                    </div>
                </div>
            </form>

            {{-- Document List --}}
            @if ($employee->documents->isNotEmpty())
                <table class="w-full">
                    <thead>
                        <tr class="border-b">
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">{{ trans('general.name') }}</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">{{ trans('employees::general.document_type') }}</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">{{ trans('employees::general.uploaded_at') }}</th>
                            <th class="px-4 py-2 text-right text-sm font-medium text-gray-500">{{ trans('general.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($employee->documents as $doc)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-2 text-sm">{{ $doc->name }}</td>
                                <td class="px-4 py-2 text-sm">{{ $documentTypes[$doc->type] ?? $doc->type }}</td>
                                <td class="px-4 py-2 text-sm">{{ $doc->uploaded_at?->format('M d, Y') }}</td>
                                <td class="px-4 py-2 text-sm text-right">
                                    <a href="{{ route('employees.employees.documents.download', [$employee->id, $doc->id]) }}" class="text-purple-700 hover:underline mr-3">
                                        {{ trans('general.download') }}
                                    </a>
                                    <form method="POST" action="{{ route('employees.employees.documents.destroy', [$employee->id, $doc->id]) }}" class="inline" onsubmit="return confirm('{{ trans('general.delete_confirm') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">{{ trans('general.delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-gray-500 text-sm">{{ trans('employees::general.no_documents') }}</p>
            @endif
        </div>
    </x-slot>
</x-layouts.admin>
