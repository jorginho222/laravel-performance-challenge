<?php

namespace Src\Catalog\Infrastructure\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Src\Catalog\Infrastructure\Persistence\CategoryModel;

/**
 * Any user can browse categories; only users who manage the catalog can change them.
 */
class CategoryPolicy
{
    public function viewAny(Authenticatable $user): bool
    {
        return true;
    }

    public function view(Authenticatable $user, CategoryModel $category): bool
    {
        return true;
    }

    public function create(Authenticatable $user): bool
    {
        return Gate::forUser($user)->allows('manage-catalog');
    }

    public function update(Authenticatable $user, CategoryModel $category): bool
    {
        return Gate::forUser($user)->allows('manage-catalog');
    }

    public function delete(Authenticatable $user, CategoryModel $category): bool
    {
        return Gate::forUser($user)->allows('manage-catalog');
    }
}
