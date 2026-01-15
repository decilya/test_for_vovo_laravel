<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminProductFilterRequest extends ProductFilterRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        $parentRules = parent::rules();

        // Добавляем дополнительные правила для админов
        $additionalRules = [
            'status' => ['nullable', 'string', 'in:active,inactive,draft,archived'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'created_by' => ['nullable', 'integer', 'exists:users,id'],
            'has_images' => ['nullable', 'boolean'],
            'has_description' => ['nullable', 'boolean'],
            'sku' => ['nullable', 'string', 'max:100'],
            'vendor_code' => ['nullable', 'string', 'max:100'],
            'weight_from' => ['nullable', 'numeric', 'min:0'],
            'weight_to' => ['nullable', 'numeric', 'min:0'],
            'dimensions' => ['nullable', 'string'],
        ];

        return array_merge($parentRules, $additionalRules);
    }

    public function messages(): array
    {
        $parentMessages = parent::messages();

        $additionalMessages = [
            'status.in' => 'Статус может быть: active, inactive, draft, archived',
            'user_id.exists' => 'Указанный пользователь не существует',
            'created_by.exists' => 'Указанный создатель не существует',
        ];

        return array_merge($parentMessages, $additionalMessages);
    }
}
