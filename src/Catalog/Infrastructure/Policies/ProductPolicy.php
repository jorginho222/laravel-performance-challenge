<?php

namespace Src\Catalog\Infrastructure\Policies;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Src\Catalog\Domain\ProductStatus;
use Src\Catalog\Infrastructure\Persistence\ProductModel;

/**
 * Any user can browse active products; only users who manage the catalog can see inactive
 * ones and change products.
 */
class ProductPolicy
{
    public function viewAny(Authenticatable $user): bool
    {
        return true;
    }

    /**
     * An inactive product is reported as not found to users who cannot see it, so they
     * cannot tell it exists.
     */
    public function view(Authenticatable $user, ProductModel $product): Response
    {
        return $product->status === ProductStatus::Active || $this->viewInactive($user)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function viewInactive(Authenticatable $user): bool
    {
        return Gate::forUser($user)->allows('manage-catalog');
    }

    public function create(Authenticatable $user): bool
    {
        return Gate::forUser($user)->allows('manage-catalog');
    }

    public function update(Authenticatable $user, ProductModel $product): bool
    {
        return Gate::forUser($user)->allows('manage-catalog');
    }

    public function delete(Authenticatable $user, ProductModel $product): bool
    {
        return Gate::forUser($user)->allows('manage-catalog');
    }
}
