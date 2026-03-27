<?php

namespace Modules\Pos\Http\Controllers;

use App\Abstracts\Http\Controller;
use App\Models\Common\Contact;
use App\Models\Common\Item;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Modules\Pos\Http\Requests\OrderRefund;
use Modules\Pos\Http\Requests\OrderStore;
use Modules\Pos\Models\PosOrder;
use Modules\Pos\Services\PosInventoryService;
use Modules\Pos\Services\PosOrderService;
use Modules\Pos\Services\PosReceiptService;

class Orders extends Controller
{
    public function __construct(
        protected PosOrderService $orders,
        protected PosReceiptService $receipts,
        protected PosInventoryService $inventory
    ) {
    }

    public function index(Request $request): Response|mixed
    {
        $products = Item::query()
            ->where('company_id', company_id())
            ->where('enabled', true)
            ->orderBy('name')
            ->get(['id', 'name', 'description', 'sale_price']);

        $stockLevels = $this->inventory->stockLevels($products);
        $variantSkus = $this->variantSkus($products->pluck('id')->all());
        $contacts = Contact::query()
            ->withoutGlobalScopes()
            ->where('company_id', company_id())
            ->where('type', Contact::CUSTOMER_TYPE)
            ->where('enabled', true)
            ->orderBy('name')
            ->limit(100)
            ->get(['id', 'name', 'email', 'phone']);

        $products = $products->map(function ($product) use ($stockLevels, $variantSkus) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'sale_price' => (float) $product->sale_price,
                'stock_level' => $stockLevels[$product->id] ?? null,
                'sku' => $variantSkus[$product->id] ?? null,
            ];
        });

        $recentOrders = PosOrder::ownedByCompany()
            ->with('items')
            ->latest()
            ->limit(10)
            ->get();
        $setting = $this->orders->settings();

        return view('pos::orders.index', compact('products', 'contacts', 'recentOrders', 'setting'));
    }

    public function store(OrderStore $request): Response|mixed
    {
        $order = $this->orders->create($request->validated());

        flash(trans('messages.success.added', ['type' => trans('pos::general.order')]))->success();

        return redirect()->route('pos.orders.show', $order->id);
    }

    public function history(Request $request): Response|mixed
    {
        $query = PosOrder::ownedByCompany()->with(['contact', 'items']);

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($builder) use ($search) {
                $builder->where('order_number', 'like', '%' . $search . '%')
                    ->orWhere('tab_name', 'like', '%' . $search . '%')
                    ->orWhereHas('contact', fn ($contact) => $contact->where('name', 'like', '%' . $search . '%'))
                    ->orWhereHas('items', function ($items) use ($search) {
                        $items->where('name', 'like', '%' . $search . '%')
                            ->orWhere('sku', 'like', '%' . $search . '%');
                    });
            });
        }

        $orders = $query->latest()->paginate(20);
        $statuses = collect([
            PosOrder::STATUS_COMPLETED,
            PosOrder::STATUS_REFUNDED,
            PosOrder::STATUS_CANCELLED,
        ])->mapWithKeys(fn ($status) => [$status => trans('pos::general.statuses.' . $status)]);

        return view('pos::orders.history', compact('orders', 'statuses'));
    }

    public function show(int $id): Response|mixed
    {
        $order = PosOrder::ownedByCompany()
            ->with(['items', 'contact'])
            ->findOrFail($id);
        $setting = $this->orders->settings();
        $receipt = $this->receipts->receiptData($order, $setting);

        return view('pos::orders.show', compact('order', 'setting', 'receipt'));
    }

    public function refund(int $id, OrderRefund $request): Response|mixed
    {
        $order = PosOrder::ownedByCompany()
            ->with('items')
            ->findOrFail($id);
        $refund = $this->orders->refund($order, $request->validated('items', []));

        flash(trans('pos::general.messages.refund_created', ['order' => $refund->order_number]))->success();

        return redirect()->route('pos.orders.show', $refund->id);
    }

    public function receipt(int $id): Response|mixed
    {
        $order = PosOrder::ownedByCompany()
            ->with(['items', 'contact'])
            ->findOrFail($id);
        $setting = $this->orders->settings();
        $receipt = $this->receipts->receiptData($order, $setting);

        return view('pos::orders.receipt', compact('order', 'setting', 'receipt'));
    }

    protected function variantSkus(array $itemIds): array
    {
        if (! class_exists(\Modules\Inventory\Models\Variant::class) || empty($itemIds)) {
            return [];
        }

        if (! DB::getSchemaBuilder()->hasTable('inventory_variants')) {
            return [];
        }

        return DB::table('inventory_variants')
            ->select('item_id', DB::raw('MIN(sku) as sku'))
            ->whereIn('item_id', $itemIds)
            ->groupBy('item_id')
            ->pluck('sku', 'item_id')
            ->all();
    }
}
