<?php

namespace Src\Catalog\Infrastructure\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Src\Catalog\Infrastructure\Persistence\ProductModel;

/**
 * Any user can browse products; only users who manage the catalog can change them.
 */
class ProductPolicy
{
    public function viewAny(Authenticatable $user): bool
    {
        return true;
    }

    public function view(Authenticatable $user, ProductModel $product): bool
    {
        return true;
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
