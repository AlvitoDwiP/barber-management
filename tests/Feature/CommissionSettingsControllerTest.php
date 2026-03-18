<?php

namespace Tests\Feature;

use App\Models\CommissionSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CommissionSettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_commission_settings_page_loads_singleton_settings_and_sidebar_link(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get(route('settings.commission.edit'));

        $response->assertOk();
        $response->assertSeeText('Komisi');
        $response->assertSeeText('Pengaturan Komisi');
        $response->assertSeeText('Persen (%)');
        $response->assertSeeText('Rupiah (Rp)');
        $response->assertSee('name="default_service_commission_type"', false);
        $response->assertSee('name="default_service_commission_value"', false);
        $response->assertSee('name="default_product_commission_type"', false);
        $response->assertSee('name="default_product_commission_value"', false);
        $response->assertSee('value="50.00"', false);
        $response->assertSee('value="5000.00"', false);
    }

    public function test_commission_settings_update_persists_singleton_row(): void
    {
        DB::table('commission_settings')->insert([
            'id' => 2,
            'default_service_commission_type' => 'percent',
            'default_service_commission_value' => '80.00',
            'default_product_commission_type' => 'fixed',
            'default_product_commission_value' => '9000.00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->put(route('settings.commission.update'), [
                'default_service_commission_type' => 'percent',
                'default_service_commission_value' => '45.00',
                'default_product_commission_type' => 'percent',
                'default_product_commission_value' => '12.50',
            ]);

        $response->assertRedirect(route('settings.commission.edit'));
        $response->assertSessionHas('success', 'Pengaturan komisi default berhasil diperbarui.');
        $this->assertDatabaseHas('commission_settings', [
            'id' => 1,
            'default_service_commission_type' => 'percent',
            'default_service_commission_value' => '45.00',
            'default_product_commission_type' => 'percent',
            'default_product_commission_value' => '12.50',
        ]);
        $this->assertDatabaseHas('commission_settings', [
            'id' => 2,
            'default_service_commission_type' => 'percent',
            'default_service_commission_value' => '80.00',
            'default_product_commission_type' => 'fixed',
            'default_product_commission_value' => '9000.00',
        ]);
        $this->assertDatabaseCount('commission_settings', 2);
    }

    public function test_commission_settings_update_rejects_invalid_types_and_percent_ranges(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->from(route('settings.commission.edit'))
            ->put(route('settings.commission.update'), [
                'default_service_commission_type' => 'fixed',
                'default_service_commission_value' => '150',
                'default_product_commission_type' => 'percent',
                'default_product_commission_value' => '101',
            ]);

        $response->assertRedirect(route('settings.commission.edit'));
        $response->assertSessionHasErrors([
            'default_service_commission_type' => 'Tipe komisi layanan default harus berupa percent.',
            'default_service_commission_value' => 'Nilai komisi layanan default persen harus berada di antara 0 sampai 100.',
            'default_product_commission_value' => 'Nilai komisi produk default persen harus berada di antara 0 sampai 100.',
        ]);
    }

    public function test_commission_settings_update_rejects_negative_fixed_value(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->from(route('settings.commission.edit'))
            ->put(route('settings.commission.update'), [
                'default_service_commission_type' => 'percent',
                'default_service_commission_value' => '50',
                'default_product_commission_type' => 'fixed',
                'default_product_commission_value' => '-1',
            ]);

        $response->assertRedirect(route('settings.commission.edit'));
        $response->assertSessionHasErrors([
            'default_product_commission_value' => 'Nilai komisi produk default tidak boleh negatif.',
        ]);
    }
}
