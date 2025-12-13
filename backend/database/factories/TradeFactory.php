<?php

namespace Database\Factories;

use App\Enums\OrderSymbol;
use App\Models\Order;
use App\Models\Trade;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trade>
 */
class TradeFactory extends Factory
{
    protected $model = Trade::class;

    public function definition(): array
    {
        $symbol = $this->faker->randomElement(OrderSymbol::cases());
        $price = $symbol->price();

        return [
            'symbol' => $symbol->value,
            'buy_order_id' => Order::factory(),
            'sell_order_id' => Order::factory(),
            'price' => $price,
            'amount' => '1',
            'volume_usd' => bcmul('1', $price, 8),
            'fee_usd' => bcmul(bcmul('1', $price, 8), '0.015', 8),
        ];
    }
}
