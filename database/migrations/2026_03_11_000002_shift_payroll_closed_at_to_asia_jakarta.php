<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement("UPDATE payroll_periods SET closed_at = datetime(closed_at, '+7 hours') WHERE closed_at IS NOT NULL");

            return;
        }

        if ($driver === 'mysql') {
            DB::statement("UPDATE payroll_periods SET closed_at = DATE_ADD(closed_at, INTERVAL 7 HOUR) WHERE closed_at IS NOT NULL");

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement("UPDATE payroll_periods SET closed_at = closed_at + INTERVAL '7 hour' WHERE closed_at IS NOT NULL");
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement("UPDATE payroll_periods SET closed_at = datetime(closed_at, '-7 hours') WHERE closed_at IS NOT NULL");

            return;
        }

        if ($driver === 'mysql') {
            DB::statement("UPDATE payroll_periods SET closed_at = DATE_SUB(closed_at, INTERVAL 7 HOUR) WHERE closed_at IS NOT NULL");

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement("UPDATE payroll_periods SET closed_at = closed_at - INTERVAL '7 hour' WHERE closed_at IS NOT NULL");
        }
    }
};
