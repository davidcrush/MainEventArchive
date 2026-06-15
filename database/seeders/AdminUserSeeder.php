<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    private const PLACEHOLDER_PASSWORD = 'CHANGE_ME_SET_A_STRONG_PASSWORD_ON_PRODUCTION';

    public function run(): void
    {
        $name = config('admin.name');
        $email = config('admin.email');
        $password = config('admin.password');

        if (blank($password) || $password === self::PLACEHOLDER_PASSWORD) {
            throw new RuntimeException(
                'Set ADMIN_PASSWORD in .env before seeding. Copy .env.example and replace the placeholder value.',
            );
        }

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password,
                'is_admin' => true,
                'email_verified_at' => now(),
            ],
        );
    }
}
