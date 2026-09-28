<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Order extends Model
{
    use HasUuids;

    protected $fillable = ['number', 'total'];

    protected function casts(): array
    {
        return ['total' => 'decimal:2'];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            $order->number ??= (static::max('number') ?? 0) + 1;
        });
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->withPivot('quantity');
    }
}
