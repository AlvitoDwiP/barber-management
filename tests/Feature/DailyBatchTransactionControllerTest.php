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
        $response->assertSeeText('Input transaksi harian sekaligus');
        $response->assertSeeText('Tambah Transaksi');
        $response->assertSeeText('Duplikat');
        $response->assertSeeText('Ringkasan Input Harian');
        $response->assertSeeText('Transaksi terisi');
        $response->assertSeeText('Kas Masuk');
        $response->assertDontSeeText('Rekap Manual');
        $response->assertDontSeeText('Status Rekonsiliasi');
        $response->assertDontSee('manual_recap[transaction_count]', false);
        $response->assertDontSee('manual_recap[cash]', false);
        $response->assertDontSee('manual_recap[qr]', false);
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
            'entries' => [
                [
                    'employee_id' => $employee->id,
                    'payment_method' => 'cash',
                    'notes' => 'Walk in',
                    'items' => $this->withoutItemEmployees($this->transactionItems(
                        $employee->id,
                        [
                            ['service_id' => $service->id],
                        ],
                        [
                            ['product_id' => $product->id, 'qty' => 2],
                        ],
                    )),
                ],
                [
                    'employee_id' => $employee->id,
                    'payment_method' => 'qr',
                    'notes' => '',
                    'items' => $this->withoutItemEmployees($this->transactionItems(
                        $employee->id,
                        [
                            ['service_id' => $service->id],
                            ['service_id' => $service->id],
                        ],
                    )),
                ],
            ],
        ]);

        $response->assertRedirect(route('transactions.index'));
        $response->assertSessionHas('success', '2 transaksi berhasil disimpan. Hasilnya sudah masuk ke daftar transaksi.');
        $this->assertDatabaseCount('transactions', 2);
        $this->assertDatabaseHas('transactions', [
            'transaction_date' => '2026-03-15 00:00:00',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
        ]);
        $this->assertSame(3, $product->fresh()->stock);
    }

    public function test_daily_batch_request_ignores_commission_override_fields_from_payload(): void
    {
        $user = User::factory()->create();
        $employee = $this->createEmployee();
        $service = Service::query()->create([
            'name' => 'Hair Spa',
            'price' => '100000.00',
            'commission_type' => 'percent',
            'commission_value' => '40.00',
        ]);
        $product = Product::query()->create([
            'name' => 'Vitamin',
            'price' => '50000.00',
            'stock' => 5,
            'commission_type' => 'fixed',
            'commission_value' => '5000.00',
        ]);

        $response = $this->actingAs($user)->post(route('transactions.daily-batch.store'), [
            'transaction_date' => '2026-03-15',
            'entries' => [
                [
                    'employee_id' => $employee->id,
                    'payment_method' => 'cash',
                    'notes' => 'No override from transaction form',
                    'items' => [
                        ...$this->withoutItemEmployees([
                            $this->serviceItem($service->id, $employee->id, 'fixed', 9999),
                            $this->productItem($product->id, $employee->id, 2, 'percent', 99),
                        ]),
                    ],
                ],
            ],
        ]);

        $response->assertRedirect(route('transactions.index'));
        $this->assertDatabaseHas('transaction_items', [
            'item_type' => 'service',
            'item_name' => 'Hair Spa',
            'commission_type' => 'percent',
            'commission_value' => '40.00',
            'commission_amount' => '40000.00',
        ]);
        $this->assertDatabaseHas('transaction_items', [
            'item_type' => 'product',
            'item_name' => 'Vitamin',
            'commission_type' => 'fixed',
            'commission_value' => '5000.00',
            'commission_amount' => '10000.00',
        ]);
    }

    public function test_daily_batch_request_rejects_empty_entry(): void
    {
        $user = User::factory()->create();
        $employee = $this->createEmployee();

        $response = $this->actingAs($user)
            ->from(route('transactions.daily-batch.create'))
            ->post(route('transactions.daily-batch.store'), [
                'transaction_date' => '2026-03-15',
                'entries' => [
                    [
                        'employee_id' => $employee->id,
                        'payment_method' => 'cash',
                        'notes' => '',
                        'items' => [],
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
            'entries' => [
                [
                    'employee_id' => $employee->id,
                    'payment_method' => 'cash',
                    'notes' => null,
                    'items' => $this->withoutItemEmployees($this->transactionItems(
                        $employee->id,
                        [
                            ['service_id' => $service->id],
                        ],
                    )),
                ],
                [
                    'employee_id' => $employee->id,
                    'payment_method' => 'qr',
                    'notes' => null,
                    'items' => $this->withoutItemEmployees($this->transactionItems(
                        $employee->id,
                        [],
                        [
                            ['product_id' => $product->id, 'qty' => 2],
                        ],
                    )),
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
                'entries' => [
                    [
                        'employee_id' => $employee->id,
                        'payment_method' => 'cash',
                        'notes' => null,
                        'items' => $this->withoutItemEmployees($this->transactionItems(
                            $employee->id,
                            [
                                ['service_id' => $service->id],
                            ],
                        )),
                    ],
                    [
                        'employee_id' => $employee->id,
                        'payment_method' => 'qr',
                        'notes' => null,
                        'items' => [],
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
                'entries' => [
                    [
                        'employee_id' => $employee->id,
                        'payment_method' => 'cash',
                        'notes' => null,
                        'items' => $this->withoutItemEmployees([
                            $this->serviceItem(9999, $employee->id),
                            $this->productItem(9999, $employee->id, -1),
                        ]),
                    ],
                ],
            ]);

        $response->assertRedirect(route('transactions.daily-batch.create'));
        $response->assertSessionHasErrors([
            'entries.0.items.0.service_id',
            'entries.0.items.1.product_id',
            'entries.0.items.1.qty',
        ]);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_daily_batch_request_rejects_inactive_employee_for_new_transactions(): void
    {
        $user = User::factory()->create();
        $employee = Employee::query()->create([
            'name' => 'Pegawai Nonaktif',
            'employment_type' => Employee::EMPLOYMENT_TYPE_PERMANENT,
            'is_active' => false,
        ]);
        $service = Service::query()->create([
            'name' => 'Hair Spa',
            'price' => '100000.00',
        ]);

        $response = $this->actingAs($user)
            ->from(route('transactions.daily-batch.create'))
            ->post(route('transactions.daily-batch.store'), [
                'transaction_date' => '2026-03-15',
                'entries' => [
                    [
                        'employee_id' => $employee->id,
                        'payment_method' => 'cash',
                        'notes' => null,
                        'items' => $this->withoutItemEmployees([
                            $this->serviceItem($service->id, $employee->id),
                        ]),
                    ],
                ],
            ]);

        $response->assertRedirect(route('transactions.daily-batch.create'));
        $response->assertSessionHasErrors([
            'entries.0.employee_id' => 'Pegawai transaksi nonaktif atau tidak valid.',
        ]);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_daily_batch_request_accepts_two_blocks_with_different_employees(): void
    {
        $user = User::factory()->create();
        $employeeOne = $this->createEmployee();
        $employeeTwo = Employee::query()->create([
            'name' => 'Sari',
            'status' => 'tetap',
            'is_active' => true,
        ]);
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
            'entries' => [
                [
                    'employee_id' => $employeeOne->id,
                    'payment_method' => 'cash',
                    'notes' => 'Blok pertama',
                    'items' => $this->withoutItemEmployees([
                        $this->serviceItem($service->id, $employeeOne->id),
                    ]),
                ],
                [
                    'employee_id' => $employeeTwo->id,
                    'payment_method' => 'qr',
                    'notes' => 'Blok kedua',
                    'items' => $this->withoutItemEmployees([
                        $this->productItem($product->id, $employeeTwo->id, 2),
                    ]),
                ],
            ],
        ]);

        $response->assertRedirect(route('transactions.index'));
        $this->assertDatabaseHas('transactions', [
            'employee_id' => $employeeOne->id,
            'payment_method' => 'cash',
            'notes' => 'Blok pertama',
        ]);
        $this->assertDatabaseHas('transactions', [
            'employee_id' => $employeeTwo->id,
            'payment_method' => 'qr',
            'notes' => 'Blok kedua',
        ]);
        $this->assertDatabaseHas('transaction_items', [
            'item_type' => 'service',
            'employee_id' => $employeeOne->id,
            'employee_name' => $employeeOne->name,
        ]);
        $this->assertDatabaseHas('transaction_items', [
            'item_type' => 'product',
            'employee_id' => $employeeTwo->id,
            'employee_name' => $employeeTwo->name,
        ]);
    }

    private function createEmployee(): Employee
    {
        return Employee::query()->create([
            'name' => 'Budi',
            'status' => 'tetap',
            'is_active' => true,
        ]);
    }

    private function withoutItemEmployees(array $items): array
    {
        return collect($items)
            ->map(function (array $item): array {
                unset($item['employee_id']);

                return $item;
            })
            ->values()
            ->all();
    }
}
