<?php

namespace Src\Catalog\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Src\Catalog\Infrastructure\Cache\CategoryCache;
use Src\Catalog\Infrastructure\Http\Requests\StoreCategoryRequest;
use Src\Catalog\Infrastructure\Http\Requests\UpdateCategoryRequest;
use Src\Catalog\Infrastructure\Http\Resources\CategoryResource;
use Src\Catalog\Infrastructure\Persistence\CategoryModel;

class CategoryController
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', CategoryModel::class);

        $page = max(1, $request->integer('page', 1));

        $payload = CategoryCache::remember(
            "index:page:{$page}",
            fn () => CategoryResource::collection(CategoryModel::orderBy('name')->paginate())
                ->response($request)
                ->getData(true),
        );

        return response()->json($payload);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = CategoryModel::create($request->validated());

        return (new CategoryResource($category))->response()->setStatusCode(201);
    }

    public function show(CategoryModel $category): CategoryResource
    {
        Gate::authorize('view', $category);

        return new CategoryResource($category);
    }

    public function update(UpdateCategoryRequest $request, CategoryModel $category): CategoryResource
    {
        $category->update($request->validated());

        return new CategoryResource($category);
    }

    public function destroy(CategoryModel $category): JsonResponse
    {
        Gate::authorize('delete', $category);

        if ($category->products()->exists()) {
            return response()->json(['message' => 'Category has products and cannot be deleted.'], 409);
        }

        $category->delete();

        return response()->json(null, 204);
    }
}
