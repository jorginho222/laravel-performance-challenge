<?php

namespace Src\Ordering\Infrastructure\Http\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Src\Ordering\Application\CreateOrder\CreateOrder;
use Src\Ordering\Application\GetOrder\GetOrder;
use Src\Ordering\Infrastructure\Http\Requests\StoreOrderRequest;
use Src\Ordering\Infrastructure\Http\Resources\OrderResource;

class OrderController
{
    public function store(StoreOrderRequest $request, CreateOrder $createOrder): RedirectResponse
    {
        $order = $createOrder->handle($request->toDto());

        return redirect("/orders/{$order->id}");
    }

    public function show(string $order, GetOrder $getOrder): Response
    {
        $data = $getOrder->handle($order) ?? abort(404);

        Gate::authorize('view-order', $data);

        return Inertia::render('Ordering/OrderShow', [
            'order' => (new OrderResource($data))->resolve(),
        ]);
    }
}
