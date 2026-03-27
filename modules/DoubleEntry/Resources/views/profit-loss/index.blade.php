<x-layouts.admin>
    <x-slot name="title">{{ trans('double-entry::general.profit_loss') }}</x-slot>

    <x-slot name="content">
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <form method="GET" action="{{ route('double-entry.profit-loss.index') }}" class="grid grid-cols-1 lg:grid-cols-4 gap-4 items-end">
                <div>
                    <label class="text-sm">{{ trans('double-entry::general.date_from') }}</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="w-full border rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="text-sm">{{ trans('double-entry::general.date_to') }}</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="w-full border rounded-lg px-3 py-2">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 border rounded-lg">Filter</button>
                    <button type="submit" name="format" value="csv" class="px-4 py-2 border rounded-lg">{{ trans('double-entry::general.csv') }}</button>
                    <button type="submit" name="format" value="pdf" class="px-4 py-2 border rounded-lg">{{ trans('double-entry::general.pdf') }}</button>
                </div>
            </form>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-semibold mb-4">{{ trans('double-entry::general.types.income') }}</h2>
                @foreach ($report['income'] as $row)
                    <div class="flex justify-between py-2 border-b">
                        <span>{{ $row['account']->code }} - {{ $row['account']->name }}</span>
                        <span>{{ money($row['balance'], setting('default.currency', 'USD')) }}</span>
                    </div>
                @endforeach
                <div class="flex justify-between pt-4 font-semibold">
                    <span>Total Income</span>
                    <span>{{ money($report['totals']['income'], setting('default.currency', 'USD')) }}</span>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-semibold mb-4">{{ trans('double-entry::general.types.expense') }}</h2>
                @foreach ($report['expense'] as $row)
                    <div class="flex justify-between py-2 border-b">
                        <span>{{ $row['account']->code }} - {{ $row['account']->name }}</span>
                        <span>{{ money($row['balance'], setting('default.currency', 'USD')) }}</span>
                    </div>
                @endforeach
                <div class="flex justify-between pt-4 font-semibold">
                    <span>Total Expense</span>
                    <span>{{ money($report['totals']['expense'], setting('default.currency', 'USD')) }}</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 mt-6">
            <div class="flex justify-between text-xl font-semibold">
                <span>{{ trans('double-entry::general.net_profit') }}</span>
                <span>{{ money($report['totals']['income'] - $report['totals']['expense'], setting('default.currency', 'USD')) }}</span>
            </div>
        </div>
    </x-slot>
</x-layouts.admin>
