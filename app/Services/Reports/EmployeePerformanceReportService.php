<?php

namespace App\Services\Reports;

use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Services\Reports\Concerns\InteractsWithExactReportMoney;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EmployeePerformanceReportService
{
    use InteractsWithExactReportMoney;

    public function getTopEmployeeOfMonth(?Carbon $month = null): ?array
    {
        $targetMonth = $month ?? now();
        $year = (int) $targetMonth->year;
        $monthNumber = (int) $targetMonth->month;
        $startDate = Carbon::create($year, $monthNumber, 1)->startOfMonth()->toDateString();
        $endDate = Carbon::create($year, $monthNumber, 1)->endOfMonth()->toDateString();
        $dateExpression = $this->getDateExpression('transactions.transaction_date');

        $topEmployee = $this->usesItemEmployeeSnapshots()
            ? TransactionItem::query()
                ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
                ->where('transaction_items.item_type', 'service')
                ->whereBetween(DB::raw($dateExpression), [$startDate, $endDate])
                ->groupBy('transaction_items.employee_id')
                ->selectRaw('
                    MAX(transaction_items.employee_name) as employee_name,
                    COALESCE(SUM(transaction_items.qty), 0) as service_count
                ')
                ->orderByDesc('service_count')
                ->first()
            : TransactionItem::query()
                ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
                ->join('employees', 'employees.id', '=', 'transactions.employee_id')
                ->where('transaction_items.item_type', 'service')
                ->whereBetween(DB::raw($dateExpression), [$startDate, $endDate])
                ->groupBy('transactions.employee_id', 'employees.name')
                ->selectRaw('
                    employees.name as employee_name,
                    COALESCE(SUM(transaction_items.qty), 0) as service_count
                ')
                ->orderByDesc('service_count')
                ->first();

        return [
            'employee_name' => $topEmployee?->employee_name,
            'service_count' => (int) ($topEmployee?->service_count ?? 0),
        ];
    }

    public function getEmployeePerformanceReport(string $startDate, string $endDate, ?int $employeeId = null): Collection
    {
        $dateExpression = $this->getDateExpression('transactions.transaction_date');

        $query = $this->usesItemEmployeeSnapshots()
            ? TransactionItem::query()
                ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
                ->whereBetween(DB::raw($dateExpression), [$startDate, $endDate])
                ->when($employeeId !== null, fn ($builder) => $builder->where('transaction_items.employee_id', $employeeId))
                ->groupBy('transaction_items.employee_id')
                ->selectRaw('
                    MAX(transaction_items.employee_name) as employee_name,
                    COUNT(DISTINCT transaction_items.transaction_id) as total_transactions,
                    COALESCE(SUM(CASE WHEN transaction_items.item_type = ? THEN transaction_items.qty ELSE 0 END), 0) as total_services,
                    COALESCE(SUM(CASE WHEN transaction_items.item_type = ? THEN transaction_items.subtotal ELSE 0 END), 0) as service_revenue,
                    COALESCE(SUM(CASE WHEN transaction_items.item_type = ? THEN transaction_items.qty ELSE 0 END), 0) as total_products,
                    COALESCE(SUM(CASE WHEN transaction_items.item_type = ? THEN transaction_items.subtotal ELSE 0 END), 0) as product_revenue,
                    COALESCE(SUM(transaction_items.commission_amount), 0) as total_commission
                ', ['service', 'service', 'product', 'product'])
                ->orderByDesc('total_commission')
                ->orderBy('employee_name')
            : Transaction::query()
                ->join('employees', 'employees.id', '=', 'transactions.employee_id')
                ->leftJoin('transaction_items', 'transaction_items.transaction_id', '=', 'transactions.id')
                ->whereBetween(DB::raw($dateExpression), [$startDate, $endDate])
                ->when($employeeId !== null, fn ($builder) => $builder->where('transactions.employee_id', $employeeId))
                ->groupBy('employees.id', 'employees.name')
                ->selectRaw('
                    employees.name as employee_name,
                    COUNT(DISTINCT transactions.id) as total_transactions,
                    COALESCE(SUM(CASE WHEN transaction_items.item_type = ? THEN transaction_items.qty ELSE 0 END), 0) as total_services,
                    COALESCE(SUM(CASE WHEN transaction_items.item_type = ? THEN transaction_items.subtotal ELSE 0 END), 0) as service_revenue,
                    COALESCE(SUM(CASE WHEN transaction_items.item_type = ? THEN transaction_items.qty ELSE 0 END), 0) as total_products,
                    COALESCE(SUM(CASE WHEN transaction_items.item_type = ? THEN transaction_items.subtotal ELSE 0 END), 0) as product_revenue,
                    COALESCE(SUM(transaction_items.commission_amount), 0) as total_commission
                ', ['service', 'service', 'product', 'product'])
                ->orderByDesc('total_commission')
                ->orderBy('employees.name');

        return $query->get()
            ->map(function ($row) {
                return [
                    'employee_name' => $row->employee_name,
                    'total_transactions' => (int) $row->total_transactions,
                    'total_services' => (int) $row->total_services,
                    'service_revenue' => $this->moneyToDecimal($row->service_revenue),
                    'total_products' => (int) $row->total_products,
                    'product_revenue' => $this->moneyToDecimal($row->product_revenue),
                    'total_commission' => $this->moneyToDecimal($row->total_commission),
                ];
            });
    }

    private function usesItemEmployeeSnapshots(): bool
    {
        return Schema::hasColumns('transaction_items', [
            'employee_id',
            'employee_name',
        ]);
    }

    private function getDateExpression(string $column): string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return "date({$column})";
        }

        return "DATE({$column})";
    }
}
