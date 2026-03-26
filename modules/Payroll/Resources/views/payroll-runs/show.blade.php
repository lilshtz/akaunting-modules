<x-layouts.admin>
    <x-slot name="title">#{{ $run->id }} {{ trans('payroll::general.payroll_run') }}</x-slot>

    <x-slot name="buttons">
        @if ($run->status === 'review')
            <form method="POST" action="{{ route('payroll.payroll-runs.approve', $run->id) }}" class="inline">
                @csrf
                <button type="submit" class="bg-emerald-600 text-white px-4 py-2 rounded-lg">{{ trans('payroll::general.approve_run') }}</button>
            </form>
        @endif

        @if ($run->status === 'approved')
            <form method="POST" action="{{ route('payroll.payroll-runs.process', $run->id) }}" class="inline">
                @csrf
                <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-lg">{{ trans('payroll::general.process_run') }}</button>
            </form>
        @endif
    </x-slot>

    <x-slot name="content">
        <div class="grid gap-6 lg:grid-cols-4 mb-6">
            <div class="bg-white rounded-xl shadow-sm p-4"><div class="text-sm text-gray-500">{{ trans('payroll::general.gross') }}</div><div class="text-xl font-semibold">{{ money($run->total_gross, setting('default.currency', 'USD')) }}</div></div>
            <div class="bg-white rounded-xl shadow-sm p-4"><div class="text-sm text-gray-500">{{ trans('payroll::general.deductions') }}</div><div class="text-xl font-semibold">{{ money($run->total_deductions, setting('default.currency', 'USD')) }}</div></div>
            <div class="bg-white rounded-xl shadow-sm p-4"><div class="text-sm text-gray-500">{{ trans('payroll::general.net') }}</div><div class="text-xl font-semibold">{{ money($run->total_net, setting('default.currency', 'USD')) }}</div></div>
            <div class="bg-white rounded-xl shadow-sm p-4"><div class="text-sm text-gray-500">{{ trans('payroll::general.status') }}</div><div class="text-xl font-semibold">{{ trans('payroll::general.statuses.' . $run->status) }}</div></div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <form method="POST" action="{{ route('payroll.payroll-runs.update', $run->id) }}">
                @csrf
                @method('PUT')

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b">
                                <th class="px-3 py-2 text-left text-sm font-medium text-gray-500">{{ trans('employees::general.employee') }}</th>
                                <th class="px-3 py-2 text-left text-sm font-medium text-gray-500">{{ trans('payroll::general.gross') }}</th>
                                <th class="px-3 py-2 text-left text-sm font-medium text-gray-500">{{ trans('payroll::general.benefits') }}</th>
                                <th class="px-3 py-2 text-left text-sm font-medium text-gray-500">{{ trans('payroll::general.deductions') }}</th>
                                <th class="px-3 py-2 text-left text-sm font-medium text-gray-500">{{ trans('payroll::general.net') }}</th>
                                <th class="px-3 py-2 text-left text-sm font-medium text-gray-500">{{ trans('payroll::general.notes') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($run->employees as $line)
                                <tr class="border-b">
                                    <td class="px-3 py-3 text-sm">{{ $line->employee?->name }}</td>
                                    <td class="px-3 py-3"><input type="number" step="0.0001" min="0" name="lines[{{ $line->id }}][gross_amount]" value="{{ old('lines.' . $line->id . '.gross_amount', $line->gross_amount) }}" class="w-28 rounded-lg border-gray-300" @disabled(! in_array($run->status, ['draft', 'review'], true)) /></td>
                                    <td class="px-3 py-3"><input type="number" step="0.0001" min="0" name="lines[{{ $line->id }}][benefit_amount]" value="{{ old('lines.' . $line->id . '.benefit_amount', $line->benefit_amount) }}" class="w-28 rounded-lg border-gray-300" @disabled(! in_array($run->status, ['draft', 'review'], true)) /></td>
                                    <td class="px-3 py-3"><input type="number" step="0.0001" min="0" name="lines[{{ $line->id }}][deduction_amount]" value="{{ old('lines.' . $line->id . '.deduction_amount', $line->deduction_amount) }}" class="w-28 rounded-lg border-gray-300" @disabled(! in_array($run->status, ['draft', 'review'], true)) /></td>
                                    <td class="px-3 py-3 text-sm">{{ money($line->net_amount, setting('default.currency', 'USD')) }}</td>
                                    <td class="px-3 py-3"><input type="text" name="lines[{{ $line->id }}][notes]" value="{{ old('lines.' . $line->id . '.notes', $line->notes) }}" class="w-full rounded-lg border-gray-300" @disabled(! in_array($run->status, ['draft', 'review'], true)) /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if (in_array($run->status, ['draft', 'review'], true))
                    <div class="mt-4">
                        <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-lg">{{ trans('general.save') }}</button>
                    </div>
                @endif
            </form>
        </div>
    </x-slot>
</x-layouts.admin>
