<x-layouts.admin>
    <x-slot name="title">{{ trans('payroll::general.pay_calendars') }}</x-slot>

    <x-slot name="buttons">
        <x-link href="{{ route('payroll.pay-calendars.create') }}" kind="primary">
            {{ trans('general.title.new', ['type' => trans('payroll::general.pay_calendar')]) }}
        </x-link>
    </x-slot>

    <x-slot name="content">
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <table class="w-full">
                <thead>
                    <tr class="border-b">
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('general.name') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('payroll::general.frequency') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('payroll::general.next_run_date') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('payroll::general.assigned_employees') }}</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-gray-500">{{ trans('general.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($calendars as $calendar)
                        <tr class="border-b">
                            <td class="px-4 py-3 text-sm"><a href="{{ route('payroll.pay-calendars.show', $calendar->id) }}" class="text-purple-700 hover:underline">{{ $calendar->name }}</a></td>
                            <td class="px-4 py-3 text-sm">{{ trans('payroll::general.frequencies.' . $calendar->frequency) }}</td>
                            <td class="px-4 py-3 text-sm">{{ $calendar->next_run_date?->format('M d, Y') }}</td>
                            <td class="px-4 py-3 text-sm">{{ $calendar->employees_count }}</td>
                            <td class="px-4 py-3 text-sm text-right">
                                <a href="{{ route('payroll.pay-calendars.edit', $calendar->id) }}" class="text-purple-700 hover:underline mr-3">{{ trans('general.edit') }}</a>
                                <form method="POST" action="{{ route('payroll.pay-calendars.destroy', $calendar->id) }}" class="inline" onsubmit="return confirm('{{ trans('general.delete_confirm') }}')">
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

        <div class="mt-4">{{ $calendars->links() }}</div>
    </x-slot>
</x-layouts.admin>
