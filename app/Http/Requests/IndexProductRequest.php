<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexProductRequest extends FormRequest
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
            'category_id' => ['sometimes', 'uuid'],
            'status' => ['sometimes', Rule::in(Product::STATUSES)],
            'search' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
