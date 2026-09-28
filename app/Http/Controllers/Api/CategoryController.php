<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return CategoryResource::collection(Category::orderBy('name')->paginate());
    }

    public function store(Request $request): JsonResponse
    {
        $category = Category::create($request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]));

        return (new CategoryResource($category))->response()->setStatusCode(201);
    }

    public function show(Category $category): CategoryResource
    {
        return new CategoryResource($category);
    }

    public function update(Request $request, Category $category): CategoryResource
    {
        $category->update($request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
        ]));

        return new CategoryResource($category);
    }

    public function destroy(Category $category): JsonResponse
    {
        if ($category->products()->exists()) {
            return response()->json(['message' => 'Category has products and cannot be deleted.'], 409);
        }

        $category->delete();

        return response()->json(null, 204);
    }
}
