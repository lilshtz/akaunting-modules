<?php

namespace Modules\BankFeeds\Http\Controllers\Api;

use App\Abstracts\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\BankFeeds\Models\BankFeedImport;
use Modules\BankFeeds\Models\BankFeedTransaction;

class BankFeeds extends Controller
{
    public function imports(): JsonResponse
    {
        $imports = BankFeedImport::where('company_id', company_id())
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return response()->json($imports);
    }

    public function transactions(Request $request): JsonResponse
    {
        $query = BankFeedTransaction::whereHas('import', function ($q) {
            $q->where('company_id', company_id());
        });

        if ($request->has('import_id')) {
            $query->where('import_id', $request->get('import_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        $transactions = $query->with(['category'])
            ->orderBy('date', 'desc')
            ->paginate(50);

        return response()->json($transactions);
    }

    public function show(int $id): JsonResponse
    {
        $transaction = BankFeedTransaction::whereHas('import', function ($q) {
            $q->where('company_id', company_id());
        })->with(['category', 'import'])->findOrFail($id);

        return response()->json($transaction);
    }
}
