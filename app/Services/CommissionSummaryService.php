<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\TransactionItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CommissionSummaryService
{
    public function getPayrollSnapshotRows(Collection $transactionIds): Collection
    {
        if ($transactionIds->isEmpty()) {
            return collect();
        }

        // Payroll must aggregate frozen transaction item snapshots, never live master commission rules.
        return $this->baseSummaryQuery(Employee::EMPLOYMENT_TYPE_PERMANENT)
            ->whereIn('transactions.id', $transactionIds)
            ->selectRaw("
                transactions.employee_id as employee_id,
                employees.name as employee_name,
                COUNT(DISTINCT transactions.id) as total_transaction_count,
                SUM(CASE WHEN transaction_items.item_type = 'service' THEN transaction_items.qty ELSE 0 END) as total_services,
                SUM(CASE WHEN transaction_items.item_type = 'product' THEN transaction_items.qty ELSE 0 END) as total_products,
                COALESCE(SUM(CASE WHEN transaction_items.item_type = 'service' THEN transaction_items.subtotal ELSE 0 END), 0) as total_service_amount,
                COALESCE(SUM(CASE WHEN transaction_items.item_type = 'service' THEN transaction_items.commission_amount ELSE 0 END), 0) as total_service_commission,
                COALESCE(SUM(CASE WHEN transaction_items.item_type = 'product' THEN transaction_items.commission_amount ELSE 0 END), 0) as total_product_commission,
                COALESCE(SUM(transaction_items.commission_amount), 0) as total_commission
            ")
            ->groupBy('transactions.employee_id', 'employees.name')
            ->orderBy('employees.name')
            ->get()
            ->map(function ($row): object {
                return (object) [
                    'employee_id' => (int) $row->employee_id,
                    'employee_name' => (string) $row->employee_name,
                    'total_transaction_count' => (int) $row->total_transaction_count,
                    'total_services' => (int) $row->total_services,
                    'total_products' => (int) $row->total_products,
                    'total_service_amount' => (string) $row->total_service_amount,
                    'total_service_commission' => (string) $row->total_service_commission,
                    'total_product_commission' => (string) $row->total_product_commission,
                    'total_commission' => (string) $row->total_commission,
                ];
            });
    }

    public function getFreelanceDailySummaries(string $startDate, string $endDate, ?int $employeeId = null): Collection
    {
        return $this->buildFreelanceSummaryQuery($startDate, $endDate, $employeeId)
            ->orderByDesc('transactions.transaction_date')
            ->orderBy('employees.name')
            ->get()
            ->map(fn ($row) => $this->mapFreelanceSummaryRow($row));
    }

    public function getFreelanceDailySummary(int $employeeId, string $workDate): ?object
    {
        $row = $this->buildFreelanceSummaryQuery($workDate, $workDate, $employeeId)
            ->whereDate('transactions.transaction_date', $workDate)
            ->first();

        return $row ? $this->mapFreelanceSummaryRow($row) : null;
    }

    private function buildFreelanceSummaryQuery(string $startDate, string $endDate, ?int $employeeId = null)
    {
        return $this->baseSummaryQuery(Employee::EMPLOYMENT_TYPE_FREELANCE)
            ->whereDate('transactions.transaction_date', '>=', $startDate)
            ->whereDate('transactions.transaction_date', '<=', $endDate)
            ->when($employeeId, fn ($query) => $query->where('transactions.employee_id', $employeeId))
            ->selectRaw("
                transactions.transaction_date as work_date,
                transactions.employee_id as employee_id,
                employees.name as employee_name,
                COUNT(DISTINCT transactions.id) as total_transaction_count,
                COALESCE(SUM(CASE WHEN transaction_items.item_type = 'service' THEN transaction_items.subtotal ELSE 0 END), 0) as total_service_amount,
                COALESCE(SUM(CASE WHEN transaction_items.item_type = 'service' THEN transaction_items.commission_amount ELSE 0 END), 0) as service_commission,
                SUM(CASE WHEN transaction_items.item_type = 'product' THEN transaction_items.qty ELSE 0 END) as total_product_qty,
                COALESCE(SUM(CASE WHEN transaction_items.item_type = 'product' THEN transaction_items.commission_amount ELSE 0 END), 0) as product_commission,
                COALESCE(SUM(transaction_items.commission_amount), 0) as total_commission
            ")
            ->groupBy('transactions.transaction_date', 'transactions.employee_id', 'employees.name');
    }

    private function baseSummaryQuery(string $employmentType)
    {
        return TransactionItem::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->join('employees', 'employees.id', '=', 'transactions.employee_id')
            ->where('employees.employment_type', $employmentType);
    }

    private function mapFreelanceSummaryRow(object $row): object
    {
        return (object) [
            'employee_id' => (int) $row->employee_id,
            'employee_name' => (string) $row->employee_name,
            'work_date' => Carbon::parse($row->work_date)->toDateString(),
            'total_transaction_count' => (int) $row->total_transaction_count,
            'total_service_amount' => (string) $row->total_service_amount,
            'service_commission' => (string) $row->service_commission,
            'total_product_qty' => (int) $row->total_product_qty,
            'product_commission' => (string) $row->product_commission,
            'total_commission' => (string) $row->total_commission,
        ];
    }
}
