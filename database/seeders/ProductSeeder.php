<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            ['name' => 'Hair clay', 'price' => 75000, 'stock' => 20],
            ['name' => 'Hair powder', 'price' => 70000, 'stock' => 20],
            ['name' => 'Vitamin rambut', 'price' => 85000, 'stock' => 20],
            ['name' => 'Minuman', 'price' => 10000, 'stock' => 50],
        ];

        foreach ($products as $product) {
            Product::query()->updateOrCreate(
                ['name' => $product['name']],
                [
                    'price' => $product['price'],
                    'stock' => $product['stock'],
                ]
            );
        }
    }
}
