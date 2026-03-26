<?php

namespace Modules\PaypalSync\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\PaypalSync\Models\PaypalSyncSettings;
use Modules\PaypalSync\Models\PaypalSyncTransaction;
use Modules\PaypalSync\Services\PaypalService;
use Modules\PaypalSync\Services\TransactionSyncService;

class Transactions extends Controller
{
    /**
     * Display a listing of PayPal transactions.
     *
     * @return Response
     */
    public function index()
    {
        $transactions = PaypalSyncTransaction::where('company_id', company_id())
            ->orderBy('date', 'desc')
            ->paginate(25);

        return view('paypal-sync::transactions', compact('transactions'));
    }

    /**
     * Trigger a manual sync of PayPal transactions.
     *
     * @param Request $request
     * @return Response
     */
    public function sync(Request $request)
    {
        $settings = PaypalSyncSettings::where('company_id', company_id())
            ->where('enabled', true)
            ->first();

        if (!$settings) {
            $message = trans('paypal-sync::general.sync_error', ['error' => 'PayPal Sync is not configured or is disabled.']);

            flash($message)->error();

            return response()->json([
                'success' => false,
                'error' => true,
                'message' => $message,
                'redirect' => route('paypal-sync.settings.edit'),
            ]);
        }

        try {
            $paypalService = new PaypalService($settings);
            $syncService = new TransactionSyncService($paypalService, $settings);
            $result = $syncService->sync();

            $message = trans('paypal-sync::general.sync_success', [
                'imported' => $result['imported'],
                'skipped' => $result['skipped'],
            ]);

            flash($message)->success();

            return response()->json([
                'success' => true,
                'error' => false,
                'message' => $message,
                'redirect' => route('paypal-sync.transactions.index'),
            ]);
        } catch (\Exception $e) {
            $message = trans('paypal-sync::general.sync_error', ['error' => $e->getMessage()]);

            flash($message)->error();

            return response()->json([
                'success' => false,
                'error' => true,
                'message' => $message,
                'redirect' => route('paypal-sync.transactions.index'),
            ]);
        }
    }

    /**
     * Manually match a PayPal transaction to an Akaunting invoice.
     *
     * @param PaypalSyncTransaction $transaction
     * @param Request $request
     * @return Response
     */
    public function match(PaypalSyncTransaction $transaction, Request $request)
    {
        $request->validate([
            'document_id' => 'required|integer|exists:documents,id',
        ]);

        if (!$transaction->bank_transaction_id) {
            return response()->json([
                'success' => false,
                'error' => true,
                'message' => 'No bank transaction linked to this PayPal transaction.',
            ]);
        }

        $bankTransaction = $transaction->bankTransaction;

        if ($bankTransaction) {
            $bankTransaction->update([
                'document_id' => $request->input('document_id'),
            ]);
        }

        $message = trans('messages.success.updated', ['type' => trans('paypal-sync::general.transaction_id')]);

        flash($message)->success();

        return response()->json([
            'success' => true,
            'error' => false,
            'message' => $message,
            'redirect' => route('paypal-sync.transactions.index'),
        ]);
    }
}
