<x-layouts.admin>
    <x-slot name="title">{{ trans('appointments::general.forms') }}</x-slot>

    <x-slot name="buttons">
        <x-link href="{{ route('appointments.forms.create') }}" kind="primary">{{ trans('general.title.new', ['type' => trans('appointments::general.form')]) }}</x-link>
    </x-slot>

    <x-slot name="content">
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <table class="w-full">
                <thead>
                    <tr class="border-b">
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('general.name') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('appointments::general.booking_link') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('general.enabled') }}</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-gray-500">{{ trans('general.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($forms as $form)
                        <tr class="border-b">
                            <td class="px-4 py-3 text-sm"><a href="{{ route('appointments.forms.show', $form->id) }}" class="text-purple-700 hover:underline">{{ $form->name }}</a></td>
                            <td class="px-4 py-3 text-sm break-all">{{ $form->booking_url }}</td>
                            <td class="px-4 py-3 text-sm">{{ $form->enabled ? trans('general.yes') : trans('general.no') }}</td>
                            <td class="px-4 py-3 text-sm text-right">
                                <a href="{{ route('appointments.forms.edit', $form->id) }}" class="text-purple-700 hover:underline mr-3">{{ trans('general.edit') }}</a>
                                <form method="POST" action="{{ route('appointments.forms.destroy', $form->id) }}" class="inline" onsubmit="return confirm('{{ trans('general.delete_confirm') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">{{ trans('general.delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-gray-500">{{ trans('general.no_records') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $forms->links() }}</div>
    </x-slot>
</x-layouts.admin>
