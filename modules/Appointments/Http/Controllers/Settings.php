<?php

namespace Modules\Appointments\Http\Controllers;

use App\Abstracts\Http\Controller;
use Modules\Appointments\Http\Requests\SettingsUpdate;
use Modules\Appointments\Services\LeaveBalanceService;

class Settings extends Controller
{
    public function __construct(protected LeaveBalanceService $balances)
    {
    }

    public function edit()
    {
        $leaveTypes = $this->balances->labels();
        $leaveAllowances = $this->balances->allowances();

        return view('appointments::settings.index', compact('leaveTypes', 'leaveAllowances'));
    }

    public function update(SettingsUpdate $request)
    {
        setting(['appointments.leave_types' => json_encode($request->get('leave_types', []))]);
        setting(['appointments.leave_allowances' => json_encode($request->get('leave_allowances', []))]);
        setting()->save();

        flash(trans('messages.success.updated', ['type' => trans('appointments::general.settings')]))->success();

        return redirect()->route('appointments.settings.edit');
    }
}
