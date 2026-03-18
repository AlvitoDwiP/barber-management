<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Reports\PaymentReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class PaymentReportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_report_exposes_exact_decimal_strings_for_cash_and_qr_totals(): void
    {
        $employee = Employee::query()->create([
            'name' => 'Budi',
            'status' => 'tetap',
        ]);

        Transaction::query()->create([
            'transaction_code' => 'TRX-20260118-001',
            'transaction_date' => '2026-01-18',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'subtotal_amount' => '1000.01',
            'discount_amount' => '0.00',
            'total_amount' => '1000.01',
        ]);

        Transaction::query()->create([
            'transaction_code' => 'TRX-20260118-002',
            'transaction_date' => '2026-01-18',
            'employee_id' => $employee->id,
            'payment_method' => 'qr',
            'subtotal_amount' => '0.02',
            'discount_amount' => '0.00',
            'total_amount' => '0.02',
        ]);

        Transaction::query()->create([
            'transaction_code' => 'TRX-20260201-001',
            'transaction_date' => '2026-02-01',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'subtotal_amount' => '50.00',
            'discount_amount' => '0.00',
            'total_amount' => '50.00',
        ]);

        $rows = app(PaymentReportService::class)->getPaymentMethodReport(2026)->keyBy('month_number');

        $this->assertSame('1000.01', $rows->get(1)['total_cash']);
        $this->assertSame('0.02', $rows->get(1)['total_qr']);
        $this->assertSame(2, $rows->get(1)['total_transactions']);
        $this->assertSame('50.00', $rows->get(2)['total_cash']);
        $this->assertSame('0.00', $rows->get(2)['total_qr']);
        $this->assertSame(1, $rows->get(2)['total_transactions']);
        $this->assertSame('0.00', $rows->get(3)['total_cash']);
        $this->assertSame('0.00', $rows->get(3)['total_qr']);

        $response = $this->actingAs(User::factory()->create())
            ->get(route('reports.payment', ['year' => 2026]));

        $response->assertOk();
        $response->assertViewHas('rows', function (Collection $rows): bool {
            $rows = $rows->keyBy('month_number');

            return $rows->get(1)['total_cash'] === '1000.01'
                && $rows->get(1)['total_qr'] === '0.02'
                && $rows->get(2)['total_cash'] === '50.00'
                && $rows->get(3)['total_cash'] === '0.00';
        });
    }
}
