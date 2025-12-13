<?php

namespace Database\Factories;

use App\Enums\OrderSymbol;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory
{
    protected $model = Asset::class;

    public function definition(): array
    {
        $symbol = $this->faker->randomElement(OrderSymbol::cases());

        return [
            'user_id' => User::factory(),
            'symbol' => $symbol->value,
            'amount' => '1',
            'locked_amount' => '0',
        ];
    }
}
