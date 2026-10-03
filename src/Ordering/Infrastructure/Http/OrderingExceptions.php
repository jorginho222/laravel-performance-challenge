<?php

namespace Src\Ordering\Infrastructure\Http;

use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Validation\ValidationException;
use Src\Ordering\Domain\Exceptions\ProductNotFound;
use Src\Ordering\Domain\Exceptions\ProductsUnavailable;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Renders the ordering domain exceptions as the HTTP errors the API returns
 * (registered in bootstrap/app.php).
 */
class OrderingExceptions
{
    public static function register(Exceptions $exceptions): void
    {
        $exceptions->map(ProductsUnavailable::class, fn (ProductsUnavailable $e) => ValidationException::withMessages(
            collect($e->reasons)->mapWithKeys(fn (string $reason, string $id) => ["products.{$id}" => $reason])->all()
        ));

        $exceptions->map(ProductNotFound::class, fn (ProductNotFound $e) => new NotFoundHttpException($e->getMessage(), $e));
    }
}
