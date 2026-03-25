<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Employee::query()->updateOrCreate(
            ['name' => 'dafasand'],
            [
                'employment_type' => Employee::EMPLOYMENT_TYPE_PERMANENT,
                'is_active' => true,
            ]
        );
    }
}
