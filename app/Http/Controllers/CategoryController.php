<?php

namespace App\Http\Controllers;

use App\Contracts\CategoryManager;
use App\Enums\RecordStatus;
use App\Http\Requests\Categories\StoreCategoryRequest;
use App\Http\Requests\Categories\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(private readonly CategoryManager $categories) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Category::class);

        return view('categories.index', [
            'categories' => $this->categories->paginate($request->only(['search', 'status'])),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Category::class);

        return view('categories.create', [
            'category' => new Category(['status' => RecordStatus::Active]),
            'parents' => $this->categories->allActive(),
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $category = $this->categories->create($request->validated());

        return redirect()->route('categories.index')
            ->with('status', "Category {$category->name} created.");
    }

    public function edit(Category $category): View
    {
        $this->authorize('update', $category);

        return view('categories.edit', [
            'category' => $category->load('parent'),
            'parents' => $this->categories->allActive()->where('id', '!=', $category->id),
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $this->categories->update($category, $request->validated());

        return redirect()->route('categories.index')
            ->with('status', 'Category updated.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        $this->categories->delete($category);

        return redirect()->route('categories.index')
            ->with('status', 'Category deleted.');
    }

    public function changeStatus(Request $request, Category $category): RedirectResponse
    {
        $this->authorize('changeStatus', $category);

        $request->validate(['status' => ['required', 'string']]);

        $this->categories->changeStatus(
            $category,
            RecordStatus::from((string) $request->input('status')),
        );

        return back()->with('status', 'Category status updated.');
    }
}
