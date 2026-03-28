<?php

namespace Modules\DoubleEntry\Http\Controllers;

use App\Abstracts\Http\Controller;

class Journals extends Controller
{
    public function index()
    {
        return $this->placeholder(trans('double-entry::general.journal_entries'));
    }

    public function create()
    {
        return $this->placeholder(trans('double-entry::general.journal_entries'));
    }

    public function store()
    {
        return redirect()->route('double-entry.journals.index');
    }

    public function show(int $id)
    {
        return $this->placeholder(trans('double-entry::general.journal_entries') . ' #' . $id);
    }

    public function edit(int $id)
    {
        return $this->placeholder(trans('double-entry::general.journal_entries') . ' #' . $id);
    }

    public function update(int $id)
    {
        return redirect()->route('double-entry.journals.edit', $id);
    }

    public function destroy(int $id)
    {
        return redirect()->route('double-entry.journals.index');
    }

    protected function placeholder(string $title)
    {
        return view('double-entry::placeholder', [
            'title' => $title,
            'message' => trans('double-entry::general.coming_soon', ['feature' => $title]),
        ]);
    }
}
