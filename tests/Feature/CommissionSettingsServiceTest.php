<?php

namespace Tests\Feature;

use App\Models\CommissionSetting;
use App\Services\CommissionSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CommissionSettingsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_commission_settings_are_available_on_new_environment(): void
    {
        $this->assertDatabaseHas('commission_settings', CommissionSetting::defaultAttributes());

        $settings = app(CommissionSettingsService::class)->get();

        $this->assertSame('percent', $settings->default_service_commission_type);
        $this->assertSame('50.00', $settings->default_service_commission_value);
        $this->assertSame('fixed', $settings->default_product_commission_type);
        $this->assertSame('5000.00', $settings->default_product_commission_value);
    }

    public function test_service_recreates_default_commission_settings_when_row_is_missing(): void
    {
        CommissionSetting::query()->whereKey(1)->delete();

        DB::table('commission_settings')->insert([
            'id' => 2,
            'default_service_commission_type' => 'fixed',
            'default_service_commission_value' => '10000.00',
            'default_product_commission_type' => 'percent',
            'default_product_commission_value' => '10.00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $settings = app(CommissionSettingsService::class)->get();

        $this->assertSame(1, $settings->id);
        $this->assertDatabaseCount('commission_settings', 2);
        $this->assertSame('percent', $settings->default_service_commission_type);
        $this->assertSame('50.00', $settings->default_service_commission_value);
        $this->assertSame('fixed', $settings->default_product_commission_type);
        $this->assertSame('5000.00', $settings->default_product_commission_value);
    }
}
