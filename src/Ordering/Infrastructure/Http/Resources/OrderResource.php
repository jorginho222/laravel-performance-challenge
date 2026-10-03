<?php

namespace Src\Ordering\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Src\Ordering\Application\OrderData;
use Src\Ordering\Application\OrderLineData;

/**
 * @property OrderData $resource
 */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $order = $this->resource;
        // Orders are never modified, so they were last updated when they were placed.
        $placedAt = Carbon::instance($order->placedAt)->toJSON();

        return [
            'id' => $order->id,
            'user_id' => $order->customerId,
            'number' => $order->number,
            'total' => $order->total,
            'products' => array_map(fn (OrderLineData $line) => [
                'product_id' => $line->productId,
                'name' => $line->productName,
                'price' => $line->unitPrice,
                'quantity' => $line->quantity,
            ], $order->lines),
            'created_at' => $placedAt,
            'updated_at' => $placedAt,
        ];
    }
}
