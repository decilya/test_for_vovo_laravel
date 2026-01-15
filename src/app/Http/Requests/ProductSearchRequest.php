<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'min:2', 'max:500'],

            'filters' => ['nullable', 'array'],
            'filters.price_range' => ['nullable', 'array:min,max'],
            'filters.price_range.min' => ['nullable', 'numeric', 'min:0'],
            'filters.price_range.max' => ['nullable', 'numeric', 'min:0'],
            'filters.categories' => ['nullable', 'array'],
            'filters.categories.*' => ['integer', 'exists:categories,id'],
            'filters.in_stock' => ['nullable', 'boolean'],
            'filters.rating_min' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'filters.brands' => ['nullable', 'array'],
            'filters.brands.*' => ['string', 'max:100'],
            'filters.attributes' => ['nullable', 'array'],

            'sort' => ['nullable', 'array'],
            'sort.field' => ['required_with:sort', 'string', 'in:price,name,rating,created_at,views,sales'],
            'sort.direction' => ['required_with:sort', 'string', 'in:asc,desc'],

            'pagination' => ['nullable', 'array'],
            'pagination.page' => ['nullable', 'integer', 'min:1'],
            'pagination.per_page' => ['nullable', 'integer', 'min:1', 'max:200'],

            'options' => ['nullable', 'array'],
            'options.include' => ['nullable', 'array'],
            'options.include.*' => ['string', 'in:category,images,reviews,variants'],
            'options.with_trashed' => ['nullable', 'boolean'],
            'options.only_active' => ['nullable', 'boolean'],
        ];
    }

    public function validatedWithDefaults(): array
    {
        $validated = parent::validated();

        $defaults = [
            'filters' => [],
            'sort' => ['field' => 'created_at', 'direction' => 'desc'],
            'pagination' => ['page' => 1, 'per_page' => 15],
            'options' => ['only_active' => true],
        ];

        return array_merge($defaults, $validated);
    }
}
