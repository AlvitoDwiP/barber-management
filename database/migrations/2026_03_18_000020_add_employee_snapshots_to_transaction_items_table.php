<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumns('transaction_items', [
            'employee_id',
            'employee_name',
            'employee_employment_type',
        ])) {
            return;
        }

        Schema::table('transaction_items', function (Blueprint $table) {
            $table->foreignId('employee_id')
                ->nullable()
                ->after('product_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->string('employee_name')->nullable()->after('employee_id');
            $table->string('employee_employment_type')->nullable()->after('employee_name');

            $table->index('employee_id');
            $table->index('employee_employment_type');
        });

        $rows = DB::table('transaction_items')
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->join('employees', 'employees.id', '=', 'transactions.employee_id')
            ->select(
                'transaction_items.id',
                'transactions.employee_id as transaction_employee_id',
                'employees.name as employee_name',
                'employees.employment_type as employee_employment_type',
                'employees.status as employee_status'
            )
            ->get();

        foreach ($rows as $row) {
            DB::table('transaction_items')
                ->where('id', $row->id)
                ->update([
                    'employee_id' => $row->transaction_employee_id,
                    'employee_name' => $row->employee_name,
                    'employee_employment_type' => $row->employee_employment_type
                        ?: match ($row->employee_status) {
                            'tetap' => 'permanent',
                            'freelance' => 'freelance',
                            default => null,
                        },
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('transaction_items', function (Blueprint $table) {
            $table->dropIndex(['employee_id']);
            $table->dropIndex(['employee_employment_type']);
            $table->dropConstrainedForeignId('employee_id');
            $table->dropColumn([
                'employee_name',
                'employee_employment_type',
            ]);
        });
    }
};
