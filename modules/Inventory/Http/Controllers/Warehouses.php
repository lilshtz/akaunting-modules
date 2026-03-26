<?php

namespace Modules\Inventory\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Inventory\Http\Requests\WarehouseStore;
use Modules\Inventory\Http\Requests\WarehouseUpdate;
use Modules\Inventory\Models\Warehouse;

class Warehouses extends Controller
{
    public function index(): JsonResponse
    {
        $warehouses = Warehouse::where('company_id', company_id())
            ->withCount(['stock', 'histories'])
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $warehouses]);
    }

    public function create(): JsonResponse
    {
        return response()->json([
            'data' => [
                'defaults' => [
                    'enabled' => true,
                ],
            ],
        ]);
    }

    public function store(WarehouseStore $request): JsonResponse
    {
        $warehouse = Warehouse::create([
            'company_id' => company_id(),
            'name' => $request->get('name'),
            'address' => $request->get('address'),
            'email' => $request->get('email'),
            'phone' => $request->get('phone'),
            'enabled' => $request->boolean('enabled', true),
        ]);

        return response()->json([
            'message' => trans('messages.success.added', ['type' => $warehouse->name]),
            'data' => $warehouse,
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $warehouse = Warehouse::where('company_id', company_id())
            ->with(['stock.item', 'histories'])
            ->findOrFail($id);

        return response()->json(['data' => $warehouse]);
    }

    public function edit(int $id): JsonResponse
    {
        return $this->show($id);
    }

    public function update(int $id, WarehouseUpdate $request): JsonResponse
    {
        $warehouse = Warehouse::where('company_id', company_id())->findOrFail($id);

        $warehouse->update([
            'name' => $request->get('name'),
            'address' => $request->get('address'),
            'email' => $request->get('email'),
            'phone' => $request->get('phone'),
            'enabled' => $request->boolean('enabled', $warehouse->enabled),
        ]);

        return response()->json([
            'message' => trans('messages.success.updated', ['type' => $warehouse->name]),
            'data' => $warehouse->fresh(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $warehouse = Warehouse::where('company_id', company_id())->findOrFail($id);
        $name = $warehouse->name;

        $warehouse->delete();

        return response()->json([
            'message' => trans('messages.success.deleted', ['type' => $name]),
        ]);
    }
}
