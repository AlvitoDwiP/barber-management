<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addTransactionsIndexes();
        $this->addTransactionDetailsIndexes();
        $this->addTransactionItemsIndexes();
    }

    public function down(): void
    {
        $this->dropTransactionsIndexes();
        $this->dropTransactionDetailsIndexes();
        $this->dropTransactionItemsIndexes();
    }

    private function addTransactionsIndexes(): void
    {
        if (! Schema::hasTable('transactions')) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table): void {
            if (! Schema::hasIndex('transactions', ['created_at'])) {
                $table->index('created_at', 'transactions_created_at_report_idx');
            }

            if (! Schema::hasIndex('transactions', ['transaction_date'])) {
                $table->index('transaction_date', 'transactions_transaction_date_report_idx');
            }

            if (! Schema::hasIndex('transactions', ['payment_method'])) {
                $table->index('payment_method', 'transactions_payment_method_report_idx');
            }
        });
    }

    private function addTransactionDetailsIndexes(): void
    {
        if (! Schema::hasTable('transaction_details')) {
            return;
        }

        Schema::table('transaction_details', function (Blueprint $table): void {
            if (! Schema::hasIndex('transaction_details', ['transaction_id'])) {
                $table->index('transaction_id', 'transaction_details_transaction_id_report_idx');
            }

            if (! Schema::hasIndex('transaction_details', ['item_type'])) {
                $table->index('item_type', 'transaction_details_item_type_report_idx');
            }

            if (Schema::hasColumn('transaction_details', 'employee_id')
                && ! Schema::hasIndex('transaction_details', ['employee_id'])) {
                $table->index('employee_id', 'transaction_details_employee_id_report_idx');
            }
        });
    }

    private function addTransactionItemsIndexes(): void
    {
        if (! Schema::hasTable('transaction_items')) {
            return;
        }

        Schema::table('transaction_items', function (Blueprint $table): void {
            if (! Schema::hasIndex('transaction_items', ['transaction_id'])) {
                $table->index('transaction_id', 'transaction_items_transaction_id_report_idx');
            }

            if (! Schema::hasIndex('transaction_items', ['item_type'])) {
                $table->index('item_type', 'transaction_items_item_type_report_idx');
            }

            if (Schema::hasColumn('transaction_items', 'employee_id')
                && ! Schema::hasIndex('transaction_items', ['employee_id'])) {
                $table->index('employee_id', 'transaction_items_employee_id_report_idx');
            }
        });
    }

    private function dropTransactionsIndexes(): void
    {
        if (! Schema::hasTable('transactions')) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table): void {
            if (Schema::hasIndex('transactions', 'transactions_created_at_report_idx')) {
                $table->dropIndex('transactions_created_at_report_idx');
            }

            if (Schema::hasIndex('transactions', 'transactions_transaction_date_report_idx')) {
                $table->dropIndex('transactions_transaction_date_report_idx');
            }

            if (Schema::hasIndex('transactions', 'transactions_payment_method_report_idx')) {
                $table->dropIndex('transactions_payment_method_report_idx');
            }
        });
    }

    private function dropTransactionDetailsIndexes(): void
    {
        if (! Schema::hasTable('transaction_details')) {
            return;
        }

        Schema::table('transaction_details', function (Blueprint $table): void {
            if (Schema::hasIndex('transaction_details', 'transaction_details_transaction_id_report_idx')) {
                $table->dropIndex('transaction_details_transaction_id_report_idx');
            }

            if (Schema::hasIndex('transaction_details', 'transaction_details_item_type_report_idx')) {
                $table->dropIndex('transaction_details_item_type_report_idx');
            }

            if (Schema::hasIndex('transaction_details', 'transaction_details_employee_id_report_idx')) {
                $table->dropIndex('transaction_details_employee_id_report_idx');
            }
        });
    }

    private function dropTransactionItemsIndexes(): void
    {
        if (! Schema::hasTable('transaction_items')) {
            return;
        }

        Schema::table('transaction_items', function (Blueprint $table): void {
            if (Schema::hasIndex('transaction_items', 'transaction_items_transaction_id_report_idx')) {
                $table->dropIndex('transaction_items_transaction_id_report_idx');
            }

            if (Schema::hasIndex('transaction_items', 'transaction_items_item_type_report_idx')) {
                $table->dropIndex('transaction_items_item_type_report_idx');
            }

            if (Schema::hasIndex('transaction_items', 'transaction_items_employee_id_report_idx')) {
                $table->dropIndex('transaction_items_employee_id_report_idx');
            }
        });
    }
};

