<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $today = now()->toDateString();

        DB::table('payroll_periods')
            ->where('status', 'open')
            ->whereDate('start_date', '>', $today)
            ->update([
                'start_date' => $today,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Data normalization is intentionally irreversible.
    }
};
