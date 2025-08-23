<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrdersTableSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');  // Disable FK checks

        Order::truncate();

        $users = User::all();

        foreach ($users as $user) {
            Order::factory()->count(rand(1,3))->create([
                'user_id' => $user->id,
            ]);
        }
    }
}
