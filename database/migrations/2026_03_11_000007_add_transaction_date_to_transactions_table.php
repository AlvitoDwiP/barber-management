<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transactions')) {
            return;
        }

        if (! Schema::hasColumn('transactions', 'transaction_date')) {
            Schema::table('transactions', function (Blueprint $table): void {
                $table->date('transaction_date')->nullable()->after('transaction_code');
            });
        }

        if (! Schema::hasIndex('transactions', ['transaction_date'])) {
            Schema::table('transactions', function (Blueprint $table): void {
                $table->index('transaction_date', 'transactions_transaction_date_report_idx');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('transactions')) {
            return;
        }

        if (Schema::hasIndex('transactions', 'transactions_transaction_date_report_idx')) {
            Schema::table('transactions', function (Blueprint $table): void {
                $table->dropIndex('transactions_transaction_date_report_idx');
            });
        }
    }
};

