<?php

namespace App\Services\Reports;

use App\Services\Reports\Concerns\InteractsWithExactReportMoney;

class BusinessMetricService
{
    use InteractsWithExactReportMoney;

    public function buildCashFlowSummary(mixed $cash = 0, mixed $qr = 0): array
    {
        $cashMinorUnits = $this->moneyToMinorUnits($cash);
        $qrMinorUnits = $this->moneyToMinorUnits($qr);
        $cashInMinorUnits = $cashMinorUnits + $qrMinorUnits;

        return [
            'cash' => $this->moneyFromMinorUnits($cashMinorUnits),
            'qr' => $this->moneyFromMinorUnits($qrMinorUnits),
            'cash_in' => $this->moneyFromMinorUnits($cashInMinorUnits),
        ];
    }

    public function buildOperatingPerformanceSummary(
        mixed $serviceRevenue = 0,
        mixed $productRevenue = 0,
        mixed $barberCommissions = 0,
        mixed $operationalExpenses = 0,
    ): array {
        $serviceRevenueMinorUnits = $this->moneyToMinorUnits($serviceRevenue);
        $productRevenueMinorUnits = $this->moneyToMinorUnits($productRevenue);
        $barberCommissionsMinorUnits = $this->moneyToMinorUnits($barberCommissions);
        $operationalExpensesMinorUnits = $this->moneyToMinorUnits($operationalExpenses);

        $totalRevenueMinorUnits = $serviceRevenueMinorUnits + $productRevenueMinorUnits;
        $totalOperatingExpensesMinorUnits = $barberCommissionsMinorUnits + $operationalExpensesMinorUnits;
        $operatingProfitMinorUnits = $totalRevenueMinorUnits - $totalOperatingExpensesMinorUnits;
        $legacyBarberIncomeMinorUnits = $totalRevenueMinorUnits - $barberCommissionsMinorUnits;

        return [
            'service_revenue' => $this->moneyFromMinorUnits($serviceRevenueMinorUnits),
            'product_revenue' => $this->moneyFromMinorUnits($productRevenueMinorUnits),
            'total_revenue' => $this->moneyFromMinorUnits($totalRevenueMinorUnits),
            'barber_commissions' => $this->moneyFromMinorUnits($barberCommissionsMinorUnits),
            'operational_expenses' => $this->moneyFromMinorUnits($operationalExpensesMinorUnits),
            'total_operating_expenses' => $this->moneyFromMinorUnits($totalOperatingExpensesMinorUnits),
            'operating_profit' => $this->moneyFromMinorUnits($operatingProfitMinorUnits),

            // Legacy aliases retained to keep non-report business code and older tests stable during the refactor.
            'employee_fees' => $this->moneyFromMinorUnits($barberCommissionsMinorUnits),
            'employee_commissions' => $this->moneyFromMinorUnits($barberCommissionsMinorUnits),
            'expenses' => $this->moneyFromMinorUnits($operationalExpensesMinorUnits),
            'profit' => $this->moneyFromMinorUnits($operatingProfitMinorUnits),
            'net_profit' => $this->moneyFromMinorUnits($operatingProfitMinorUnits),
            'net_income' => $this->moneyFromMinorUnits($operatingProfitMinorUnits),
            'barber_income' => $this->moneyFromMinorUnits($legacyBarberIncomeMinorUnits),
        ];
    }
}
