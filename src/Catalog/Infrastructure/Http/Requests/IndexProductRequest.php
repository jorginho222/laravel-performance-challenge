<?php

namespace Src\Catalog\Infrastructure\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Src\Catalog\Domain\ProductStatus;
use Src\Catalog\Infrastructure\Persistence\ProductModel;

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
            'status' => ['sometimes', $this->canViewInactive() ? Rule::enum(ProductStatus::class) : Rule::in([ProductStatus::Active->value])],
            'search' => ['sometimes', 'string', 'max:255'],
        ];
    }

    /**
     * The validated filters, limited to active products for users who cannot see inactive ones.
     *
     * @return array{search?: string, category_id?: string, status?: string}
     */
    public function filters(): array
    {
        return $this->canViewInactive()
            ? $this->validated()
            : [...$this->validated(), 'status' => ProductStatus::Active->value];
    }

    private function canViewInactive(): bool
    {
        return $this->user()->can('viewInactive', ProductModel::class);
    }
}
