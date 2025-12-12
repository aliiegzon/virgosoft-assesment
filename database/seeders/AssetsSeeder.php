<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Seeder;

class AssetsSeeder extends Seeder
{
    public function run(): void
    {
        $seedUsers = [
            'admin@admin.com',
            'egzon@admin.com',
        ];

        foreach ($seedUsers as $email) {
            $user = User::query()->where('email', $email)->first();
            if (!$user) {
                continue;
            }

            $this->seedAsset($user, 'USDT', '10000', '0');
            $this->seedAsset($user, 'BTC', '1', '0');
        }
    }

    private function seedAsset(User $user, string $symbol, string $amount, string $lockedAmount): void
    {
        Asset::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'symbol' => $symbol,
            ],
            [
                'amount' => $amount,
                'locked_amount' => $lockedAmount,
            ]
        );
    }
}
