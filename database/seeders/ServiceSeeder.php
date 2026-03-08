<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = [
            ['name' => 'Haircut', 'price' => 50000],
            ['name' => 'Highlight', 'price' => 250000],
            ['name' => 'Perm', 'price' => 300000],
            ['name' => 'Keratin', 'price' => 450000],
        ];

        foreach ($services as $service) {
            Service::query()->updateOrCreate(
                ['name' => $service['name']],
                ['price' => $service['price']]
            );
        }
    }
}
