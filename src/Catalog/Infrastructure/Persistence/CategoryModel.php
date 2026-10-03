<?php

namespace Src\Catalog\Infrastructure\Persistence;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Src\Catalog\Infrastructure\Policies\CategoryPolicy;

#[UseFactory(CategoryFactory::class)]
#[UsePolicy(CategoryPolicy::class)]
class CategoryModel extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory, HasUuids;

    protected $table = 'categories';

    protected $fillable = ['name'];

    public function products(): HasMany
    {
        return $this->hasMany(ProductModel::class, 'category_id');
    }
}
