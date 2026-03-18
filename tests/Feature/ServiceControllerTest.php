<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_service_allows_null_commission_override(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->post(route('services.store'), [
                'name' => 'Hair Spa',
                'price' => '120000.00',
                'commission_type' => '',
                'commission_value' => '',
            ]);

        $response->assertRedirect(route('services.index'));

        $this->assertDatabaseHas('services', [
            'name' => 'Hair Spa',
            'price' => '120000.00',
            'commission_type' => null,
            'commission_value' => null,
        ]);
    }

    public function test_store_service_rejects_invalid_percent_commission_override(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->from(route('services.create'))
            ->post(route('services.store'), [
                'name' => 'Hair Spa',
                'price' => '120000.00',
                'commission_type' => 'percent',
                'commission_value' => '150',
            ]);

        $response->assertRedirect(route('services.create'));
        $response->assertSessionHasErrors([
            'commission_value' => 'Nilai komisi persen harus berada di antara 0 sampai 100.',
        ]);
        $this->assertDatabaseCount('services', 0);
    }

    public function test_store_service_rejects_negative_percent_commission_override(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->from(route('services.create'))
            ->post(route('services.store'), [
                'name' => 'Hair Spa',
                'price' => '120000.00',
                'commission_type' => 'percent',
                'commission_value' => '-1',
            ]);

        $response->assertRedirect(route('services.create'));
        $response->assertSessionHasErrors([
            'commission_value' => 'Nilai komisi tidak boleh negatif.',
        ]);
        $this->assertDatabaseCount('services', 0);
    }

    public function test_update_service_requires_type_and_value_to_be_consistent(): void
    {
        $service = Service::query()->create([
            'name' => 'Haircut',
            'price' => '50000.00',
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->from(route('services.edit', $service))
            ->put(route('services.update', $service), [
                'name' => 'Haircut',
                'price' => '50000.00',
                'commission_type' => 'fixed',
                'commission_value' => '',
            ]);

        $response->assertRedirect(route('services.edit', $service));
        $response->assertSessionHasErrors([
            'commission_value' => 'Nilai komisi wajib diisi saat tipe komisi dipilih.',
        ]);
    }

    public function test_store_service_rejects_fixed_commission_override(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->from(route('services.create'))
            ->post(route('services.store'), [
                'name' => 'Hair Spa',
                'price' => '120000.00',
                'commission_type' => 'fixed',
                'commission_value' => '25000.00',
            ]);

        $response->assertRedirect(route('services.create'));
        $response->assertSessionHasErrors([
            'commission_type' => 'Tipe komisi harus berupa Persen (%).',
        ]);
        $this->assertDatabaseCount('services', 0);
    }

    public function test_update_service_can_persist_percent_commission_override(): void
    {
        $service = Service::query()->create([
            'name' => 'Haircut',
            'price' => '50000.00',
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->put(route('services.update', $service), [
                'name' => 'Haircut Premium',
                'price' => '75000.00',
                'commission_type' => 'percent',
                'commission_value' => '40.00',
            ]);

        $response->assertRedirect(route('services.index'));

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'name' => 'Haircut Premium',
            'price' => '75000.00',
            'commission_type' => 'percent',
            'commission_value' => '40.00',
        ]);
    }
}
