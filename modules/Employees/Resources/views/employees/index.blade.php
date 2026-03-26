<x-layouts.admin>
    <x-slot name="title">
        {{ trans('employees::general.employee_directory') }}
    </x-slot>

    <x-slot name="favorite"
        title="{{ trans('employees::general.employees') }}"
        icon="badge"
        route="employees.employees.index"
    ></x-slot>

    <x-slot name="buttons">
        <x-link href="{{ route('employees.employees.create') }}" kind="primary">
            {{ trans('general.title.new', ['type' => trans('employees::general.employee')]) }}
        </x-link>
    </x-slot>

    <x-slot name="content">
        {{-- Filters --}}
        <div class="mb-4 flex flex-wrap gap-3">
            <form method="GET" action="{{ route('employees.employees.index') }}" class="flex flex-wrap gap-3 w-full">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ trans('general.search') }}..."
                    class="rounded-lg border-gray-300 text-sm px-3 py-2" />

                <select name="department_id" class="rounded-lg border-gray-300 text-sm px-3 py-2" onchange="this.form.submit()">
                    <option value="">{{ trans('employees::general.departments') }}</option>
                    @foreach ($departments as $id => $name)
                        <option value="{{ $id }}" {{ request('department_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>

                <select name="status" class="rounded-lg border-gray-300 text-sm px-3 py-2" onchange="this.form.submit()">
                    <option value="">{{ trans('general.statuses') }}</option>
                    @foreach ($statuses as $key => $label)
                        <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>

                <select name="type" class="rounded-lg border-gray-300 text-sm px-3 py-2" onchange="this.form.submit()">
                    <option value="">{{ trans('employees::general.employee_type') }}</option>
                    @foreach ($types as $key => $label)
                        <option value="{{ $key }}" {{ request('type') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>

                <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-purple-700">
                    {{ trans('general.search') }}
                </button>
            </form>
        </div>

        {{-- Employee Table --}}
        <div class="bg-white rounded-xl shadow-sm">
            <table class="w-full">
                <thead>
                    <tr class="border-b">
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('general.name') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('employees::general.department') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('employees::general.employee_type') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('employees::general.classification') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('general.status') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('employees::general.hire_date') }}</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-gray-500">{{ trans('general.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($employees as $employee)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm">
                                <div class="flex items-center gap-3">
                                    @if ($employee->photo_path)
                                        <img src="{{ Storage::url($employee->photo_path) }}" alt="" class="w-8 h-8 rounded-full object-cover" />
                                    @else
                                        <div class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center text-purple-700 text-xs font-bold">
                                            {{ strtoupper(substr($employee->name, 0, 2)) }}
                                        </div>
                                    @endif
                                    <a href="{{ route('employees.employees.show', $employee->id) }}" class="text-purple-700 hover:underline font-medium">
                                        {{ $employee->name }}
                                    </a>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm">{{ $employee->department?->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm">{{ $employee->type_label }}</td>
                            <td class="px-4 py-3 text-sm">{{ $employee->classification_label }}</td>
                            <td class="px-4 py-3 text-sm">
                                @if ($employee->status === 'active')
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        {{ $employee->status_label }}
                                    </span>
                                @elseif ($employee->status === 'terminated')
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        {{ $employee->status_label }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        {{ $employee->status_label }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm">{{ $employee->hire_date?->format('M d, Y') ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-right">
                                <x-dropdown id="dropdown-{{ $employee->id }}">
                                    <x-dropdown.link href="{{ route('employees.employees.show', $employee->id) }}">
                                        {{ trans('general.show') }}
                                    </x-dropdown.link>
                                    <x-dropdown.link href="{{ route('employees.employees.edit', $employee->id) }}">
                                        {{ trans('general.edit') }}
                                    </x-dropdown.link>
                                    <x-delete-link :model="$employee" route="employees.employees.destroy" />
                                </x-dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-gray-500">
                                {{ trans('employees::general.no_employees') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($employees->hasPages())
            <div class="mt-4">
                {{ $employees->withQueryString()->links() }}
            </div>
        @endif
    </x-slot>
</x-layouts.admin>
