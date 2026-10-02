<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\UseCases\CreateOrder;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function store(StoreOrderRequest $request, CreateOrder $createOrder): JsonResponse
    {
        $order = $createOrder->handle($request->user(), $request->validated('products'));

        return (new OrderResource($order))->response()->setStatusCode(201);
    }
}
