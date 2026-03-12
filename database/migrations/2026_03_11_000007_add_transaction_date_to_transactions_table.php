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
    }

    public function down(): void
    {
        // Kept intentionally empty because transaction_date is part of the active schema.
    }
};
