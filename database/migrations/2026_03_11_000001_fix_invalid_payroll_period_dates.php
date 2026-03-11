<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $invalidPayrolls = DB::table('payroll_periods')
            ->whereNotNull('end_date')
            ->whereColumn('start_date', '>', 'end_date')
            ->orderBy('id')
            ->get(['id', 'start_date', 'end_date']);

        foreach ($invalidPayrolls as $payroll) {
            $previousEndDate = DB::table('payroll_periods')
                ->where('id', '<', $payroll->id)
                ->whereNotNull('end_date')
                ->orderByDesc('id')
                ->value('end_date');

            $candidateFromPrevious = $previousEndDate !== null
                ? Carbon::parse($previousEndDate)->addDay()->toDateString()
                : null;

            $candidateFromTransactions = DB::table('transactions')
                ->where('payroll_period_id', $payroll->id)
                ->min('transaction_date');

            $fixedStartDate = $candidateFromPrevious;

            if ($fixedStartDate === null || $fixedStartDate > $payroll->end_date) {
                $fixedStartDate = $candidateFromTransactions ?: $payroll->end_date;
            }

            DB::table('payroll_periods')
                ->where('id', $payroll->id)
                ->update([
                    'start_date' => $fixedStartDate,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        // Intentionally left blank because data correction is not safely reversible.
    }
};
