<?php

namespace Modules\Stripe\Http\Controllers;

use App\Abstracts\Http\Controller;
use Modules\Stripe\Models\StripePayment;
use Modules\Stripe\Models\StripeSettings;
use Modules\Stripe\Services\StripeService;

class PaymentHistory extends Controller
{
    /**
     * Display a listing of Stripe payments for the current company.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $payments = StripePayment::where('company_id', company_id())
            ->with('document')
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return view('stripe::history', compact('payments'));
    }

    /**
     * Initiate a refund for a Stripe payment.
     *
     * @param  StripePayment  $payment
     * @return \Illuminate\Http\JsonResponse
     */
    public function refund(StripePayment $payment)
    {
        if ($payment->company_id !== company_id()) {
            abort(403);
        }

        if ($payment->status === 'refunded') {
            $message = trans('stripe::general.payment_failed');

            flash($message)->error();

            return response()->json([
                'success' => false,
                'error' => true,
                'message' => $message,
                'redirect' => route('stripe.payments.index'),
            ]);
        }

        try {
            $settings = StripeSettings::where('company_id', company_id())
                ->where('enabled', true)
                ->firstOrFail();

            $service = new StripeService($settings);

            if (empty($payment->stripe_charge_id)) {
                throw new \RuntimeException('No charge ID available for refund.');
            }

            $refund = $service->createRefund($payment->stripe_charge_id);

            $payment->update([
                'status' => 'refunded',
                'refund_id' => $refund['id'] ?? null,
            ]);

            $message = trans('messages.success.updated', ['type' => trans('stripe::general.refund')]);

            flash($message)->success();

            return response()->json([
                'success' => true,
                'error' => false,
                'message' => $message,
                'redirect' => route('stripe.payments.index'),
            ]);
        } catch (\Exception $e) {
            $message = $e->getMessage();

            flash($message)->error()->important();

            return response()->json([
                'success' => false,
                'error' => true,
                'message' => $message,
                'redirect' => route('stripe.payments.index'),
            ]);
        }
    }
}
