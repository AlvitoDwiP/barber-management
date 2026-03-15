<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyBatchTransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_daily_batch_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('transactions.daily-batch.create'));

        $response->assertOk();
        $response->assertSee('Tambah Transaksi');
        $response->assertSee('Gunakan halaman ini untuk membuat satu atau beberapa transaksi sekaligus');
    }

    public function test_authenticated_user_can_store_daily_batch_transactions(): void
    {
        $user = User::factory()->create();
        $employee = $this->createEmployee();
        $service = Service::query()->create([
            'name' => 'Hair Spa',
            'price' => '100000.00',
        ]);
        $product = Product::query()->create([
            'name' => 'Vitamin',
            'price' => '50000.00',
            'stock' => 5,
        ]);

        $response = $this->actingAs($user)->post(route('transactions.daily-batch.store'), [
            'transaction_date' => '2026-03-15',
            'employee_id' => $employee->id,
            'entries' => [
                [
                    'payment_method' => 'cash',
                    'notes' => 'Walk in',
                    'services' => [
                        ['service_id' => $service->id],
                    ],
                    'products' => [
                        ['product_id' => $product->id, 'qty' => 2],
                    ],
                ],
                [
                    'payment_method' => 'qr',
                    'notes' => '',
                    'services' => [
                        ['service_id' => $service->id],
                        ['service_id' => $service->id],
                    ],
                    'products' => [],
                ],
            ],
        ]);

        $response->assertRedirect(route('transactions.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseCount('transactions', 2);
        $this->assertDatabaseHas('transactions', [
            'transaction_date' => '2026-03-15 00:00:00',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
        ]);
        $this->assertSame(3, $product->fresh()->stock);
    }

    public function test_daily_batch_request_rejects_empty_entry(): void
    {
        $user = User::factory()->create();
        $employee = $this->createEmployee();

        $response = $this->actingAs($user)
            ->from(route('transactions.daily-batch.create'))
            ->post(route('transactions.daily-batch.store'), [
                'transaction_date' => '2026-03-15',
                'employee_id' => $employee->id,
                'entries' => [
                    [
                        'payment_method' => 'cash',
                        'notes' => '',
                        'services' => [
                            ['service_id' => ''],
                        ],
                        'products' => [
                            ['product_id' => '', 'qty' => 1],
                        ],
                    ],
                ],
            ]);

        $response->assertRedirect(route('transactions.daily-batch.create'));
        $response->assertSessionHasErrors('entries.0.items');
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_daily_batch_request_accepts_service_only_and_product_only_entries(): void
    {
        $user = User::factory()->create();
        $employee = $this->createEmployee();
        $service = Service::query()->create([
            'name' => 'Hair Spa',
            'price' => '100000.00',
        ]);
        $product = Product::query()->create([
            'name' => 'Vitamin',
            'price' => '50000.00',
            'stock' => 5,
        ]);

        $response = $this->actingAs($user)->post(route('transactions.daily-batch.store'), [
            'transaction_date' => '2026-03-15',
            'employee_id' => $employee->id,
            'entries' => [
                [
                    'payment_method' => 'cash',
                    'notes' => null,
                    'services' => [
                        ['service_id' => $service->id],
                    ],
                    'products' => [],
                ],
                [
                    'payment_method' => 'qr',
                    'notes' => null,
                    'services' => [],
                    'products' => [
                        ['product_id' => $product->id, 'qty' => 2],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect(route('transactions.index'));
        $this->assertDatabaseCount('transactions', 2);
        $this->assertSame(3, $product->fresh()->stock);
    }

    public function test_daily_batch_request_rejects_when_one_entry_is_empty_even_if_another_is_valid(): void
    {
        $user = User::factory()->create();
        $employee = $this->createEmployee();
        $service = Service::query()->create([
            'name' => 'Hair Spa',
            'price' => '100000.00',
        ]);

        $response = $this->actingAs($user)
            ->from(route('transactions.daily-batch.create'))
            ->post(route('transactions.daily-batch.store'), [
                'transaction_date' => '2026-03-15',
                'employee_id' => $employee->id,
                'entries' => [
                    [
                        'payment_method' => 'cash',
                        'notes' => null,
                        'services' => [
                            ['service_id' => $service->id],
                        ],
                        'products' => [],
                    ],
                    [
                        'payment_method' => 'qr',
                        'notes' => null,
                        'services' => [],
                        'products' => [],
                    ],
                ],
            ]);

        $response->assertRedirect(route('transactions.daily-batch.create'));
        $response->assertSessionHasErrors('entries.1.items');
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_daily_batch_request_rejects_invalid_ids_and_non_positive_qty(): void
    {
        $user = User::factory()->create();
        $employee = $this->createEmployee();

        $response = $this->actingAs($user)
            ->from(route('transactions.daily-batch.create'))
            ->post(route('transactions.daily-batch.store'), [
                'transaction_date' => '2026-03-15',
                'employee_id' => $employee->id,
                'entries' => [
                    [
                        'payment_method' => 'cash',
                        'notes' => null,
                        'services' => [
                            ['service_id' => 9999],
                        ],
                        'products' => [
                            ['product_id' => 9999, 'qty' => -1],
                        ],
                    ],
                ],
            ]);

        $response->assertRedirect(route('transactions.daily-batch.create'));
        $response->assertSessionHasErrors([
            'entries.0.services.0.service_id',
            'entries.0.products.0.product_id',
            'entries.0.products.0.qty',
        ]);
        $this->assertDatabaseCount('transactions', 0);
    }

    private function createEmployee(): Employee
    {
        return Employee::query()->create([
            'name' => 'Budi',
            'status' => 'tetap',
        ]);
    }
}
