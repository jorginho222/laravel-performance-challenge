<?php

namespace Src\Catalog\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Src\Catalog\Infrastructure\Http\Requests\IndexProductRequest;
use Src\Catalog\Infrastructure\Http\Requests\StoreProductRequest;
use Src\Catalog\Infrastructure\Http\Requests\UpdateProductRequest;
use Src\Catalog\Infrastructure\Http\Resources\ProductResource;
use Src\Catalog\Infrastructure\Persistence\Product;
use Src\Catalog\Infrastructure\Search\ProductSearch;

class ProductController
{
    public function index(IndexProductRequest $request, ProductSearch $search): AnonymousResourceCollection
    {
        $products = $search->paginate($request->validated());

        return ProductResource::collection($products);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::create($request->validated());

        return (new ProductResource($product->load('category')))->response()->setStatusCode(201);
    }

    public function show(Product $product): ProductResource
    {
        return new ProductResource($product->load('category'));
    }

    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        $product->update($request->validated());

        return new ProductResource($product->load('category'));
    }

    public function destroy(Product $product): JsonResponse
    {
        if ($product->hasOrders()) {
            return response()->json(['message' => 'Product belongs to orders and cannot be deleted.'], 409);
        }

        $product->delete();

        return response()->json(null, 204);
    }
}
