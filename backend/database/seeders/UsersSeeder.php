<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UsersSeeder extends Seeder
{
    private array $users = [
        [
            'email' => 'admin@admin.com',
            'first_name' => 'Admin',
            'last_name' => 'Admin',
            'role' => 'admin',
            'password' => '123#456!',
            'balance'  => 10000000,
        ],
        [
            'email' => 'egzon@admin.com',
            'first_name' => 'Egzon',
            'last_name' => 'Admin',
            'role' => 'admin',
            'password' => '123#456!',
            'balance'  => 10000000,
        ]
    ];

    public function run()
    {
        foreach ($this->users as $userData) {
            $this->createUser($userData);
        }
    }

    private function createUser(array $userData): void
    {
        $user = User::query()->firstOrCreate(
            ['email' => $userData['email']],
            [
                'first_name' => $userData['first_name'],
                'last_name' => $userData['last_name'],
                'password' => Hash::make($userData['password']),
                'remember_token' => Str::random(10),
                'is_active' => true,
                'email_verified_at' => now(),
                'balance' => $userData['balance'] ?? 0,
            ]
        );

        if (isset($userData['role'])) {
            $role = Role::query()->where('name', $userData['role'])
                ->where('guard_name', 'api')
                ->first();

            if ($role) {
                $user->assignRole($role);
            }
        }
    }
}
