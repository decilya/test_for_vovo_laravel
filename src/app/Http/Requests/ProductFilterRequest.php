<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class ProductFilterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Поиск по названию
            'q' => [
                'nullable',
                'string',
                'min:2',
                'max:255',
            ],

            // Фильтры по цене
            'price_from' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999999.99',
                'decimal:0,2',
            ],
            'price_to' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999999.99',
                'decimal:0,2',
                'gte:price_from',
            ],

            // Фильтр по категории
            'category_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
            ],

            // Множественный выбор категорий
            'category_ids' => [
                'nullable',
                'array',
                'max:10',
            ],
            'category_ids.*' => [
                'integer',
                'exists:categories,id',
            ],

            // Фильтр по наличию
            'in_stock' => [
                'nullable',
                'boolean',
            ],

            // Фильтры по рейтингу
            'rating_from' => [
                'nullable',
                'numeric',
                'min:0',
                'max:5',
                'regex:/^\d(\.\d{1})?$/',
            ],
            'rating_to' => [
                'nullable',
                'numeric',
                'min:0',
                'max:5',
                'regex:/^\d(\.\d{1})?$/',
                'gte:rating_from',
            ],

            // Сортировка
            'sort' => [
                'nullable',
                'string',
                Rule::in(['price_asc', 'price_desc', 'rating_desc', 'newest', 'popular', 'name_asc', 'name_desc']),
            ],

            // Пагинация
            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],

            // Дополнительные фильтры
            'min_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'max_price' => [
                'nullable',
                'numeric',
                'min:0',
                'gte:min_price',
            ],

            // Фильтр по дате создания
            'created_after' => [
                'nullable',
                'date',
                'date_format:Y-m-d',
            ],
            'created_before' => [
                'nullable',
                'date',
                'date_format:Y-m-d',
                'after_or_equal:created_after',
            ],

            // Фильтр по обновлению
            'updated_after' => [
                'nullable',
                'date',
                'date_format:Y-m-d',
            ],

            // Включать удаленные товары (для админов)
            'with_trashed' => [
                'nullable',
                'boolean',
            ],

            // Включать товары не в наличии
            'include_out_of_stock' => [
                'nullable',
                'boolean',
            ],


            // Диапазон цен в виде массива
            'price_range' => [
                'nullable',
                'array:min,max',
                'size:2',
            ],
            'price_range.min' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'price_range.max' => [
                'nullable',
                'numeric',
                'min:0',
                'gte:price_range.min',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'q.min' => 'Поисковый запрос должен содержать минимум 2 символа',
            'q.max' => 'Поисковый запрос не может быть длиннее 255 символов',

            'price_from.numeric' => 'Минимальная цена должна быть числом',
            'price_from.min' => 'Минимальная цена не может быть отрицательной',
            'price_from.max' => 'Цена слишком высокая',
            'price_from.decimal' => 'Цена должна содержать максимум 2 знака после запятой',

            'price_to.numeric' => 'Максимальная цена должна быть числом',
            'price_to.gte' => 'Максимальная цена должна быть больше или равна минимальной',

            'category_id.exists' => 'Указанная категория не существует',

            'category_ids.max' => 'Можно выбрать не более 10 категорий',
            'category_ids.*.exists' => 'Одна из указанных категорий не существует',

            'in_stock.boolean' => 'Параметр наличия должен быть true или false',

            'rating_from.numeric' => 'Рейтинг должен быть числом',
            'rating_from.min' => 'Рейтинг не может быть меньше 0',
            'rating_from.max' => 'Рейтинг не может быть больше 5',
            'rating_from.regex' => 'Рейтинг должен быть в формате: 0-5 с одной десятичной цифрой',

            'rating_to.gte' => 'Максимальный рейтинг должен быть больше или равен минимальному',

            'sort.in' => 'Недопустимое значение сортировки. Допустимые: price_asc, price_desc, rating_desc, newest, popular, name_asc, name_desc',

            'page.min' => 'Номер страницы должен быть не менее 1',

            'per_page.min' => 'Количество элементов на странице должно быть не менее 1',
            'per_page.max' => 'Количество элементов на странице не может превышать 100',

            'created_after.date_format' => 'Дата должна быть в формате ГГГГ-ММ-ДД',
            'created_before.after_or_equal' => 'Дата "до" должна быть позже или равна дате "после"',

            'tags.max' => 'Можно выбрать не более 5 тэгов',
            'tags.*.max' => 'Тэг не может быть длиннее 50 символов',

            'price_range.size' => 'Диапазон цен должен содержать минимальное и максимальное значение',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'q' => 'поисковый запрос',
            'price_from' => 'минимальная цена',
            'price_to' => 'максимальная цена',
            'category_id' => 'категория',
            'category_ids' => 'категории',
            'in_stock' => 'наличие',
            'rating_from' => 'минимальный рейтинг',
            'rating_to' => 'максимальный рейтинг',
            'sort' => 'сортировка',
            'per_page' => 'элементов на странице',
            'created_after' => 'дата создания от',
            'created_before' => 'дата создания до',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Преобразуем строковые булевые значения в boolean
        if ($this->has('in_stock')) {
            $this->merge([
                'in_stock' => filter_var($this->in_stock, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        if ($this->has('with_trashed')) {
            $this->merge([
                'with_trashed' => filter_var($this->with_trashed, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        if ($this->has('include_out_of_stock')) {
            $this->merge([
                'include_out_of_stock' => filter_var($this->include_out_of_stock, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        // Обрабатываем price_range если передан как строка JSON
        if ($this->has('price_range') && is_string($this->price_range)) {
            try {
                $priceRange = json_decode($this->price_range, true, 512, JSON_THROW_ON_ERROR);
                $this->merge(['price_range' => $priceRange]);
            } catch (\JsonException $e) {
                // Оставляем как есть, валидация обработает ошибку
            }
        }

        // Обрабатываем category_ids если передан как строка
        if ($this->has('category_ids') && is_string($this->category_ids)) {
            $categoryIds = array_filter(explode(',', $this->category_ids));
            $this->merge(['category_ids' => $categoryIds]);
        }

        // Обрабатываем tags если передан как строка
        if ($this->has('tags') && is_string($this->tags)) {
            $tags = array_filter(explode(',', $this->tags));
            $this->merge(['tags' => $tags]);
        }

        // Нормализуем поисковый запрос
        if ($this->has('q')) {
            $searchQuery = trim($this->q);
            $searchQuery = preg_replace('/\s+/', ' ', $searchQuery); // Убираем множественные пробелы
            $this->merge(['q' => $searchQuery]);
        }

        // Конвертируем пустые строки в null для числовых полей
        $numericFields = ['price_from', 'price_to', 'rating_from', 'rating_to', 'min_price', 'max_price'];
        foreach ($numericFields as $field) {
            if ($this->has($field) && $this->$field === '') {
                $this->merge([$field => null]);
            }
        }
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        $response = response()->json([
            'success' => false,
            'message' => 'Ошибка валидации',
            'errors' => $validator->errors(),
            'request_data' => $this->validated(),
        ], 422);

        throw new HttpResponseException($response);
    }

    /**
     * Get validated data with defaults.
     */
    public function validatedWithDefaults(): array
    {
        $validated = $this->validated();

        // Устанавливаем значения по умолчанию
        $defaults = [
            'sort' => 'newest',
            'per_page' => config('products.pagination.default_per_page', 15),
            'page' => 1,
            'in_stock' => true, // По умолчанию показываем только товары в наличии
            'include_out_of_stock' => false,
        ];

        return array_merge($defaults, $validated);
    }

    /**
     * Get only filter parameters (excluding pagination and sorting).
     */
    public function filtersOnly(): array
    {
        $validated = $this->validatedWithDefaults();

        // Удаляем параметры пагинации и сортировки
        $exclude = ['sort', 'page', 'per_page', 'with_trashed', 'include_out_of_stock'];

        return array_diff_key($validated, array_flip($exclude));
    }

    /**
     * Get pagination parameters.
     */
    public function paginationParams(): array
    {
        $validated = $this->validatedWithDefaults();

        return [
            'page' => $validated['page'],
            'per_page' => $validated['per_page'],
        ];
    }

    /**
     * Check if search query is present.
     */
    public function hasSearch(): bool
    {
        return !empty($this->validated('q'));
    }

    /**
     * Check if price filters are present.
     */
    public function hasPriceFilter(): bool
    {
        return !empty($this->validated('price_from')) ||
            !empty($this->validated('price_to')) ||
            !empty($this->validated('price_range'));
    }

    /**
     * Get price range as array [min, max].
     */
    public function getPriceRange(): ?array
    {
        if ($this->has('price_range')) {
            $range = $this->validated('price_range');
            return [
                'min' => $range['min'] ?? null,
                'max' => $range['max'] ?? null,
            ];
        }

        return [
            'min' => $this->validated('price_from'),
            'max' => $this->validated('price_to'),
        ];
    }

    /**
     * Get sort field and direction.
     */
    public function getSortParams(): array
    {
        $sort = $this->validatedWithDefaults()['sort'];

        return match ($sort) {
            'price_asc' => ['field' => 'price', 'direction' => 'asc'],
            'price_desc' => ['field' => 'price', 'direction' => 'desc'],
            'rating_desc' => ['field' => 'rating', 'direction' => 'desc'],
            'popular' => ['field' => 'views', 'direction' => 'desc'], // предполагаем поле views
            'name_asc' => ['field' => 'name', 'direction' => 'asc'],
            'name_desc' => ['field' => 'name', 'direction' => 'desc'],
            default => ['field' => 'created_at', 'direction' => 'desc'], // newest
        };
    }
}
