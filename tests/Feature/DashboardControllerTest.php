<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Expense;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_today_summary_uses_transaction_date_instead_of_created_at(): void
    {
        config(['app.timezone' => 'Asia/Jakarta']);

        $this->travelTo(Carbon::parse('2026-03-15 09:00:00', config('app.timezone')));

        $user = User::factory()->create();
        $employee = $this->createEmployee();

        Transaction::query()->create([
            'transaction_code' => 'TRX-20260315-001',
            'transaction_date' => '2026-03-15',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'subtotal_amount' => '100000.00',
            'discount_amount' => '0.00',
            'total_amount' => '100000.00',
            'notes' => 'Masuk ringkasan hari ini',
            'created_at' => Carbon::parse('2026-03-14 23:45:00', config('app.timezone')),
            'updated_at' => Carbon::parse('2026-03-14 23:45:00', config('app.timezone')),
        ]);

        Transaction::query()->create([
            'transaction_code' => 'TRX-20260315-002',
            'transaction_date' => '2026-03-15',
            'employee_id' => $employee->id,
            'payment_method' => 'qr',
            'subtotal_amount' => '75000.00',
            'discount_amount' => '0.00',
            'total_amount' => '75000.00',
            'notes' => 'Tetap masuk meski dibuat kemarin',
            'created_at' => Carbon::parse('2026-03-14 22:30:00', config('app.timezone')),
            'updated_at' => Carbon::parse('2026-03-14 22:30:00', config('app.timezone')),
        ]);

        Transaction::query()->create([
            'transaction_code' => 'TRX-20260314-001',
            'transaction_date' => '2026-03-14',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'subtotal_amount' => '50000.00',
            'discount_amount' => '0.00',
            'total_amount' => '50000.00',
            'notes' => 'Tidak boleh masuk ringkasan hari ini',
            'created_at' => Carbon::parse('2026-03-15 08:15:00', config('app.timezone')),
            'updated_at' => Carbon::parse('2026-03-15 08:15:00', config('app.timezone')),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('todaySummary', fn (array $summary): bool => $summary === [
            'today_revenue' => 175000.0,
            'today_transactions' => 2,
            'today_cash' => 100000.0,
            'today_qr' => 75000.0,
        ]);
    }

    public function test_dashboard_monthly_summary_still_uses_transaction_date_for_current_month(): void
    {
        config(['app.timezone' => 'Asia/Jakarta']);

        $this->travelTo(Carbon::parse('2026-03-15 09:00:00', config('app.timezone')));

        $user = User::factory()->create();
        $employee = $this->createEmployee();

        Transaction::query()->create([
            'transaction_code' => 'TRX-20260315-010',
            'transaction_date' => '2026-03-15',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'subtotal_amount' => '100000.00',
            'discount_amount' => '0.00',
            'total_amount' => '100000.00',
            'notes' => null,
        ]);

        Transaction::query()->create([
            'transaction_code' => 'TRX-20260301-001',
            'transaction_date' => '2026-03-01',
            'employee_id' => $employee->id,
            'payment_method' => 'qr',
            'subtotal_amount' => '50000.00',
            'discount_amount' => '0.00',
            'total_amount' => '50000.00',
            'notes' => null,
        ]);

        Transaction::query()->create([
            'transaction_code' => 'TRX-20260228-001',
            'transaction_date' => '2026-02-28',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'subtotal_amount' => '80000.00',
            'discount_amount' => '0.00',
            'total_amount' => '80000.00',
            'notes' => null,
        ]);

        Expense::query()->create([
            'expense_date' => '2026-03-10',
            'category' => 'listrik',
            'amount' => '30000.00',
            'note' => 'Biaya bulan berjalan',
        ]);

        Expense::query()->create([
            'expense_date' => '2026-02-20',
            'category' => 'lainnya',
            'amount' => '45000.00',
            'note' => 'Biaya bulan lalu',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('monthlySummary', fn (array $summary): bool => $summary === [
            'month_revenue' => 150000.0,
            'month_expenses' => 30000.0,
            'month_profit_estimate' => 120000.0,
        ]);
    }

    private function createEmployee(): Employee
    {
        return Employee::query()->create([
            'name' => 'Budi',
            'status' => 'tetap',
        ]);
    }
}
