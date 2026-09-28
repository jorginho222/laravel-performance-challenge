<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'category_id' => ['sometimes', 'uuid'],
            'status' => ['sometimes', Rule::in(Product::STATUSES)],
            'search' => ['sometimes', 'string', 'max:255'],
        ]);

        $products = Product::with('category')
            ->when($filters['category_id'] ?? null, fn ($q, $v) => $q->where('category_id', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->orderBy('name')
            ->paginate();

        return ProductResource::collection($products);
    }

    public function store(Request $request): JsonResponse
    {
        $product = Product::create($request->validate($this->rules()));

        return (new ProductResource($product->load('category')))->response()->setStatusCode(201);
    }

    public function show(Product $product): ProductResource
    {
        return new ProductResource($product->load('category'));
    }

    public function update(Request $request, Product $product): ProductResource
    {
        $product->update($request->validate($this->rules(partial: true)));

        return new ProductResource($product->load('category'));
    }

    public function destroy(Product $product): JsonResponse
    {
        if ($product->orders()->exists()) {
            return response()->json(['message' => 'Product belongs to orders and cannot be deleted.'], 409);
        }

        $product->delete();

        return response()->json(null, 204);
    }

    private function rules(bool $partial = false): array
    {
        $sometimes = $partial ? ['sometimes'] : [];

        return [
            'name' => [...$sometimes, 'required', 'string', 'max:255'],
            'category_id' => [...$sometimes, 'required', 'uuid', 'exists:categories,id'],
            'price' => [...$sometimes, 'required', 'numeric', 'min:0', 'max:99999999.99'],
            'stock' => [...$sometimes, 'required', 'integer', 'min:0'],
            'status' => [...$sometimes, 'required', Rule::in(Product::STATUSES)],
        ];
    }
}
