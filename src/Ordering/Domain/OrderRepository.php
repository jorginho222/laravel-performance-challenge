<?php

namespace Src\Ordering\Domain;

interface OrderRepository
{
    /**
     * The next order number. Call it inside the transaction that saves the order, so
     * concurrent orders get consecutive numbers and a rolled-back order leaves no gap.
     */
    public function nextNumber(): int;

    public function save(Order $order): void;

    public function find(string $id): ?Order;
}
