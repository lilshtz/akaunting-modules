@php
    $productJson = $products->values()->toJson();
    $contactJson = $contacts->values()->toJson();
@endphp

<x-layouts.admin>
    <x-slot name="title">
        {{ trans('pos::general.pos_terminal') }}
    </x-slot>

    <div class="grid gap-6 lg:grid-cols-[1.5fr,1fr]">
        <section class="rounded-xl bg-white p-6 shadow-sm">
            <div class="mb-4 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">{{ trans('pos::general.pos_terminal') }}</h1>
                    <p class="text-sm text-gray-500">{{ trans('pos::general.barcode_hint') }}</p>
                </div>
                <div class="flex gap-2">
                    <button type="button" id="new-pos-tab" class="rounded-lg bg-black px-4 py-2 text-sm font-medium text-white">
                        {{ trans('pos::general.new_tab') }}
                    </button>
                    <a href="{{ route('pos.orders.history') }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700">
                        {{ trans('pos::general.order_history') }}
                    </a>
                </div>
            </div>

            <div class="mb-4">
                <input id="product-search" type="text" class="w-full rounded-lg border border-gray-200 px-4 py-3" placeholder="{{ trans('pos::general.search_products') }}">
            </div>

            <div id="tab-strip" class="mb-4 flex flex-wrap gap-2"></div>

            <div id="product-grid" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3"></div>
        </section>

        <section class="rounded-xl bg-white p-6 shadow-sm">
            <form method="POST" action="{{ route('pos.orders.store') }}" id="pos-checkout-form">
                @csrf

                <div class="mb-4">
                    <label class="mb-2 block text-sm font-medium text-gray-700">{{ trans('pos::general.search_customer') }}</label>
                    <select name="contact_id" id="contact-select" class="w-full rounded-lg border border-gray-200 px-4 py-3">
                        <option value="">{{ trans('pos::general.walk_in_customer') }}</option>
                        @foreach ($contacts as $contact)
                            <option value="{{ $contact->id }}">{{ $contact->name }}@if($contact->email) ({{ $contact->email }}) @endif</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label class="mb-2 block text-sm font-medium text-gray-700">{{ trans('pos::general.tab_name') }}</label>
                    <input type="text" name="tab_name" id="tab-name" class="w-full rounded-lg border border-gray-200 px-4 py-3" placeholder="Walk-in #1">
                </div>

                <div class="overflow-hidden rounded-xl border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left">{{ trans('pos::general.products') }}</th>
                                <th class="px-3 py-2 text-right">Qty</th>
                                <th class="px-3 py-2 text-right">Price</th>
                                <th class="px-3 py-2 text-right">{{ trans('pos::general.discount') }}</th>
                                <th class="px-3 py-2 text-right">{{ trans('pos::general.tax') }}</th>
                                <th class="px-3 py-2 text-right">{{ trans('pos::general.total') }}</th>
                            </tr>
                        </thead>
                        <tbody id="basket-body" class="divide-y divide-gray-100"></tbody>
                    </table>
                </div>

                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">{{ trans('pos::general.default_payment_method') }}</label>
                        <select name="payment_method" id="payment-method" class="w-full rounded-lg border border-gray-200 px-4 py-3">
                            @foreach (['cash', 'card', 'split'] as $method)
                                <option value="{{ $method }}" @selected($setting->default_payment_method === $method)>{{ trans('pos::general.payment_methods.' . $method) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">{{ trans('pos::general.paid_amount') }}</label>
                        <input type="number" min="0" step="0.01" name="paid_amount" id="paid-amount" class="w-full rounded-lg border border-gray-200 px-4 py-3" value="0">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">{{ trans('pos::general.split_bill') }}</label>
                        <input type="number" min="1" max="20" name="split_count" id="split-count" class="w-full rounded-lg border border-gray-200 px-4 py-3" value="1">
                    </div>
                    <div class="rounded-lg bg-gray-50 p-4 text-sm text-gray-600">
                        <div class="flex justify-between"><span>{{ trans('pos::general.change_amount') }}</span><strong id="change-amount">0.00</strong></div>
                        <div class="mt-2 flex justify-between"><span>{{ trans('pos::general.split_bill') }}</span><strong id="split-amount">0.00</strong></div>
                    </div>
                </div>

                <div class="mt-4 space-y-2 rounded-xl bg-gray-50 p-4">
                    <div class="flex justify-between text-sm"><span>{{ trans('pos::general.subtotal') }}</span><strong id="subtotal-value">0.00</strong></div>
                    <div class="flex justify-between text-sm"><span>{{ trans('pos::general.discount') }}</span><strong id="discount-value">0.00</strong></div>
                    <div class="flex justify-between text-sm"><span>{{ trans('pos::general.tax') }}</span><strong id="tax-value">0.00</strong></div>
                    <div class="flex justify-between text-base font-semibold"><span>{{ trans('pos::general.total') }}</span><strong id="total-value">0.00</strong></div>
                </div>

                <input type="hidden" name="items_json" id="items-json">

                <button type="submit" class="mt-4 w-full rounded-lg bg-emerald-600 px-4 py-3 text-sm font-semibold text-white">
                    {{ trans('pos::general.process_payment') }}
                </button>
            </form>
        </section>
    </div>

    <section class="mt-6 rounded-xl bg-white p-6 shadow-sm">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold">{{ trans('pos::general.recent_orders') }}</h2>
            <span class="text-sm text-gray-500">{{ trans('pos::general.messages.open_second_customer') }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left">#</th>
                        <th class="px-4 py-3 text-left">{{ trans('pos::general.customer') }}</th>
                        <th class="px-4 py-3 text-left">{{ trans('pos::general.total') }}</th>
                        <th class="px-4 py-3 text-left">{{ trans('pos::general.default_payment_method') }}</th>
                        <th class="px-4 py-3 text-left">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($recentOrders as $order)
                        <tr>
                            <td class="px-4 py-3"><a class="text-emerald-700 hover:underline" href="{{ route('pos.orders.show', $order->id) }}">{{ $order->order_number }}</a></td>
                            <td class="px-4 py-3">{{ $order->contact_id ? $order->contact->name : trans('pos::general.walk_in_customer') }}</td>
                            <td class="px-4 py-3">@money($order->total, setting('default.currency', 'USD'), true)</td>
                            <td class="px-4 py-3">{{ $order->payment_method_label }}</td>
                            <td class="px-4 py-3">{{ $order->status_label }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <script>
        (() => {
            const products = {!! $productJson !!};
            const contacts = {!! $contactJson !!};
            const storageKey = 'akaunting-pos-tabs';
            const searchInput = document.getElementById('product-search');
            const tabStrip = document.getElementById('tab-strip');
            const productGrid = document.getElementById('product-grid');
            const basketBody = document.getElementById('basket-body');
            const itemsJson = document.getElementById('items-json');
            const subtotalValue = document.getElementById('subtotal-value');
            const discountValue = document.getElementById('discount-value');
            const taxValue = document.getElementById('tax-value');
            const totalValue = document.getElementById('total-value');
            const paidAmount = document.getElementById('paid-amount');
            const changeAmount = document.getElementById('change-amount');
            const splitCount = document.getElementById('split-count');
            const splitAmount = document.getElementById('split-amount');
            const tabName = document.getElementById('tab-name');
            const contactSelect = document.getElementById('contact-select');
            const newTabButton = document.getElementById('new-pos-tab');

            let state = loadState();

            function loadState() {
                const stored = window.localStorage.getItem(storageKey);
                if (stored) {
                    try {
                        return JSON.parse(stored);
                    } catch (error) {
                        window.localStorage.removeItem(storageKey);
                    }
                }

                return {
                    active: 'tab-1',
                    tabs: {
                        'tab-1': {name: 'Walk-in #1', contact_id: '', items: []}
                    }
                };
            }

            function saveState() {
                window.localStorage.setItem(storageKey, JSON.stringify(state));
            }

            function activeTab() {
                return state.tabs[state.active];
            }

            function persistCurrentTab() {
                const tab = activeTab();
                if (!tab) {
                    return;
                }

                tab.name = tabName.value || tab.name;
                tab.contact_id = contactSelect.value;
                saveState();
            }

            function renderTabs() {
                tabStrip.innerHTML = '';
                Object.entries(state.tabs).forEach(([id, tab]) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'rounded-full px-4 py-2 text-sm ' + (id === state.active ? 'bg-black text-white' : 'bg-gray-100 text-gray-700');
                    button.textContent = tab.name;
                    button.addEventListener('click', () => {
                        persistCurrentTab();
                        state.active = id;
                        render();
                    });
                    tabStrip.appendChild(button);
                });
            }

            function renderProducts() {
                const term = searchInput.value.trim().toLowerCase();
                const visible = products.filter((product) => {
                    return !term
                        || product.name.toLowerCase().includes(term)
                        || (product.description || '').toLowerCase().includes(term)
                        || String(product.id).includes(term)
                        || (product.sku || '').toLowerCase().includes(term);
                });

                productGrid.innerHTML = '';

                visible.forEach((product) => {
                    const card = document.createElement('button');
                    card.type = 'button';
                    card.className = 'rounded-xl border border-gray-200 p-4 text-left hover:border-emerald-500';
                    card.innerHTML = `
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="font-semibold">${product.name}</div>
                                <div class="mt-1 text-xs text-gray-500">${product.description || ''}</div>
                                <div class="mt-2 text-sm text-gray-600">${product.sku || 'Item #' + product.id}</div>
                            </div>
                            <div class="text-right">
                                <div class="font-semibold">${Number(product.sale_price || 0).toFixed(2)}</div>
                                <div class="mt-1 text-xs text-gray-500">${product.stock_level === null ? '' : '{{ trans('pos::general.stock_level') }}: ' + Number(product.stock_level).toFixed(2)}</div>
                            </div>
                        </div>
                    `;
                    card.addEventListener('click', () => addProduct(product));
                    productGrid.appendChild(card);
                });
            }

            function addProduct(product) {
                const tab = activeTab();
                const existing = tab.items.find((item) => item.item_id === product.id);

                if (existing) {
                    existing.quantity += 1;
                } else {
                    tab.items.push({
                        item_id: product.id,
                        name: product.name,
                        sku: product.sku || '',
                        quantity: 1,
                        price: Number(product.sale_price || 0),
                        discount: 0,
                        tax: 0
                    });
                }

                saveState();
                renderBasket();
            }

            function renderBasket() {
                const tab = activeTab();
                basketBody.innerHTML = '';

                tab.items.forEach((item, index) => {
                    const lineTotal = (item.quantity * item.price) - (item.quantity * item.discount) + (item.quantity * item.tax);
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td class="px-3 py-3">
                            <div class="font-medium">${item.name}</div>
                            <div class="text-xs text-gray-500">${item.sku || ''}</div>
                        </td>
                        <td class="px-3 py-3 text-right"><input data-field="quantity" data-index="${index}" class="w-20 rounded border border-gray-200 px-2 py-1 text-right" type="number" min="0" step="0.01" value="${item.quantity}"></td>
                        <td class="px-3 py-3 text-right"><input data-field="price" data-index="${index}" class="w-24 rounded border border-gray-200 px-2 py-1 text-right" type="number" min="0" step="0.01" value="${item.price}"></td>
                        <td class="px-3 py-3 text-right"><input data-field="discount" data-index="${index}" class="w-24 rounded border border-gray-200 px-2 py-1 text-right" type="number" min="0" step="0.01" value="${item.discount}"></td>
                        <td class="px-3 py-3 text-right"><input data-field="tax" data-index="${index}" class="w-24 rounded border border-gray-200 px-2 py-1 text-right" type="number" min="0" step="0.01" value="${item.tax}"></td>
                        <td class="px-3 py-3 text-right">
                            <div>${lineTotal.toFixed(2)}</div>
                            <button data-remove="${index}" type="button" class="mt-1 text-xs text-red-600">Remove</button>
                        </td>
                    `;
                    basketBody.appendChild(row);
                });

                basketBody.querySelectorAll('input[data-index]').forEach((input) => {
                    input.addEventListener('input', (event) => {
                        const current = tab.items[Number(event.target.dataset.index)];
                        current[event.target.dataset.field] = Number(event.target.value || 0);
                        saveState();
                        renderBasket();
                    });
                });

                basketBody.querySelectorAll('button[data-remove]').forEach((button) => {
                    button.addEventListener('click', (event) => {
                        tab.items.splice(Number(event.target.dataset.remove), 1);
                        saveState();
                        renderBasket();
                    });
                });

                syncInputs();
            }

            function syncInputs() {
                const tab = activeTab();
                tabName.value = tab.name;
                contactSelect.value = tab.contact_id || '';
                itemsJson.value = JSON.stringify(tab.items);

                const totals = tab.items.reduce((carry, item) => {
                    carry.subtotal += item.quantity * item.price;
                    carry.discount += item.quantity * item.discount;
                    carry.tax += item.quantity * item.tax;
                    carry.total += (item.quantity * item.price) - (item.quantity * item.discount) + (item.quantity * item.tax);
                    return carry;
                }, {subtotal: 0, discount: 0, tax: 0, total: 0});

                subtotalValue.textContent = totals.subtotal.toFixed(2);
                discountValue.textContent = totals.discount.toFixed(2);
                taxValue.textContent = totals.tax.toFixed(2);
                totalValue.textContent = totals.total.toFixed(2);
                changeAmount.textContent = Math.max(0, Number(paidAmount.value || 0) - totals.total).toFixed(2);
                splitAmount.textContent = (totals.total / Math.max(1, Number(splitCount.value || 1))).toFixed(2);

                saveState();
                renderTabs();
            }

            function render() {
                renderTabs();
                renderProducts();
                renderBasket();
            }

            newTabButton.addEventListener('click', () => {
                persistCurrentTab();
                const nextIndex = Object.keys(state.tabs).length + 1;
                const id = 'tab-' + Date.now();
                state.tabs[id] = {name: 'Walk-in #' + nextIndex, contact_id: '', items: []};
                state.active = id;
                saveState();
                render();
            });

            searchInput.addEventListener('input', renderProducts);
            paidAmount.addEventListener('input', syncInputs);
            splitCount.addEventListener('input', syncInputs);
            tabName.addEventListener('input', () => {
                activeTab().name = tabName.value || activeTab().name;
                syncInputs();
            });
            contactSelect.addEventListener('change', () => {
                activeTab().contact_id = contactSelect.value;
                syncInputs();
            });

            document.getElementById('pos-checkout-form').addEventListener('submit', () => {
                persistCurrentTab();
                itemsJson.value = JSON.stringify(activeTab().items);
                saveState();
            });

            render();
        })();
    </script>
</x-layouts.admin>
