<?php

namespace Modules\DoubleEntry\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Request;

class AccountDefaults extends Controller
{
    public function index()
    {
        return view('double-entry::placeholder', [
            'title' => trans('double-entry::general.account_defaults'),
            'message' => trans('double-entry::general.coming_soon', ['feature' => trans('double-entry::general.account_defaults')]),
        ]);
    }

    public function store(Request $request)
    {
        return redirect()->route('double-entry.account-defaults.index');
    }
}
