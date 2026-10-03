<?php

namespace Src\Ordering\Domain\Exceptions;

use Src\Shared\Domain\DomainException;

final class ProductNotFound extends DomainException
{
    /**
     * @param  list<string>  $productIds
     */
    public function __construct(public readonly array $productIds)
    {
        parent::__construct('Products not found: '.implode(', ', $productIds).'.');
    }
}
