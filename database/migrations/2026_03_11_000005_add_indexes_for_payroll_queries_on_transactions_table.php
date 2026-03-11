<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->index('payroll_id', 'transactions_payroll_id_idx');
            $table->index('transaction_date', 'transactions_transaction_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_payroll_id_idx');
            $table->dropIndex('transactions_transaction_date_idx');
        });
    }
};
