<?php

namespace Src\Catalog\Infrastructure\Http\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Src\Catalog\Infrastructure\Cache\CategoryCache;
use Src\Catalog\Infrastructure\Http\Requests\StoreCategoryRequest;
use Src\Catalog\Infrastructure\Http\Requests\UpdateCategoryRequest;
use Src\Catalog\Infrastructure\Http\Resources\CategoryResource;
use Src\Catalog\Infrastructure\Persistence\CategoryModel;

class CategoryWebController
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', CategoryModel::class);

        $page = max(1, $request->integer('page', 1));

        // Cached apart from the API listing: the payload's page links point to this route.
        $categories = CategoryCache::remember(
            "web:index:page:{$page}",
            fn () => CategoryResource::collection(CategoryModel::orderBy('name')->paginate())
                ->response($request)
                ->getData(true),
        );

        return Inertia::render('Catalog/Categories/CategoryIndex', ['categories' => $categories]);
    }

    public function create(): Response
    {
        Gate::authorize('create', CategoryModel::class);

        return Inertia::render('Catalog/Categories/CategoryCreate');
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $category = CategoryModel::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Category {$category->name} created. The listing refreshes when its cache expires."]);

        return redirect('/categories');
    }

    public function edit(CategoryModel $category): Response
    {
        Gate::authorize('update', $category);

        return Inertia::render('Catalog/Categories/CategoryEdit', [
            'category' => (new CategoryResource($category))->resolve(),
        ]);
    }

    public function update(UpdateCategoryRequest $request, CategoryModel $category): RedirectResponse
    {
        $category->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Category {$category->name} updated. The listing refreshes when its cache expires."]);

        return redirect('/categories');
    }

    public function destroy(CategoryModel $category): RedirectResponse
    {
        Gate::authorize('delete', $category);

        if ($category->products()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => "Category {$category->name} has products and cannot be deleted."]);

            return back();
        }

        $category->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Category {$category->name} deleted. The listing refreshes when its cache expires."]);

        return back();
    }
}
