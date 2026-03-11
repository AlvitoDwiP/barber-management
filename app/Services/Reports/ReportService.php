<?php

namespace App\Services\Reports;

class ReportService
{
    public function __construct(
        private readonly DailyReportService $dailyReportService,
        private readonly MonthlyReportService $monthlyReportService,
        private readonly EmployeePerformanceReportService $employeePerformanceReportService,
        private readonly ProductReportService $productReportService,
    ) {
    }

    public function getTodaySummary(): array
    {
        return $this->dailyReportService->getTodaySummary();
    }

    public function getMonthlySummary(): array
    {
        return $this->monthlyReportService->getCurrentMonthSummary();
    }

    public function getTopEmployeeOfMonth(): ?array
    {
        return $this->employeePerformanceReportService->getTopEmployeeOfMonth();
    }

    public function getTopProductOfMonth(): ?array
    {
        return $this->productReportService->getTopProductOfMonth();
    }
}
