<?php

namespace Src\Catalog\Infrastructure\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Src\Catalog\Domain\ProductStatus;

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
            'status' => ['sometimes', Rule::enum(ProductStatus::class)],
            'search' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
