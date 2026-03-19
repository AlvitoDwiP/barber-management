<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DailyBatchRuntimeRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_items_schema_includes_employee_snapshot_columns_required_by_daily_batch(): void
    {
        $this->assertTrue(Schema::hasColumns('transaction_items', [
            'employee_id',
            'employee_name',
            'employee_employment_type',
        ]));
    }

    public function test_daily_batch_submits_one_block_with_one_service(): void
    {
        $user = User::factory()->create();
        $employee = $this->createEmployee('Budi');
        $service = Service::query()->create([
            'name' => 'Hair Spa',
            'price' => '100000.00',
        ]);

        $response = $this->actingAs($user)->post(route('transactions.daily-batch.store'), [
            'transaction_date' => '2026-03-19',
            'entries' => [
                [
                    'employee_id' => $employee->id,
                    'payment_method' => 'cash',
                    'notes' => 'Satu layanan',
                    'items' => [
                        [
                            'item_type' => 'service',
                            'service_id' => $service->id,
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect(route('transactions.index'));
        $response->assertSessionMissing('error');

        $this->assertDatabaseHas('transactions', [
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'notes' => 'Satu layanan',
            'total_amount' => '100000.00',
        ]);
        $this->assertDatabaseHas('transaction_items', [
            'item_type' => 'service',
            'service_id' => $service->id,
            'employee_id' => $employee->id,
            'employee_name' => $employee->name,
            'qty' => 1,
            'subtotal' => '100000.00',
        ]);
    }

    public function test_daily_batch_submits_one_block_with_one_product(): void
    {
        $user = User::factory()->create();
        $employee = $this->createEmployee('Budi');
        $product = Product::query()->create([
            'name' => 'Vitamin',
            'price' => '50000.00',
            'stock' => 5,
        ]);

        $response = $this->actingAs($user)->post(route('transactions.daily-batch.store'), [
            'transaction_date' => '2026-03-19',
            'entries' => [
                [
                    'employee_id' => $employee->id,
                    'payment_method' => 'qr',
                    'notes' => 'Satu produk',
                    'items' => [
                        [
                            'item_type' => 'product',
                            'product_id' => $product->id,
                            'qty' => 2,
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect(route('transactions.index'));
        $response->assertSessionMissing('error');

        $this->assertDatabaseHas('transactions', [
            'employee_id' => $employee->id,
            'payment_method' => 'qr',
            'notes' => 'Satu produk',
            'total_amount' => '100000.00',
        ]);
        $this->assertDatabaseHas('transaction_items', [
            'item_type' => 'product',
            'product_id' => $product->id,
            'employee_id' => $employee->id,
            'employee_name' => $employee->name,
            'qty' => 2,
            'subtotal' => '100000.00',
        ]);
        $this->assertSame(3, $product->fresh()->stock);
    }

    public function test_daily_batch_submits_one_block_with_mixed_items(): void
    {
        $user = User::factory()->create();
        $employee = $this->createEmployee('Budi');
        $service = Service::query()->create([
            'name' => 'Haircut',
            'price' => '40000.00',
        ]);
        $product = Product::query()->create([
            'name' => 'Pomade',
            'price' => '30000.00',
            'stock' => 10,
        ]);

        $response = $this->actingAs($user)->post(route('transactions.daily-batch.store'), [
            'transaction_date' => '2026-03-19',
            'entries' => [
                [
                    'employee_id' => $employee->id,
                    'payment_method' => 'cash',
                    'notes' => 'Campuran',
                    'items' => [
                        [
                            'item_type' => 'service',
                            'service_id' => $service->id,
                        ],
                        [
                            'item_type' => 'product',
                            'product_id' => $product->id,
                            'qty' => 1,
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect(route('transactions.index'));
        $response->assertSessionMissing('error');

        $this->assertDatabaseHas('transactions', [
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'notes' => 'Campuran',
            'total_amount' => '70000.00',
        ]);
        $this->assertDatabaseHas('transaction_items', [
            'item_type' => 'service',
            'service_id' => $service->id,
            'employee_id' => $employee->id,
        ]);
        $this->assertDatabaseHas('transaction_items', [
            'item_type' => 'product',
            'product_id' => $product->id,
            'employee_id' => $employee->id,
            'qty' => 1,
        ]);
    }

    public function test_daily_batch_submits_two_valid_blocks_in_one_request(): void
    {
        $user = User::factory()->create();
        $employeeOne = $this->createEmployee('Budi');
        $employeeTwo = $this->createEmployee('Sari');
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
            'transaction_date' => '2026-03-19',
            'entries' => [
                [
                    'employee_id' => $employeeOne->id,
                    'payment_method' => 'cash',
                    'notes' => 'Blok 1',
                    'items' => [
                        [
                            'item_type' => 'service',
                            'service_id' => $service->id,
                        ],
                    ],
                ],
                [
                    'employee_id' => $employeeTwo->id,
                    'payment_method' => 'qr',
                    'notes' => 'Blok 2',
                    'items' => [
                        [
                            'item_type' => 'product',
                            'product_id' => $product->id,
                            'qty' => 2,
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect(route('transactions.index'));
        $response->assertSessionMissing('error');

        $this->assertDatabaseCount('transactions', 2);
        $this->assertDatabaseHas('transactions', [
            'employee_id' => $employeeOne->id,
            'payment_method' => 'cash',
            'notes' => 'Blok 1',
        ]);
        $this->assertDatabaseHas('transactions', [
            'employee_id' => $employeeTwo->id,
            'payment_method' => 'qr',
            'notes' => 'Blok 2',
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
            'qty' => 2,
        ]);
    }

    public function test_daily_batch_validation_returns_field_errors_without_generic_flash_message(): void
    {
        $user = User::factory()->create();
        $employee = $this->createEmployee('Budi');

        $response = $this->actingAs($user)
            ->from(route('transactions.daily-batch.create'))
            ->post(route('transactions.daily-batch.store'), [
                'transaction_date' => '2026-03-19',
                'entries' => [
                    [
                        'employee_id' => $employee->id,
                        'payment_method' => 'cash',
                        'notes' => 'Kurang item',
                        'items' => [
                            [
                                'item_type' => 'product',
                                'product_id' => '',
                                'qty' => '',
                            ],
                        ],
                    ],
                ],
            ]);

        $response->assertRedirect(route('transactions.daily-batch.create'));
        $response->assertSessionHasErrors([
            'entries.0.items.0.product_id',
            'entries.0.items.0.qty',
        ]);
        $response->assertSessionMissing('error');
        $this->assertDatabaseCount('transactions', 0);
    }

    private function createEmployee(string $name): Employee
    {
        return Employee::query()->create([
            'name' => $name,
            'status' => 'tetap',
            'employment_type' => Employee::EMPLOYMENT_TYPE_PERMANENT,
            'is_active' => true,
        ]);
    }
}
