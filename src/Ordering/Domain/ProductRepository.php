<?php

namespace Src\Ordering\Domain;

interface ProductRepository
{
    /**
     * The existing products among the ids, locked until the transaction ends so concurrent
     * orders cannot oversell the same stock. They are locked in id order, so concurrent
     * orders cannot deadlock.
     *
     * @param  list<string>  $ids
     * @return list<Product>
     */
    public function lockByIds(array $ids): array;

    public function save(Product $product): void;
}
