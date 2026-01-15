<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'category_id' => Category::factory(),
            'in_stock' => $this->faker->boolean(80),
            'rating' => $this->faker->randomFloat(1, 0, 5),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
