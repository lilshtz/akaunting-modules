<div>
    {{-- Income vs Expense Chart --}}
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <h3 class="text-lg font-semibold mb-4">Income vs Expenses (Last 12 Months)</h3>
        <div style="height: 250px;">
            <canvas id="de-income-expense-chart"></canvas>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Top 5 Accounts by Balance --}}
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-sm font-semibold mb-4">Top 5 Accounts by Balance</h3>
            @if (count($topAccounts) > 0)
                <table class="w-full">
                    <thead>
                        <tr class="border-b">
                            <th class="text-left text-xs font-medium text-gray-500 pb-2">Account</th>
                            <th class="text-left text-xs font-medium text-gray-500 pb-2">Type</th>
                            <th class="text-right text-xs font-medium text-gray-500 pb-2">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($topAccounts as $acct)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="py-2 text-sm">{{ $acct['code'] }} - {{ $acct['name'] }}</td>
                                <td class="py-2 text-sm capitalize text-gray-500">{{ $acct['type'] }}</td>
                                <td class="py-2 text-sm text-right {{ $acct['balance'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ number_format($acct['balance'], 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-sm text-gray-500">No account balances to display.</p>
            @endif
        </div>

        {{-- Recent Journal Entries (Last 10) --}}
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-sm font-semibold mb-4">Recent Journal Entries</h3>
            @if ($recentEntries->count() > 0)
                <table class="w-full">
                    <thead>
                        <tr class="border-b">
                            <th class="text-left text-xs font-medium text-gray-500 pb-2">Date</th>
                            <th class="text-left text-xs font-medium text-gray-500 pb-2">Reference</th>
                            <th class="text-left text-xs font-medium text-gray-500 pb-2">Status</th>
                            <th class="text-right text-xs font-medium text-gray-500 pb-2">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentEntries as $entry)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="py-2 text-sm">{{ $entry->date->format('Y-m-d') }}</td>
                                <td class="py-2 text-sm">
                                    <a href="{{ route('double-entry.journals.show', $entry->id) }}" class="text-purple-700 hover:underline">
                                        {{ $entry->reference ?: '#' . $entry->id }}
                                    </a>
                                </td>
                                <td class="py-2 text-sm">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $entry->status === 'posted' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                        {{ ucfirst($entry->status) }}
                                    </span>
                                </td>
                                <td class="py-2 text-sm text-right">{{ number_format($entry->total_debit, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-sm text-gray-500">No journal entries yet.</p>
            @endif
        </div>
    </div>

    {{-- Quick Links --}}
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-sm font-semibold mb-4">Quick Links</h3>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
            <a href="{{ route('double-entry.accounts.index') }}" class="flex items-center gap-2 p-3 rounded-lg border hover:bg-purple-50 text-sm">
                <span class="material-icons text-purple-700" style="font-size: 20px;">account_tree</span>
                Chart of Accounts
            </a>
            <a href="{{ route('double-entry.journals.index') }}" class="flex items-center gap-2 p-3 rounded-lg border hover:bg-purple-50 text-sm">
                <span class="material-icons text-purple-700" style="font-size: 20px;">book</span>
                Journal Entries
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
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('de-income-expense-chart');
        if (!ctx) return;

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
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString();
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                            }
                        }
                    }
                }
            }
        });
    });
</script>
