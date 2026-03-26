<?php

namespace Modules\CreditDebitNotes\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Request;
use Modules\CreditDebitNotes\Models\NotePortalToken;

class Portal extends Controller
{
    public function show(string $token)
    {
        $portalToken = NotePortalToken::where('token', $token)
            ->firstOrFail();

        $portalToken->markViewed();

        $document = $portalToken->document()
            ->with(['contact', 'items.taxes', 'totals', 'company'])
            ->firstOrFail();

        $type = $document->type;

        return view('credit-debit-notes::portal.show', compact('document', 'token', 'type'));
    }
}
