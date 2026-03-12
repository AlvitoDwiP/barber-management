<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->backfillTransactionDate();
        $this->addTransactionIndexes();
        $this->addTransactionItemIndexes();
    }

    public function down(): void
    {
        $this->dropTransactionIndexes();
        $this->dropTransactionItemIndexes();
    }

    private function backfillTransactionDate(): void
    {
        if (! Schema::hasTable('transactions') || ! Schema::hasColumn('transactions', 'transaction_date')) {
            return;
        }

        $fallbackDateExpression = Schema::hasColumn('transactions', 'created_at')
            ? DB::raw('COALESCE(DATE(created_at), CURRENT_DATE)')
            : DB::raw('CURRENT_DATE');

        DB::table('transactions')
            ->whereNull('transaction_date')
            ->update([
                'transaction_date' => $fallbackDateExpression,
            ]);
    }

    private function addTransactionIndexes(): void
    {
        if (! Schema::hasTable('transactions')) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table): void {
            if (! Schema::hasIndex('transactions', ['transaction_code'])) {
                $table->unique('transaction_code', 'transactions_transaction_code_integrity_unique');
            }

            if (! Schema::hasIndex('transactions', ['transaction_date'])) {
                $table->index('transaction_date', 'transactions_transaction_date_integrity_idx');
            }
        });
    }

    private function addTransactionItemIndexes(): void
    {
        if (! Schema::hasTable('transaction_items')) {
            return;
        }

        Schema::table('transaction_items', function (Blueprint $table): void {
            if (! Schema::hasIndex('transaction_items', ['transaction_id'])) {
                $table->index('transaction_id', 'transaction_items_transaction_id_integrity_idx');
            }

            if (! Schema::hasIndex('transaction_items', ['product_id'])) {
                $table->index('product_id', 'transaction_items_product_id_integrity_idx');
            }

            if (! Schema::hasIndex('transaction_items', ['service_id'])) {
                $table->index('service_id', 'transaction_items_service_id_integrity_idx');
            }
        });
    }

    private function dropTransactionIndexes(): void
    {
        if (! Schema::hasTable('transactions')) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table): void {
            if (Schema::hasIndex('transactions', 'transactions_transaction_code_integrity_unique')) {
                $table->dropUnique('transactions_transaction_code_integrity_unique');
            }

            if (Schema::hasIndex('transactions', 'transactions_transaction_date_integrity_idx')) {
                $table->dropIndex('transactions_transaction_date_integrity_idx');
            }
        });
    }

    private function dropTransactionItemIndexes(): void
    {
        if (! Schema::hasTable('transaction_items')) {
            return;
        }

        Schema::table('transaction_items', function (Blueprint $table): void {
            if (Schema::hasIndex('transaction_items', 'transaction_items_transaction_id_integrity_idx')) {
                $table->dropIndex('transaction_items_transaction_id_integrity_idx');
            }

            if (Schema::hasIndex('transaction_items', 'transaction_items_product_id_integrity_idx')) {
                $table->dropIndex('transaction_items_product_id_integrity_idx');
            }

            if (Schema::hasIndex('transaction_items', 'transaction_items_service_id_integrity_idx')) {
                $table->dropIndex('transaction_items_service_id_integrity_idx');
            }
        });
    }
};
