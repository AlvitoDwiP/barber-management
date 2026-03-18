<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_product_form_shows_global_default_commission_value_as_readonly(): void
    {
        DB::table('commission_settings')->where('id', 1)->update([
            'default_service_commission_type' => 'percent',
            'default_service_commission_value' => '35.00',
            'default_product_commission_type' => 'fixed',
            'default_product_commission_value' => '8000.00',
            'updated_at' => now(),
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->get(route('products.create'));

        $response->assertOk();
        $response->assertSee('value="8000.00"', false);
        $response->assertSee('readonly', false);
        $response->assertSeeText('Nilai default produk dari pengaturan global ditampilkan otomatis dan tidak bisa diedit di sini.');
        $response->assertSee("x-bind:name=\"commissionType === '' ? 'commission_value' : null\"", false);
    }

    public function test_edit_product_form_keeps_existing_custom_commission_override_editable(): void
    {
        DB::table('commission_settings')->where('id', 1)->update([
            'default_service_commission_type' => 'percent',
            'default_service_commission_value' => '35.00',
            'default_product_commission_type' => 'fixed',
            'default_product_commission_value' => '8000.00',
            'updated_at' => now(),
        ]);

        $product = Product::query()->create([
            'name' => 'Pomade',
            'price' => '45000.00',
            'stock' => 12,
            'commission_type' => 'fixed',
            'commission_value' => '2500.00',
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->get(route('products.edit', $product));

        $response->assertOk();
        $response->assertSee('value="2500.00"', false);
        $response->assertSee('name="commission_value"', false);
        $response->assertSeeText('Masukkan nilai custom persen atau rupiah untuk override komisi produk ini.');
    }

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
