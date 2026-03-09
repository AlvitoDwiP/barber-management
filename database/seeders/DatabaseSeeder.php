<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'sejati@gmail.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('12341234'),
            ]
        );

        $this->call([
            ServiceSeeder::class,
            ProductSeeder::class,
            EmployeeSeeder::class,
            ExpenseCategorySeeder::class,
        ]);
    }
}
