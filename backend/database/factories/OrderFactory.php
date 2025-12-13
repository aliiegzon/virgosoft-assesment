<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\OrderSymbol;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $symbol = $this->faker->randomElement(OrderSymbol::cases());

        return [
            'user_id' => User::factory(),
            'symbol' => $symbol->value,
            'side' => $this->faker->randomElement(['buy', 'sell']),
            'price' => $symbol->price(),
            'amount' => '1',
            'status' => OrderStatus::OPEN,
            'locked_value' => '0',
        ];
    }
}
