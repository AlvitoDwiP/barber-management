<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('transactions')) {
            Schema::table('transactions', function (Blueprint $table): void {
                $table->index('created_at', 'transactions_created_at_report_idx');
                $table->index('payment_method', 'transactions_payment_method_report_idx');
            });
        }

        if (Schema::hasTable('transaction_items')) {
            Schema::table('transaction_items', function (Blueprint $table): void {
                $table->index('item_type', 'transaction_items_item_type_report_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('transactions')) {
            Schema::table('transactions', function (Blueprint $table): void {
                $table->dropIndex('transactions_created_at_report_idx');
                $table->dropIndex('transactions_payment_method_report_idx');
            });
        }

        if (Schema::hasTable('transaction_items')) {
            Schema::table('transaction_items', function (Blueprint $table): void {
                $table->dropIndex('transaction_items_item_type_report_idx');
            });
        }
    }
};
