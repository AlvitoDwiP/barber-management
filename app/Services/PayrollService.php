<?php

namespace App\Services;

use App\Models\PayrollPeriod;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    public function openPayroll(): PayrollPeriod
    {
        return DB::transaction(function (): PayrollPeriod {
            $hasOpenPayroll = PayrollPeriod::query()
                ->where('status', 'open')
                ->exists();

            if ($hasOpenPayroll) {
                throw new DomainException('Masih ada payroll open yang belum ditutup.');
            }

            $latestClosedPayroll = PayrollPeriod::query()
                ->where('status', 'closed')
                ->whereNotNull('end_date')
                ->orderByDesc('end_date')
                ->orderByDesc('id')
                ->first();

            $startDate = $latestClosedPayroll !== null
                ? $latestClosedPayroll->end_date->copy()->addDay()
                : Carbon::today();

            return PayrollPeriod::query()->create([
                'start_date' => $startDate,
                'end_date' => null,
                'status' => 'open',
                'closed_at' => null,
            ]);
        });
    }
}
