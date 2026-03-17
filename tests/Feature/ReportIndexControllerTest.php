<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportIndexControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_index_only_shows_final_mvp_report_menus(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get(route('reports.index'));

        $response->assertOk();
        $response->assertSeeText('Menu laporan');
        $response->assertSeeText('Laporan Harian');
        $response->assertSeeText('Laporan Bulanan');
        $response->assertSeeText('Laporan Kinerja Pegawai');
        $response->assertSeeText('Laporan Penjualan Produk');
        $response->assertDontSeeText('Laporan metode pembayaran');
        $response->assertDontSeeText('Laporan Produktivitas Pegawai');
        $response->assertDontSeeText('Laporan produk');

        $response->assertSee(route('reports.daily'), false);
        $response->assertSee(route('reports.monthly'), false);
        $response->assertSee(route('reports.employees'), false);
        $response->assertSee(route('reports.products'), false);
    }
}
