<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- Income vs Expense Chart --}}
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-sm font-semibold mb-4">Income vs Expenses (12 Months)</h3>
        <canvas id="income-expense-chart" height="200"></canvas>
    </div>

    {{-- Top Accounts --}}
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-sm font-semibold mb-4">Top 5 Accounts (Last 90 Days)</h3>
        @if (count($topAccounts) > 0)
            <table class="w-full">
                <thead>
                    <tr class="border-b">
                        <th class="text-left text-xs font-medium text-gray-500 pb-2">Account</th>
                        <th class="text-right text-xs font-medium text-gray-500 pb-2">Activity</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($topAccounts as $account)
                        <tr class="border-b">
                            <td class="py-2 text-sm">{{ $account['code'] }} - {{ $account['name'] }}</td>
                            <td class="py-2 text-sm text-right">{{ number_format($account['total_activity'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="text-sm text-gray-500">No activity recorded.</p>
        @endif
    </div>

    {{-- Recent Journal Entries --}}
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-sm font-semibold mb-4">Recent Journal Entries</h3>
        @if ($recentEntries->count() > 0)
            <table class="w-full">
                <thead>
                    <tr class="border-b">
                        <th class="text-left text-xs font-medium text-gray-500 pb-2">Date</th>
                        <th class="text-left text-xs font-medium text-gray-500 pb-2">Reference</th>
                        <th class="text-left text-xs font-medium text-gray-500 pb-2">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recentEntries as $entry)
                        <tr class="border-b">
                            <td class="py-2 text-sm">{{ $entry->date->format('Y-m-d') }}</td>
                            <td class="py-2 text-sm">
                                <a href="{{ route('double-entry.journals.show', $entry->id) }}" class="text-purple-700 hover:underline">
                                    {{ $entry->reference ?? '#' . $entry->id }}
                                </a>
                            </td>
                            <td class="py-2 text-sm">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $entry->status === 'posted' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ ucfirst($entry->status) }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="text-sm text-gray-500">No journal entries yet.</p>
        @endif
    </div>

    {{-- Quick Links --}}
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-sm font-semibold mb-4">Quick Links</h3>
        <div class="grid grid-cols-2 gap-3">
            <a href="{{ route('double-entry.journals.create') }}" class="flex items-center gap-2 p-3 rounded-lg border hover:bg-purple-50 text-sm">
                <span class="material-icons text-purple-700" style="font-size: 20px;">add</span>
                New Journal Entry
            </a>
            <a href="{{ route('double-entry.general-ledger.index') }}" class="flex items-center gap-2 p-3 rounded-lg border hover:bg-purple-50 text-sm">
                <span class="material-icons text-purple-700" style="font-size: 20px;">menu_book</span>
                General Ledger
            </a>
            <a href="{{ route('double-entry.trial-balance.index') }}" class="flex items-center gap-2 p-3 rounded-lg border hover:bg-purple-50 text-sm">
                <span class="material-icons text-purple-700" style="font-size: 20px;">balance</span>
                Trial Balance
            </a>
            <a href="{{ route('double-entry.balance-sheet.index') }}" class="flex items-center gap-2 p-3 rounded-lg border hover:bg-purple-50 text-sm">
                <span class="material-icons text-purple-700" style="font-size: 20px;">account_balance</span>
                Balance Sheet
            </a>
            <a href="{{ route('double-entry.profit-loss.index') }}" class="flex items-center gap-2 p-3 rounded-lg border hover:bg-purple-50 text-sm">
                <span class="material-icons text-purple-700" style="font-size: 20px;">trending_up</span>
                Profit & Loss
            </a>
            <a href="{{ route('double-entry.accounts.index') }}" class="flex items-center gap-2 p-3 rounded-lg border hover:bg-purple-50 text-sm">
                <span class="material-icons text-purple-700" style="font-size: 20px;">account_tree</span>
                Chart of Accounts
            </a>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Chart !== 'undefined') {
            const ctx = document.getElementById('income-expense-chart');
            if (ctx) {
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: @json($chartData['months']),
                        datasets: [
                            {
                                label: 'Income',
                                data: @json($chartData['income']),
                                backgroundColor: 'rgba(34, 197, 94, 0.7)',
                                borderColor: 'rgb(34, 197, 94)',
                                borderWidth: 1,
                            },
                            {
                                label: 'Expenses',
                                data: @json($chartData['expenses']),
                                backgroundColor: 'rgba(239, 68, 68, 0.7)',
                                borderColor: 'rgb(239, 68, 68)',
                                borderWidth: 1,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: { beginAtZero: true }
                        }
                    }
                });
            }
        }
    });
</script>
