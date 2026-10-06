<?php

namespace Src\Ordering\Infrastructure\Http\Api;

use Illuminate\Http\JsonResponse;
use Src\Ordering\Application\CreateOrder\CreateOrder;
use Src\Ordering\Infrastructure\Http\Requests\StoreOrderRequest;
use Src\Ordering\Infrastructure\Http\Resources\OrderResource;

class OrderApiController
{
    public function store(StoreOrderRequest $request, CreateOrder $createOrder): JsonResponse
    {
        $order = $createOrder->handle($request->toDto());

        return (new OrderResource($order))->response()->setStatusCode(201);
    }
}
