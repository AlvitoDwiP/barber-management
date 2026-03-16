<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Expense;
use App\Models\FreelancePayment;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FreelancePaymentService
{
    public function __construct(
        private readonly CommissionSummaryService $commissionSummaryService,
    ) {
    }

    public function normalizeFilters(array $filters): array
    {
        $startDate = Carbon::parse($filters['start_date'] ?? now())->toDateString();
        $endDate = Carbon::parse($filters['end_date'] ?? $startDate)->toDateString();

        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'employee_id' => blank($filters['employee_id'] ?? null) ? null : (int) $filters['employee_id'],
        ];
    }

    public function getIndexRows(array $filters): Collection
    {
        $filters = $this->normalizeFilters($filters);

        $rowsByKey = collect();

        $payments = FreelancePayment::query()
            ->with(['employee:id,name', 'expense:id,expense_date'])
            ->whereBetween('work_date', [$filters['start_date'], $filters['end_date']])
            ->when($filters['employee_id'], fn ($query, $employeeId) => $query->where('employee_id', $employeeId))
            ->get();

        foreach ($payments as $payment) {
            $rowsByKey->put($this->makeRowKey($payment->employee_id, $payment->work_date?->toDateString()), $this->mapPaymentRow($payment));
        }

        $liveSummaries = $this->commissionSummaryService->getFreelanceDailySummaries(
            $filters['start_date'],
            $filters['end_date'],
            $filters['employee_id'],
        );

        foreach ($liveSummaries as $summary) {
            $rowKey = $this->makeRowKey($summary->employee_id, $summary->work_date);

            if (! $rowsByKey->has($rowKey)) {
                $rowsByKey->put($rowKey, $this->mapSummaryRow($summary));
            }
        }

        return $rowsByKey
            ->sort(function (object $left, object $right): int {
                $dateComparison = strcmp($right->work_date, $left->work_date);

                if ($dateComparison !== 0) {
                    return $dateComparison;
                }

                return strcmp(mb_strtolower($left->employee_name), mb_strtolower($right->employee_name));
            })
            ->values();
    }

    public function preparePayment(int $employeeId, string $workDate): FreelancePayment
    {
        return DB::transaction(function () use ($employeeId, $workDate): FreelancePayment {
            $employee = Employee::query()
                ->whereKey($employeeId)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $employee->isFreelance()) {
                throw new DomainException('Hanya pegawai freelance yang dapat diproses pada halaman ini.');
            }

            $normalizedWorkDate = Carbon::parse($workDate)->toDateString();
            $summary = $this->commissionSummaryService->getFreelanceDailySummary($employee->id, $normalizedWorkDate);

            if ($summary === null) {
                throw new DomainException('Tidak ada komisi freelance yang dapat dibayar pada tanggal tersebut.');
            }

            $payment = FreelancePayment::query()
                ->where('employee_id', $employee->id)
                ->whereDate('work_date', $normalizedWorkDate)
                ->lockForUpdate()
                ->first();

            if ($payment?->isPaid()) {
                throw new DomainException('Komisi freelance untuk pegawai dan tanggal ini sudah dibayar.');
            }

            $paymentAttributes = [
                'total_service_amount' => $summary->total_service_amount,
                'service_commission' => $summary->service_commission,
                'total_product_qty' => $summary->total_product_qty,
                'product_commission' => $summary->product_commission,
                'total_commission' => $summary->total_commission,
                'payment_status' => FreelancePayment::STATUS_UNPAID,
                'expense_id' => null,
                'paid_at' => null,
            ];

            if ($payment) {
                $payment->fill($paymentAttributes);
                $payment->save();

                return $payment->fresh(['employee', 'expense']);
            }

            return FreelancePayment::query()->create([
                'employee_id' => $employee->id,
                'work_date' => $normalizedWorkDate,
                ...$paymentAttributes,
            ])->load(['employee', 'expense']);
        });
    }

    public function getExpenseDraft(int $paymentId): array
    {
        $payment = FreelancePayment::query()
            ->with('employee:id,name')
            ->findOrFail($paymentId);

        if ($payment->isPaid()) {
            throw new DomainException('Komisi freelance ini sudah dibayar dan tidak dapat diproses ulang.');
        }

        return [
            'freelance_payment_id' => $payment->id,
            'employee_name' => $payment->employee?->name ?? '-',
            'work_date' => $payment->work_date?->toDateString(),
            'work_date_label' => $payment->work_date?->locale('id')->translatedFormat('d F Y') ?? '-',
            'expense_date' => now()->toDateString(),
            'category' => Expense::CATEGORY_PAY_FREELANCE,
            'amount' => (string) $payment->total_commission,
            'note' => $this->buildExpenseNote($payment),
            'total_commission_label' => format_rupiah($payment->total_commission),
        ];
    }

    public function settlePaymentWithExpense(int $paymentId, array $expenseAttributes): Expense
    {
        return DB::transaction(function () use ($paymentId, $expenseAttributes): Expense {
            $payment = FreelancePayment::query()
                ->with('employee:id,name')
                ->whereKey($paymentId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($payment->isPaid()) {
                throw new DomainException('Komisi freelance ini sudah dibayar dan tidak dapat dibayar dua kali.');
            }

            if ($expenseAttributes['category'] !== Expense::CATEGORY_PAY_FREELANCE) {
                throw new DomainException('Kategori pengeluaran untuk pembayaran freelance harus Bayar Freelance.');
            }

            if ((float) $expenseAttributes['amount'] !== (float) $payment->total_commission) {
                throw new DomainException('Nominal pengeluaran harus sama dengan total komisi freelance settlement.');
            }

            $expense = Expense::query()->create([
                'expense_date' => Carbon::parse($expenseAttributes['expense_date'])->toDateString(),
                'category' => $expenseAttributes['category'],
                'amount' => $payment->total_commission,
                'note' => $expenseAttributes['note'] ?? $this->buildExpenseNote($payment),
            ]);

            $payment->update([
                'expense_id' => $expense->id,
                'paid_at' => now(),
                'payment_status' => FreelancePayment::STATUS_PAID,
            ]);

            return $expense;
        });
    }

    private function mapPaymentRow(FreelancePayment $payment): object
    {
        return (object) [
            'payment_id' => $payment->id,
            'employee_id' => $payment->employee_id,
            'employee_name' => $payment->employee?->name ?? '-',
            'work_date' => $payment->work_date?->toDateString() ?? null,
            'total_service_amount' => (string) $payment->total_service_amount,
            'service_commission' => (string) $payment->service_commission,
            'total_product_qty' => (int) $payment->total_product_qty,
            'product_commission' => (string) $payment->product_commission,
            'total_commission' => (string) $payment->total_commission,
            'payment_status' => $payment->payment_status,
            'paid_at' => $payment->paid_at,
            'expense_id' => $payment->expense_id,
        ];
    }

    private function mapSummaryRow(object $summary): object
    {
        return (object) [
            'payment_id' => null,
            'employee_id' => $summary->employee_id,
            'employee_name' => $summary->employee_name,
            'work_date' => $summary->work_date,
            'total_service_amount' => $summary->total_service_amount,
            'service_commission' => $summary->service_commission,
            'total_product_qty' => $summary->total_product_qty,
            'product_commission' => $summary->product_commission,
            'total_commission' => $summary->total_commission,
            'payment_status' => FreelancePayment::STATUS_UNPAID,
            'paid_at' => null,
            'expense_id' => null,
        ];
    }

    private function makeRowKey(int $employeeId, ?string $workDate): string
    {
        return $employeeId.'|'.$workDate;
    }

    private function buildExpenseNote(FreelancePayment $payment): string
    {
        $employeeName = $payment->employee?->name ?? 'Pegawai';
        $workDateLabel = $payment->work_date?->locale('id')->translatedFormat('d F Y') ?? '-';

        return 'Pembayaran komisi freelance '.$employeeName
            .' untuk transaksi tanggal '.$workDateLabel
            .' sebesar '.format_rupiah($payment->total_commission);
    }
}
