<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->hardenTransactionsPayrollForeignKeys();
        $this->hardenPayrollResultForeignKey();
    }

    public function down(): void
    {
        $this->revertTransactionsPayrollForeignKeys();
        $this->revertPayrollResultForeignKey();
    }

    private function hardenTransactionsPayrollForeignKeys(): void
    {
        if (! Schema::hasTable('transactions')) {
            return;
        }

        if (Schema::hasColumn('transactions', 'payroll_id')) {
            Schema::table('transactions', function (Blueprint $table): void {
                $table->dropForeign(['payroll_id']);
            });

            Schema::table('transactions', function (Blueprint $table): void {
                $table->foreign('payroll_id')
                    ->references('id')
                    ->on('payroll_periods')
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();
            });
        }

        if (Schema::hasColumn('transactions', 'payroll_period_id')) {
            Schema::table('transactions', function (Blueprint $table): void {
                $table->dropForeign(['payroll_period_id']);
            });

            Schema::table('transactions', function (Blueprint $table): void {
                $table->foreign('payroll_period_id')
                    ->references('id')
                    ->on('payroll_periods')
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();
            });
        }
    }

    private function hardenPayrollResultForeignKey(): void
    {
        if (! Schema::hasTable('payroll_results')) {
            return;
        }

        Schema::table('payroll_results', function (Blueprint $table): void {
            $table->dropForeign(['payroll_period_id']);
        });

        Schema::table('payroll_results', function (Blueprint $table): void {
            $table->foreign('payroll_period_id')
                ->references('id')
                ->on('payroll_periods')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }

    private function revertTransactionsPayrollForeignKeys(): void
    {
        if (! Schema::hasTable('transactions')) {
            return;
        }

        if (Schema::hasColumn('transactions', 'payroll_id')) {
            Schema::table('transactions', function (Blueprint $table): void {
                $table->dropForeign(['payroll_id']);
            });

            Schema::table('transactions', function (Blueprint $table): void {
                $table->foreign('payroll_id')
                    ->references('id')
                    ->on('payroll_periods')
                    ->cascadeOnUpdate()
                    ->nullOnDelete();
            });
        }

        if (Schema::hasColumn('transactions', 'payroll_period_id')) {
            Schema::table('transactions', function (Blueprint $table): void {
                $table->dropForeign(['payroll_period_id']);
            });

            Schema::table('transactions', function (Blueprint $table): void {
                $table->foreign('payroll_period_id')
                    ->references('id')
                    ->on('payroll_periods')
                    ->cascadeOnUpdate()
                    ->nullOnDelete();
            });
        }
    }

    private function revertPayrollResultForeignKey(): void
    {
        if (! Schema::hasTable('payroll_results')) {
            return;
        }

        Schema::table('payroll_results', function (Blueprint $table): void {
            $table->dropForeign(['payroll_period_id']);
        });

        Schema::table('payroll_results', function (Blueprint $table): void {
            $table->foreign('payroll_period_id')
                ->references('id')
                ->on('payroll_periods')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }
};
