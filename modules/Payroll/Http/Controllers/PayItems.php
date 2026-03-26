<?php

namespace Modules\Payroll\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Request;
use Modules\Payroll\Http\Requests\PayItemStore;
use Modules\Payroll\Http\Requests\PayItemUpdate;
use Modules\Payroll\Models\PayItem;

class PayItems extends Controller
{
    public function index(Request $request)
    {
        $query = PayItem::where('company_id', company_id())->orderBy('type')->orderBy('name');

        if ($request->filled('type')) {
            $query->type($request->get('type'));
        }

        $items = $query->paginate(25);

        return view('payroll::pay-items.index', compact('items'));
    }

    public function create()
    {
        return view('payroll::pay-items.create');
    }

    public function store(PayItemStore $request)
    {
        PayItem::create([
            'company_id' => company_id(),
            'type' => $request->get('type'),
            'name' => $request->get('name'),
            'default_amount' => $request->get('default_amount'),
            'is_percentage' => $request->has('is_percentage'),
            'enabled' => $request->has('enabled'),
        ]);

        flash(trans('messages.success.added', ['type' => trans('payroll::general.pay_item')]))->success();

        return redirect()->route('payroll.pay-items.index');
    }

    public function edit(int $id)
    {
        $item = PayItem::where('company_id', company_id())->findOrFail($id);

        return view('payroll::pay-items.edit', compact('item'));
    }

    public function update(int $id, PayItemUpdate $request)
    {
        $item = PayItem::where('company_id', company_id())->findOrFail($id);

        $item->update([
            'type' => $request->get('type'),
            'name' => $request->get('name'),
            'default_amount' => $request->get('default_amount'),
            'is_percentage' => $request->has('is_percentage'),
            'enabled' => $request->has('enabled'),
        ]);

        flash(trans('messages.success.updated', ['type' => trans('payroll::general.pay_item')]))->success();

        return redirect()->route('payroll.pay-items.index');
    }

    public function destroy(int $id)
    {
        $item = PayItem::where('company_id', company_id())->findOrFail($id);
        $item->delete();

        flash(trans('messages.success.deleted', ['type' => trans('payroll::general.pay_item')]))->success();

        return redirect()->route('payroll.pay-items.index');
    }
}
