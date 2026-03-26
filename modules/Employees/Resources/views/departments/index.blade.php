<x-layouts.admin>
    <x-slot name="title">
        {{ trans('employees::general.departments') }}
    </x-slot>

    <x-slot name="favorite"
        title="{{ trans('employees::general.departments') }}"
        icon="business"
        route="employees.departments.index"
    ></x-slot>

    <x-slot name="buttons">
        <x-link href="{{ route('employees.departments.create') }}" kind="primary">
            {{ trans('general.title.new', ['type' => trans('employees::general.department')]) }}
        </x-link>
    </x-slot>

    <x-slot name="content">
        <div class="bg-white rounded-xl shadow-sm">
            <table class="w-full">
                <thead>
                    <tr class="border-b">
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('general.name') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('employees::general.manager') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('employees::general.employee_count') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('general.description') }}</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-gray-500">{{ trans('general.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($departments as $department)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm">
                                <a href="{{ route('employees.departments.edit', $department->id) }}" class="text-purple-700 hover:underline font-medium">
                                    {{ $department->name }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                {{ $department->manager?->name ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                {{ $department->employees_count }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">
                                {{ Str::limit($department->description, 60) }}
                            </td>
                            <td class="px-4 py-3 text-sm text-right">
                                <x-dropdown id="dropdown-{{ $department->id }}">
                                    <x-dropdown.link href="{{ route('employees.departments.edit', $department->id) }}">
                                        {{ trans('general.edit') }}
                                    </x-dropdown.link>
                                    <x-delete-link :model="$department" route="employees.departments.destroy" />
                                </x-dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                                {{ trans('general.no_records') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-slot>
</x-layouts.admin>
