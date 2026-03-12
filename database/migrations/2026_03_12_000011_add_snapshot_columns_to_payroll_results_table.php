<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addPayrollResultSnapshotColumns();
        $this->syncLegacyPayrollLinks();
        $this->backfillPayrollResultSnapshots();
        $this->addPayrollIndexes();
    }

    public function down(): void
    {
        $this->dropPayrollIndexes();
        $this->dropPayrollResultSnapshotColumns();
    }

    private function addPayrollResultSnapshotColumns(): void
    {
        if (! Schema::hasTable('payroll_results')) {
            return;
        }

        Schema::table('payroll_results', function (Blueprint $table): void {
            if (! Schema::hasColumn('payroll_results', 'employee_name')) {
                $table->string('employee_name')->nullable()->after('employee_id');
            }

            if (! Schema::hasColumn('payroll_results', 'total_transaction_count')) {
                $table->unsignedInteger('total_transaction_count')->default(0)->after('total_transactions');
            }

            if (! Schema::hasColumn('payroll_results', 'total_service_amount')) {
                $table->decimal('total_service_amount', 12, 2)->default(0)->after('total_products');
            }

            if (! Schema::hasColumn('payroll_results', 'total_service_commission')) {
                $table->decimal('total_service_commission', 12, 2)->default(0)->after('total_service_amount');
            }

            if (! Schema::hasColumn('payroll_results', 'total_product_commission')) {
                $table->decimal('total_product_commission', 12, 2)->default(0)->after('total_service_commission');
            }
        });
    }

    private function syncLegacyPayrollLinks(): void
    {
        if (! Schema::hasTable('transactions')
            || ! Schema::hasColumn('transactions', 'payroll_id')
            || ! Schema::hasColumn('transactions', 'payroll_period_id')) {
            return;
        }

        DB::table('transactions')
            ->whereNotNull('payroll_id')
            ->where(function ($query): void {
                $query->whereNull('payroll_period_id')
                    ->orWhereColumn('payroll_period_id', '!=', 'payroll_id');
            })
            ->update([
                'payroll_period_id' => DB::raw('payroll_id'),
            ]);
    }

    private function backfillPayrollResultSnapshots(): void
    {
        if (! Schema::hasTable('payroll_results')) {
            return;
        }

        $employeeSnapshots = DB::table('payroll_results')
            ->join('employees', 'employees.id', '=', 'payroll_results.employee_id')
            ->whereNull('payroll_results.employee_name')
            ->get([
                'payroll_results.id',
                'employees.name as employee_name',
            ]);

        foreach ($employeeSnapshots as $snapshot) {
            DB::table('payroll_results')
                ->where('id', $snapshot->id)
                ->update([
                    'employee_name' => $snapshot->employee_name,
                ]);
        }

        $snapshotRows = DB::table('transactions')
            ->join('transaction_items', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->join('employees', 'employees.id', '=', 'transactions.employee_id')
            ->whereNotNull('transactions.payroll_id')
            ->selectRaw("
                transactions.payroll_id as payroll_period_id,
                transactions.employee_id as employee_id,
                employees.name as employee_name,
                COUNT(DISTINCT transactions.id) as total_transaction_count,
                SUM(CASE WHEN transaction_items.item_type = 'service' THEN transaction_items.qty ELSE 0 END) as total_services,
                SUM(CASE WHEN transaction_items.item_type = 'product' THEN transaction_items.qty ELSE 0 END) as total_products,
                COALESCE(SUM(CASE WHEN transaction_items.item_type = 'service' THEN transaction_items.subtotal ELSE 0 END), 0) as total_service_amount,
                COALESCE(SUM(CASE WHEN transaction_items.item_type = 'service' THEN transaction_items.commission_amount ELSE 0 END), 0) as total_service_commission,
                COALESCE(SUM(CASE WHEN transaction_items.item_type = 'product' THEN transaction_items.commission_amount ELSE 0 END), 0) as total_product_commission,
                COALESCE(SUM(transaction_items.commission_amount), 0) as total_commission
            ")
            ->groupBy('transactions.payroll_id', 'transactions.employee_id', 'employees.name')
            ->get();

        foreach ($snapshotRows as $row) {
            DB::table('payroll_results')
                ->where('payroll_period_id', $row->payroll_period_id)
                ->where('employee_id', $row->employee_id)
                ->update([
                    'employee_name' => $row->employee_name,
                    'total_transactions' => (int) $row->total_transaction_count,
                    'total_transaction_count' => (int) $row->total_transaction_count,
                    'total_services' => (int) $row->total_services,
                    'total_products' => (int) $row->total_products,
                    'total_service_amount' => $row->total_service_amount,
                    'total_service_commission' => $row->total_service_commission,
                    'total_product_commission' => $row->total_product_commission,
                    'total_commission' => $row->total_commission,
                    'updated_at' => now(),
                ]);
        }

        DB::table('payroll_results')
            ->where('total_transaction_count', 0)
            ->where('total_transactions', '>', 0)
            ->update([
                'total_transaction_count' => DB::raw('total_transactions'),
            ]);
    }

    private function addPayrollIndexes(): void
    {
        if (Schema::hasTable('payroll_periods')) {
            Schema::table('payroll_periods', function (Blueprint $table): void {
                if (! Schema::hasIndex('payroll_periods', ['status', 'start_date'])) {
                    $table->index(['status', 'start_date'], 'payroll_periods_status_start_date_idx');
                }
            });
        }

        if (Schema::hasTable('payroll_results')) {
            Schema::table('payroll_results', function (Blueprint $table): void {
                if (! Schema::hasIndex('payroll_results', ['payroll_period_id', 'employee_name'])) {
                    $table->index(['payroll_period_id', 'employee_name'], 'payroll_results_period_employee_name_idx');
                }
            });
        }
    }

    private function dropPayrollIndexes(): void
    {
        if (Schema::hasTable('payroll_periods')) {
            Schema::table('payroll_periods', function (Blueprint $table): void {
                if (Schema::hasIndex('payroll_periods', 'payroll_periods_status_start_date_idx')) {
                    $table->dropIndex('payroll_periods_status_start_date_idx');
                }
            });
        }

        if (Schema::hasTable('payroll_results')) {
            Schema::table('payroll_results', function (Blueprint $table): void {
                if (Schema::hasIndex('payroll_results', 'payroll_results_period_employee_name_idx')) {
                    $table->dropIndex('payroll_results_period_employee_name_idx');
                }
            });
        }
    }

    private function dropPayrollResultSnapshotColumns(): void
    {
        if (! Schema::hasTable('payroll_results')) {
            return;
        }

        Schema::table('payroll_results', function (Blueprint $table): void {
            $columns = [
                'employee_name',
                'total_transaction_count',
                'total_service_amount',
                'total_service_commission',
                'total_product_commission',
            ];

            $existingColumns = array_values(array_filter($columns, fn (string $column) => Schema::hasColumn('payroll_results', $column)));

            if ($existingColumns !== []) {
                $table->dropColumn($existingColumns);
            }
        });
    }
};
