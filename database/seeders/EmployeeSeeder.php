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
        $employees = [
            ['name' => 'Andi', 'employment_type' => 'permanent'],
            ['name' => 'Budi', 'employment_type' => 'freelance'],
        ];

        foreach ($employees as $employee) {
            Employee::query()->updateOrCreate(
                ['name' => $employee['name']],
                ['employment_type' => $employee['employment_type']]
            );
        }
    }
}
