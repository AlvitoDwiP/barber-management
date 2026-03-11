<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transactions')) {
            return;
        }

        if (! Schema::hasColumn('transactions', 'transaction_date')) {
            return;
        }

        if (! Schema::hasColumn('transactions', 'created_at')) {
            return;
        }

        DB::table('transactions')
            ->whereNull('transaction_date')
            ->update([
                'transaction_date' => DB::raw('DATE(created_at)'),
            ]);
    }

    public function down(): void
    {
        // No rollback needed for data backfill.
    }
};

