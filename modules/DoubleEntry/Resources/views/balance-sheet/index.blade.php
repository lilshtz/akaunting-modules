<x-layouts.admin>
    <x-slot name="title">{{ trans('double-entry::general.balance_sheet') }}</x-slot>

    <x-slot name="content">
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <form method="GET" action="{{ route('double-entry.balance-sheet.index') }}" class="flex gap-4 items-end">
                <div>
                    <label class="text-sm">{{ trans('double-entry::general.date_to') }}</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="border rounded-lg px-3 py-2">
                </div>
                <button type="submit" class="px-4 py-2 border rounded-lg">Filter</button>
                <button type="submit" name="format" value="csv" class="px-4 py-2 border rounded-lg">{{ trans('double-entry::general.csv') }}</button>
                <button type="submit" name="format" value="pdf" class="px-4 py-2 border rounded-lg">{{ trans('double-entry::general.pdf') }}</button>
            </form>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @foreach (['asset', 'liability', 'equity'] as $section)
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h2 class="text-lg font-semibold mb-4">{{ trans('double-entry::general.types.' . $section) }}</h2>

                    @foreach ($report['sections'][$section] as $row)
                        <div class="flex justify-between py-2 border-b">
                            <span>{{ $row['account']->code }} - {{ $row['account']->name }}</span>
                            <span>{{ money($row['balance'], setting('default.currency', 'USD')) }}</span>
                        </div>
                    @endforeach

                    <div class="flex justify-between pt-4 font-semibold">
                        <span>Total</span>
                        <span>{{ money($report['totals'][$section], setting('default.currency', 'USD')) }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </x-slot>
</x-layouts.admin>
