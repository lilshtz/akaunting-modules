<?php

namespace Modules\DoubleEntry\Http\Controllers;

use App\Abstracts\Http\Controller;

class GeneralLedger extends Controller
{
    public function index()
    {
        return view('double-entry::placeholder', [
            'title' => trans('double-entry::general.general_ledger'),
            'message' => trans('double-entry::general.coming_soon', ['feature' => trans('double-entry::general.general_ledger')]),
        ]);
    }
}
