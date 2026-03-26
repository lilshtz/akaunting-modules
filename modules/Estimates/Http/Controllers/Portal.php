<?php

namespace Modules\Estimates\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Estimates\Models\Estimate;
use Modules\Estimates\Models\EstimateHistory;
use Modules\Estimates\Models\EstimatePortalToken;
use Modules\Estimates\Notifications\EstimateStatusChanged;

class Portal extends Controller
{
    public function show(string $token)
    {
        $portalToken = EstimatePortalToken::where('token', $token)
            ->firstOrFail();

        $estimate = Estimate::withoutGlobalScopes()
            ->where('id', $portalToken->document_id)
            ->where('type', Estimate::ESTIMATE_TYPE)
            ->with(['items.taxes', 'totals', 'company'])
            ->firstOrFail();

        // Mark as viewed
        if (! $portalToken->viewed_at) {
            $portalToken->markViewed();

            if ($estimate->status === Estimate::STATUS_SENT) {
                $estimate->update(['status' => Estimate::STATUS_VIEWED]);

                EstimateHistory::create([
                    'company_id' => $estimate->company_id,
                    'document_id' => $estimate->id,
                    'status' => Estimate::STATUS_VIEWED,
                    'description' => trans('estimates::general.messages.viewed_by_customer'),
                ]);

                $this->notifyCompany($estimate, 'viewed');
            }
        }

        $estimate->checkExpiry();

        return view('estimates::portal.show', compact('estimate', 'token'));
    }

    public function approve(Request $request, string $token)
    {
        $portalToken = EstimatePortalToken::where('token', $token)->firstOrFail();

        $estimate = Estimate::withoutGlobalScopes()
            ->where('id', $portalToken->document_id)
            ->where('type', Estimate::ESTIMATE_TYPE)
            ->firstOrFail();

        if (! in_array($estimate->status, [Estimate::STATUS_SENT, Estimate::STATUS_VIEWED])) {
            return redirect()->route('estimates.portal.show', $token)
                ->with('error', trans('estimates::general.messages.cannot_approve'));
        }

        $estimate->update(['status' => Estimate::STATUS_APPROVED]);

        EstimateHistory::create([
            'company_id' => $estimate->company_id,
            'document_id' => $estimate->id,
            'status' => Estimate::STATUS_APPROVED,
            'description' => trans('estimates::general.messages.approved_by_customer'),
        ]);

        $this->notifyCompany($estimate, 'approved');

        return redirect()->route('estimates.portal.show', $token)
            ->with('success', trans('estimates::general.messages.thank_you_approved'));
    }

    public function refuse(Request $request, string $token)
    {
        $portalToken = EstimatePortalToken::where('token', $token)->firstOrFail();

        $estimate = Estimate::withoutGlobalScopes()
            ->where('id', $portalToken->document_id)
            ->where('type', Estimate::ESTIMATE_TYPE)
            ->firstOrFail();

        if (! in_array($estimate->status, [Estimate::STATUS_SENT, Estimate::STATUS_VIEWED])) {
            return redirect()->route('estimates.portal.show', $token)
                ->with('error', trans('estimates::general.messages.cannot_refuse'));
        }

        $reason = $request->get('reason', '');

        $estimate->update(['status' => Estimate::STATUS_REFUSED]);

        EstimateHistory::create([
            'company_id' => $estimate->company_id,
            'document_id' => $estimate->id,
            'status' => Estimate::STATUS_REFUSED,
            'description' => trans('estimates::general.messages.refused_by_customer') .
                ($reason ? ': ' . $reason : ''),
        ]);

        $this->notifyCompany($estimate, 'refused', $reason);

        return redirect()->route('estimates.portal.show', $token)
            ->with('success', trans('estimates::general.messages.thank_you_refused'));
    }

    protected function notifyCompany(Estimate $estimate, string $action, string $reason = ''): void
    {
        try {
            $company = $estimate->company;
            if ($company && $company->owner) {
                $company->owner->notify(new EstimateStatusChanged($estimate, $action, $reason));
            }
        } catch (\Exception $e) {
            report($e);
        }
    }
}
