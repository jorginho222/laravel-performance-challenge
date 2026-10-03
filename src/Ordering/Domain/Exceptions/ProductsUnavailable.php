<?php

namespace Src\Ordering\Domain\Exceptions;

use Src\Shared\Domain\DomainException;

final class ProductsUnavailable extends DomainException
{
    /**
     * @param  array<string, string>  $reasons  keyed by product id
     */
    public function __construct(public readonly array $reasons)
    {
        parent::__construct(implode(' ', $reasons));
    }
}
