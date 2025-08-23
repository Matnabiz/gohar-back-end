<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition()
    {
        return [
            'user_id' => 1,  // or faker random user id
            'total' => $this->faker->randomFloat(2, 20, 500),
            'status' => 'pending', // or use faker random status
            // Add other fields here based on your orders table
        ];
    }
}
