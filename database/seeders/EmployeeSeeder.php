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
            ['name' => 'Andi', 'status' => 'tetap'],
            ['name' => 'Budi', 'status' => 'freelance'],
        ];

        foreach ($employees as $employee) {
            Employee::query()->updateOrCreate(
                ['name' => $employee['name']],
                ['status' => $employee['status']]
            );
        }
    }
}
