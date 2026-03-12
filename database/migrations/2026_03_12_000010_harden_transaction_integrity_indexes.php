<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
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

        Schema::table('transactions', function (Blueprint $table): void {
            $table->date('transaction_date')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('transactions') || ! Schema::hasColumn('transactions', 'transaction_date')) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table): void {
            $table->date('transaction_date')->nullable()->change();
        });
    }
};
