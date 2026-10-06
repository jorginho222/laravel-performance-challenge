<?php

namespace Src\Catalog\Infrastructure\Http\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Src\Catalog\Infrastructure\Http\Requests\IndexProductRequest;
use Src\Catalog\Infrastructure\Http\Requests\StoreProductRequest;
use Src\Catalog\Infrastructure\Http\Requests\UpdateProductRequest;
use Src\Catalog\Infrastructure\Http\Resources\ProductResource;
use Src\Catalog\Infrastructure\Persistence\CategoryOptions;
use Src\Catalog\Infrastructure\Persistence\ProductModel;
use Src\Catalog\Infrastructure\Search\ProductSearch;

class ProductWebController
{
    public function index(IndexProductRequest $request, ProductSearch $search): Response
    {
        Gate::authorize('viewAny', ProductModel::class);

        return Inertia::render('Catalog/Products/ProductIndex', [
            'products' => ProductResource::collection($search->paginate($request->filters())),
            'filters' => (object) $request->validated(),
            'categories' => fn () => CategoryOptions::all(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', ProductModel::class);

        return Inertia::render('Catalog/Products/ProductCreate', [
            'categories' => CategoryOptions::all(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = ProductModel::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Product {$product->name} created."]);

        return redirect('/products');
    }

    public function edit(ProductModel $product): Response
    {
        Gate::authorize('update', $product);

        return Inertia::render('Catalog/Products/ProductEdit', [
            'product' => (new ProductResource($product->load('category')))->resolve(),
            'categories' => CategoryOptions::all(),
        ]);
    }

    public function update(UpdateProductRequest $request, ProductModel $product): RedirectResponse
    {
        $product->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Product {$product->name} updated."]);

        return redirect('/products');
    }

    public function destroy(ProductModel $product): RedirectResponse
    {
        Gate::authorize('delete', $product);

        if ($product->hasOrders()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => "Product {$product->name} belongs to orders and cannot be deleted."]);

            return back();
        }

        $product->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Product {$product->name} deleted."]);

        return back();
    }
}
