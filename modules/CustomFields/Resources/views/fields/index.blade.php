<x-layouts.admin>
    <x-slot name="title">
        {{ trans('custom-fields::general.field_definitions') }}
    </x-slot>

    <x-slot name="favorite"
        title="{{ trans('custom-fields::general.field_definitions') }}"
        icon="tune"
        route="custom-fields.fields.index"
    ></x-slot>

    <x-slot name="buttons">
        <x-link href="{{ route('custom-fields.fields.create') }}" kind="primary">
            {{ trans('general.title.new', ['type' => trans_choice('custom-fields::general.fields', 1)]) }}
        </x-link>
    </x-slot>

    <x-slot name="content">
        @forelse ($groupedFields as $entityType => $fields)
            <div class="mb-6">
                <h2 class="text-lg font-semibold mb-3 capitalize">
                    {{ $entityTypes[$entityType] ?? $entityType }}
                </h2>

                <div class="bg-white rounded-xl shadow-sm">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b">
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('custom-fields::general.field_name') }}</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('custom-fields::general.field_type') }}</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('custom-fields::general.required_field') }}</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('custom-fields::general.show_on_pdf') }}</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('custom-fields::general.position') }}</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('general.enabled') }}</th>
                                <th class="px-4 py-3 text-right text-sm font-medium text-gray-500">{{ trans('general.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($fields as $field)
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm">
                                        <a href="{{ route('custom-fields.fields.edit', $field->id) }}" class="text-purple-700 hover:underline">
                                            {{ $field->name }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        {{ trans('custom-fields::general.field_types.' . $field->field_type) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        @if ($field->required)
                                            <span class="text-red-600">{{ trans('general.yes') }}</span>
                                        @else
                                            <span class="text-gray-400">{{ trans('general.no') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        @if ($field->show_on_pdf)
                                            <span class="text-green-600">{{ trans('general.yes') }}</span>
                                        @else
                                            <span class="text-gray-400">{{ trans('general.no') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm">{{ $field->position }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        @if ($field->enabled)
                                            <span class="text-green-600">{{ trans('general.yes') }}</span>
                                        @else
                                            <span class="text-red-600">{{ trans('general.no') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right">
                                        <x-dropdown id="dropdown-{{ $field->id }}">
                                            <x-dropdown.link href="{{ route('custom-fields.fields.edit', $field->id) }}">
                                                {{ trans('general.edit') }}
                                            </x-dropdown.link>
                                            <x-delete-link :model="$field" route="custom-fields.fields.destroy" />
                                        </x-dropdown>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl shadow-sm p-6 text-center text-gray-500">
                {{ trans('custom-fields::general.no_fields') }}
            </div>
        @endforelse
    </x-slot>
</x-layouts.admin>
