<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition()
    {
        return [
            'title' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->randomFloat(2, 10, 500),
            'category_id' => 1, // will be overridden in seeder
            'stock' => $this->faker->numberBetween(0, 100),
            'color' => $this->faker->words(1, true),
            'material' => $this->faker->words(1, true),
            'dimensions' => $this->faker->words(3, true)
        ];
    }
}
