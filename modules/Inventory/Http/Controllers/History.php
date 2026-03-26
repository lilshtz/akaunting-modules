<?php

namespace Modules\Inventory\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Inventory\Models\History as HistoryModel;

class History extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = HistoryModel::where('company_id', company_id())
            ->with(['item', 'warehouse']);

        if ($request->filled('item_id')) {
            $query->where('item_id', (int) $request->get('item_id'));
        }

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', (int) $request->get('warehouse_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->get('type'));
        }

        if ($request->filled('reference_id')) {
            $query->where('reference_id', (int) $request->get('reference_id'));
        }

        return response()->json([
            'data' => $query->orderByDesc('date')->orderByDesc('id')->get(),
        ]);
    }
}
