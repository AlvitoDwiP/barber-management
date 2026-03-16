<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public const DEFAULT_NAME = 'Admin';
    public const DEFAULT_EMAIL = 'sejati@gmail.com';
    public const DEFAULT_PASSWORD = '12341234';

    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => self::DEFAULT_EMAIL],
            [
                'name' => self::DEFAULT_NAME,
                'password' => Hash::make(self::DEFAULT_PASSWORD),
            ]
        );
    }
}
