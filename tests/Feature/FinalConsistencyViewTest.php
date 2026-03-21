<?php

namespace Tests\Feature;

use App\Models\PayrollPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinalConsistencyViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_uses_owner_facing_indonesian_copy(): void
    {
        User::factory()->create();

        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSeeText('Login Owner');
        $response->assertSeeText('Kata Sandi');
        $response->assertSeeText('Tetap masuk');
        $response->assertSeeText('Lupa kata sandi?');
        $response->assertSeeText('Masuk');
    }

    public function test_payroll_detail_screen_uses_indonesian_labels(): void
    {
        $user = User::factory()->create();
        $payrollPeriod = PayrollPeriod::query()->create([
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-15',
            'status' => PayrollPeriod::STATUS_OPEN,
            'closed_at' => null,
        ]);

        $response = $this->actingAs($user)->get(route('payroll.show', $payrollPeriod));

        $response->assertOk();
        $response->assertSeeText('Detail Payroll');
        $response->assertSeeText('Ringkasan Periode Payroll');
        $response->assertSeeText('Tanggal Mulai');
        $response->assertSeeText('Tanggal Selesai');
        $response->assertSeeText('Tutup Payroll');
    }

    public function test_master_data_index_empty_states_offer_clear_next_actions(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('employees.index'));
        $response->assertOk();
        $response->assertSeeText('Belum ada pegawai');
        $response->assertSeeText('Tambah Pegawai');

        $response = $this->actingAs($user)->get(route('products.index'));
        $response->assertOk();
        $response->assertSeeText('Belum ada produk');
        $response->assertSeeText('Tambah Produk');

        $response = $this->actingAs($user)->get(route('services.index'));
        $response->assertOk();
        $response->assertSeeText('Belum ada layanan');
        $response->assertSeeText('Tambah Layanan');
    }
}
