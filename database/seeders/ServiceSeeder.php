<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            ['name' => 'Hair Color (Basic)', 'price' => 40000],
            ['name' => 'Hair Color (Full)', 'price' => 225000],
            ['name' => 'Hair Color (Highlights)', 'price' => 150000],
            ['name' => 'Hair Keratin', 'price' => 175000],
            ['name' => 'Down Perm', 'price' => 75000],
            ['name' => 'Cold Perm', 'price' => 150000],
            ['name' => 'Wash & Style', 'price' => 20000],
            ['name' => 'Shaving', 'price' => 15000],
            ['name' => 'Haircut', 'price' => 40000],
        ];

        foreach ($services as $service) {
            Service::query()->updateOrCreate(
                ['name' => $service['name']],
                ['price' => $service['price']]
            );
        }
    }
}
