<?php

namespace Modules\Inventory\Http\Controllers;

use App\Abstracts\Http\Controller;
use App\Models\Common\Item;
use Illuminate\Http\JsonResponse;
use Modules\Inventory\Http\Requests\ItemGroupStore;
use Modules\Inventory\Http\Requests\ItemGroupUpdate;
use Modules\Inventory\Models\ItemGroup;

class ItemGroups extends Controller
{
    public function index(): JsonResponse
    {
        $groups = ItemGroup::where('company_id', company_id())
            ->with('items')
            ->withCount('items')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $groups]);
    }

    public function store(ItemGroupStore $request): JsonResponse
    {
        $group = ItemGroup::create([
            'company_id' => company_id(),
            'name' => $request->get('name'),
            'description' => $request->get('description'),
        ]);

        $group->items()->sync($this->validatedItemIds($request->get('item_ids', [])));

        return response()->json([
            'message' => trans('messages.success.added', ['type' => $group->name]),
            'data' => $group->load('items'),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $group = ItemGroup::where('company_id', company_id())
            ->with('items')
            ->findOrFail($id);

        return response()->json(['data' => $group]);
    }

    public function update(ItemGroupUpdate $request, int $id): JsonResponse
    {
        $group = ItemGroup::where('company_id', company_id())->findOrFail($id);
        $group->update([
            'name' => $request->get('name'),
            'description' => $request->get('description'),
        ]);
        $group->items()->sync($this->validatedItemIds($request->get('item_ids', [])));

        return response()->json([
            'message' => trans('messages.success.updated', ['type' => $group->name]),
            'data' => $group->fresh()->load('items'),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $group = ItemGroup::where('company_id', company_id())->findOrFail($id);
        $name = $group->name;
        $group->delete();

        return response()->json([
            'message' => trans('messages.success.deleted', ['type' => $name]),
        ]);
    }

    protected function validatedItemIds(array $itemIds): array
    {
        return Item::where('company_id', company_id())
            ->whereIn('id', $itemIds)
            ->pluck('id')
            ->all();
    }
}
