<?php

namespace Modules\ExpenseClaims\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Response;
use Modules\ExpenseClaims\Http\Requests\CategoryStore;
use Modules\ExpenseClaims\Http\Requests\CategoryUpdate;
use Modules\ExpenseClaims\Models\ExpenseClaimCategory;

class Categories extends Controller
{
    public function index(): Response
    {
        $categories = ExpenseClaimCategory::where('company_id', company_id())
            ->orderBy('name')
            ->paginate(25);

        return view('expense-claims::categories.index', compact('categories'));
    }

    public function create(): Response
    {
        return view('expense-claims::categories.create');
    }

    public function store(CategoryStore $request): Response
    {
        ExpenseClaimCategory::create([
            'company_id' => company_id(),
            'name' => $request->get('name'),
            'color' => $request->get('color'),
            'enabled' => (bool) $request->get('enabled', true),
        ]);

        flash(trans('messages.success.added', ['type' => trans('expense-claims::general.category')]))->success();

        return redirect()->route('expense-claims.categories.index');
    }

    public function edit(int $id): Response
    {
        $category = ExpenseClaimCategory::where('company_id', company_id())->findOrFail($id);

        return view('expense-claims::categories.edit', compact('category'));
    }

    public function update(int $id, CategoryUpdate $request): Response
    {
        $category = ExpenseClaimCategory::where('company_id', company_id())->findOrFail($id);

        $category->update([
            'name' => $request->get('name'),
            'color' => $request->get('color'),
            'enabled' => (bool) $request->get('enabled', false),
        ]);

        flash(trans('messages.success.updated', ['type' => trans('expense-claims::general.category')]))->success();

        return redirect()->route('expense-claims.categories.index');
    }

    public function destroy(int $id): Response
    {
        $category = ExpenseClaimCategory::where('company_id', company_id())->findOrFail($id);

        if ($category->items()->exists()) {
            flash(trans('expense-claims::general.messages.category_in_use'))->warning();

            return redirect()->route('expense-claims.categories.index');
        }

        $category->delete();

        flash(trans('messages.success.deleted', ['type' => trans('expense-claims::general.category')]))->success();

        return redirect()->route('expense-claims.categories.index');
    }
}
