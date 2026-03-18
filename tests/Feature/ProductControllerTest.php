<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_product_can_persist_fixed_commission_override(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->post(route('products.store'), [
                'name' => 'Pomade',
                'price' => '45000.00',
                'stock' => 12,
                'commission_type' => 'fixed',
                'commission_value' => '5000.00',
            ]);

        $response->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', [
            'name' => 'Pomade',
            'price' => '45000.00',
            'stock' => 12,
            'commission_type' => 'fixed',
            'commission_value' => '5000.00',
        ]);
    }

    public function test_store_product_rejects_negative_commission_override(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->from(route('products.create'))
            ->post(route('products.store'), [
                'name' => 'Pomade',
                'price' => '45000.00',
                'stock' => 12,
                'commission_type' => 'fixed',
                'commission_value' => '-1',
            ]);

        $response->assertRedirect(route('products.create'));
        $response->assertSessionHasErrors([
            'commission_value' => 'Nilai komisi tidak boleh negatif.',
        ]);
        $this->assertDatabaseCount('products', 0);
    }

    public function test_store_product_rejects_percent_commission_above_hundred(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->from(route('products.create'))
            ->post(route('products.store'), [
                'name' => 'Pomade',
                'price' => '45000.00',
                'stock' => 12,
                'commission_type' => 'percent',
                'commission_value' => '150',
            ]);

        $response->assertRedirect(route('products.create'));
        $response->assertSessionHasErrors([
            'commission_value' => 'Nilai komisi persen harus berada di antara 0 sampai 100.',
        ]);
        $this->assertDatabaseCount('products', 0);
    }

    public function test_update_product_requires_type_when_commission_value_is_present(): void
    {
        $product = Product::query()->create([
            'name' => 'Pomade',
            'price' => '45000.00',
            'stock' => 12,
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->from(route('products.edit', $product))
            ->put(route('products.update', $product), [
                'name' => 'Pomade',
                'price' => '45000.00',
                'stock' => 12,
                'commission_type' => '',
                'commission_value' => '5000.00',
            ]);

        $response->assertRedirect(route('products.edit', $product));
        $response->assertSessionHasErrors([
            'commission_type' => 'Tipe komisi wajib dipilih saat nilai komisi diisi.',
        ]);
    }

    public function test_update_product_can_clear_commission_override_to_follow_default(): void
    {
        $product = Product::query()->create([
            'name' => 'Pomade',
            'price' => '45000.00',
            'stock' => 12,
            'commission_type' => 'fixed',
            'commission_value' => '5000.00',
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->put(route('products.update', $product), [
                'name' => 'Pomade',
                'price' => '45000.00',
                'stock' => 12,
                'commission_type' => '',
                'commission_value' => '',
            ]);

        $response->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'commission_type' => null,
            'commission_value' => null,
        ]);
    }
}
