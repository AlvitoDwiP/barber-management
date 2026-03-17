<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\FreelancePayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_without_related_history_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $employee = Employee::query()->create([
            'name' => 'Pegawai Baru',
            'employment_type' => Employee::EMPLOYMENT_TYPE_FREELANCE,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->delete(route('employees.destroy', $employee));

        $response->assertRedirect(route('employees.index'));
        $response->assertSessionHas('success', 'Data pegawai berhasil dihapus.');
        $this->assertDatabaseMissing('employees', ['id' => $employee->id]);
    }

    public function test_employee_with_freelance_payment_history_is_deactivated_instead_of_deleted(): void
    {
        $user = User::factory()->create();
        $employee = Employee::query()->create([
            'name' => 'Budi Freelance',
            'employment_type' => Employee::EMPLOYMENT_TYPE_FREELANCE,
            'is_active' => true,
        ]);

        FreelancePayment::query()->create([
            'employee_id' => $employee->id,
            'work_date' => '2026-03-16',
            'total_service_amount' => '100000.00',
            'service_commission' => '50000.00',
            'total_product_qty' => 0,
            'product_commission' => '0.00',
            'total_commission' => '50000.00',
            'payment_status' => FreelancePayment::STATUS_UNPAID,
        ]);

        $response = $this->actingAs($user)->delete(route('employees.destroy', $employee));

        $response->assertRedirect(route('employees.index'));
        $response->assertSessionHas('success', 'Pegawai memiliki data historis sehingga dinonaktifkan, bukan dihapus.');
        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'is_active' => false,
        ]);
    }

    public function test_inactive_employee_with_history_remains_stored_when_destroy_is_called_again(): void
    {
        $user = User::factory()->create();
        $employee = Employee::query()->create([
            'name' => 'Freelancer Lama',
            'employment_type' => Employee::EMPLOYMENT_TYPE_FREELANCE,
            'is_active' => false,
        ]);

        FreelancePayment::query()->create([
            'employee_id' => $employee->id,
            'work_date' => '2026-03-16',
            'total_service_amount' => '100000.00',
            'service_commission' => '50000.00',
            'total_product_qty' => 0,
            'product_commission' => '0.00',
            'total_commission' => '50000.00',
            'payment_status' => FreelancePayment::STATUS_PAID,
        ]);

        $response = $this->actingAs($user)->delete(route('employees.destroy', $employee));

        $response->assertRedirect(route('employees.index'));
        $response->assertSessionHas('success', 'Pegawai ini sudah nonaktif dan tetap disimpan karena memiliki data historis.');
        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'is_active' => false,
        ]);
    }
}
