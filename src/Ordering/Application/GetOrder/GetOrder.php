<?php

namespace Src\Ordering\Application\GetOrder;

use Src\Ordering\Application\OrderData;
use Src\Ordering\Domain\OrderRepository;

final class GetOrder
{
    public function __construct(private OrderRepository $orderRepository) {}

    /**
     * The order, or null when it does not exist.
     */
    public function handle(string $orderId): ?OrderData
    {
        $order = $this->orderRepository->find($orderId);

        return $order ? OrderData::fromOrder($order) : null;
    }
}
