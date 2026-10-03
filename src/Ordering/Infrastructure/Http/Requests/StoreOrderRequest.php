<?php

namespace Src\Ordering\Infrastructure\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Src\Ordering\Application\CreateOrder\CreateOrderCommand;
use Src\Ordering\Application\CreateOrder\OrderItem;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'products' => ['required', 'array', 'min:1'],
            'products.*.product_id' => ['required', 'uuid', 'distinct', 'exists:products,id'],
            'products.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    public function toCommand(): CreateOrderCommand
    {
        return new CreateOrderCommand(
            $this->user()->id,
            array_map(
                fn (array $item) => new OrderItem($item['product_id'], (int) $item['quantity']),
                $this->validated('products'),
            ),
        );
    }
}
