<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'listrik',
            'beli produk stok',
            'beli alat',
            'bayar freelance',
            'lainnya',
        ];

        foreach ($categories as $name) {
            ExpenseCategory::query()->updateOrCreate(['name' => $name]);
        }
    }
}
