<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('transactions', 'payroll_id')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->foreignId('payroll_id')
                    ->nullable()
                    ->after('payroll_period_id')
                    ->constrained('payroll_periods')
                    ->nullOnDelete();
            });
        }

        // Backfill from legacy column when available.
        DB::table('transactions')
            ->whereNull('payroll_id')
            ->whereNotNull('payroll_period_id')
            ->update([
                'payroll_id' => DB::raw('payroll_period_id'),
            ]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('transactions', 'payroll_id')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropForeign(['payroll_id']);
                $table->dropColumn('payroll_id');
            });
        }
    }
};
