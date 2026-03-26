<x-layouts.admin>
    <x-slot name="title">{{ trans('payroll::general.pay_items') }}</x-slot>

    <x-slot name="buttons">
        <x-link href="{{ route('payroll.pay-items.create') }}" kind="primary">
            {{ trans('general.title.new', ['type' => trans('payroll::general.pay_item')]) }}
        </x-link>
    </x-slot>

    <x-slot name="content">
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <table class="w-full">
                <thead>
                    <tr class="border-b">
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('general.name') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('payroll::general.type') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('payroll::general.default_amount') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('payroll::general.percentage') }}</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-gray-500">{{ trans('general.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr class="border-b">
                            <td class="px-4 py-3 text-sm">{{ $item->name }}</td>
                            <td class="px-4 py-3 text-sm">{{ trans('payroll::general.types.' . $item->type) }}</td>
                            <td class="px-4 py-3 text-sm">{{ $item->default_amount !== null ? number_format($item->default_amount, 2) : '-' }}</td>
                            <td class="px-4 py-3 text-sm">{{ $item->is_percentage ? trans('general.yes') : trans('general.no') }}</td>
                            <td class="px-4 py-3 text-sm text-right">
                                <a href="{{ route('payroll.pay-items.edit', $item->id) }}" class="text-purple-700 hover:underline mr-3">{{ trans('general.edit') }}</a>
                                <form method="POST" action="{{ route('payroll.pay-items.destroy', $item->id) }}" class="inline" onsubmit="return confirm('{{ trans('general.delete_confirm') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">{{ trans('general.delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-gray-500">{{ trans('general.no_records') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $items->links() }}</div>
    </x-slot>
</x-layouts.admin>
