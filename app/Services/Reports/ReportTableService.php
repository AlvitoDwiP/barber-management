<?php

namespace App\Services\Reports;

use App\Services\Reports\Concerns\InteractsWithExactReportMoney;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ReportTableService
{
    use InteractsWithExactReportMoney;

    public function buildDailyTable(Collection $rows): array
    {
        $headers = [
            'Tanggal',
            'Jumlah transaksi',
            'Pendapatan layanan',
            'Pendapatan produk',
            'Total pendapatan',
            'Cash',
            'QR',
            'Pengeluaran',
            'Pendapatan bersih',
        ];

        return [
            'headers' => $headers,
            'displayRows' => $rows->map(fn (array $row) => [
                Carbon::parse($row['report_date'])->locale('id')->translatedFormat('d M Y'),
                number_format((int) ($row['total_transactions'] ?? 0), 0, ',', '.'),
                format_rupiah($row['service_revenue'] ?? 0),
                format_rupiah($row['product_revenue'] ?? 0),
                format_rupiah($row['total_revenue'] ?? 0),
                format_rupiah($row['cash'] ?? 0),
                format_rupiah($row['qr'] ?? 0),
                format_rupiah($row['expenses'] ?? 0),
                format_rupiah($row['net_income'] ?? 0),
            ])->all(),
            'displayFooter' => [
                'Total',
                number_format((int) $rows->sum('total_transactions'), 0, ',', '.'),
                format_rupiah($this->sumMoney($rows, 'service_revenue')),
                format_rupiah($this->sumMoney($rows, 'product_revenue')),
                format_rupiah($this->sumMoney($rows, 'total_revenue')),
                format_rupiah($this->sumMoney($rows, 'cash')),
                format_rupiah($this->sumMoney($rows, 'qr')),
                format_rupiah($this->sumMoney($rows, 'expenses')),
                format_rupiah($this->sumMoney($rows, 'net_income')),
            ],
            'csvRows' => $rows->map(fn (array $row) => [
                $row['report_date'] ?? '',
                (int) ($row['total_transactions'] ?? 0),
                $this->moneyToDecimal($row['service_revenue'] ?? 0),
                $this->moneyToDecimal($row['product_revenue'] ?? 0),
                $this->moneyToDecimal($row['total_revenue'] ?? 0),
                $this->moneyToDecimal($row['cash'] ?? 0),
                $this->moneyToDecimal($row['qr'] ?? 0),
                $this->moneyToDecimal($row['expenses'] ?? 0),
                $this->moneyToDecimal($row['net_income'] ?? 0),
            ])->all(),
            'csvFooter' => [
                'Total',
                (int) $rows->sum('total_transactions'),
                $this->sumMoney($rows, 'service_revenue'),
                $this->sumMoney($rows, 'product_revenue'),
                $this->sumMoney($rows, 'total_revenue'),
                $this->sumMoney($rows, 'cash'),
                $this->sumMoney($rows, 'qr'),
                $this->sumMoney($rows, 'expenses'),
                $this->sumMoney($rows, 'net_income'),
            ],
        ];
    }

    public function buildMonthlyTable(Collection $rows, int $year): array
    {
        $headers = [
            'Bulan',
            'Pendapatan layanan',
            'Pendapatan produk',
            'Total pendapatan',
            'Total komisi pegawai',
            'Pengeluaran',
            'Laba bersih',
        ];

        return [
            'headers' => $headers,
            'displayRows' => $rows->map(function (array $row) use ($year): array {
                return [
                    Carbon::createFromDate($year, $row['month_number'], 1)->locale('id')->translatedFormat('F Y'),
                    format_rupiah($row['service_revenue'] ?? 0),
                    format_rupiah($row['product_revenue'] ?? 0),
                    format_rupiah($row['total_revenue'] ?? 0),
                    format_rupiah($row['employee_commissions'] ?? 0),
                    format_rupiah($row['expenses'] ?? 0),
                    format_rupiah($row['net_profit'] ?? 0),
                ];
            })->all(),
            'displayFooter' => [
                'Total',
                format_rupiah($this->sumMoney($rows, 'service_revenue')),
                format_rupiah($this->sumMoney($rows, 'product_revenue')),
                format_rupiah($this->sumMoney($rows, 'total_revenue')),
                format_rupiah($this->sumMoney($rows, 'employee_commissions')),
                format_rupiah($this->sumMoney($rows, 'expenses')),
                format_rupiah($this->sumMoney($rows, 'net_profit')),
            ],
            'csvRows' => $rows->map(function (array $row) use ($year): array {
                return [
                    Carbon::createFromDate($year, $row['month_number'], 1)->locale('id')->translatedFormat('F Y'),
                    $this->moneyToDecimal($row['service_revenue'] ?? 0),
                    $this->moneyToDecimal($row['product_revenue'] ?? 0),
                    $this->moneyToDecimal($row['total_revenue'] ?? 0),
                    $this->moneyToDecimal($row['employee_commissions'] ?? 0),
                    $this->moneyToDecimal($row['expenses'] ?? 0),
                    $this->moneyToDecimal($row['net_profit'] ?? 0),
                ];
            })->all(),
            'csvFooter' => [
                'Total',
                $this->sumMoney($rows, 'service_revenue'),
                $this->sumMoney($rows, 'product_revenue'),
                $this->sumMoney($rows, 'total_revenue'),
                $this->sumMoney($rows, 'employee_commissions'),
                $this->sumMoney($rows, 'expenses'),
                $this->sumMoney($rows, 'net_profit'),
            ],
        ];
    }

    public function buildEmployeePerformanceTable(Collection $rows): array
    {
        $headers = [
            'Nama pegawai',
            'Jumlah transaksi',
            'Jumlah layanan dikerjakan',
            'Omzet layanan',
            'Jumlah produk terjual',
            'Omzet produk',
            'Total komisi',
        ];

        return [
            'headers' => $headers,
            'displayRows' => $rows->map(fn (array $row): array => [
                $row['employee_name'] ?? '-',
                number_format((int) ($row['total_transactions'] ?? 0), 0, ',', '.'),
                number_format((int) ($row['total_services'] ?? 0), 0, ',', '.'),
                format_rupiah($row['service_revenue'] ?? 0),
                number_format((int) ($row['total_products'] ?? 0), 0, ',', '.'),
                format_rupiah($row['product_revenue'] ?? 0),
                format_rupiah($row['total_commission'] ?? 0),
            ])->all(),
            'displayFooter' => [
                'Total',
                number_format((int) $rows->sum('total_transactions'), 0, ',', '.'),
                number_format((int) $rows->sum('total_services'), 0, ',', '.'),
                format_rupiah($this->sumMoney($rows, 'service_revenue')),
                number_format((int) $rows->sum('total_products'), 0, ',', '.'),
                format_rupiah($this->sumMoney($rows, 'product_revenue')),
                format_rupiah($this->sumMoney($rows, 'total_commission')),
            ],
            'csvRows' => $rows->map(fn (array $row): array => [
                $row['employee_name'] ?? '',
                (int) ($row['total_transactions'] ?? 0),
                (int) ($row['total_services'] ?? 0),
                $this->moneyToDecimal($row['service_revenue'] ?? 0),
                (int) ($row['total_products'] ?? 0),
                $this->moneyToDecimal($row['product_revenue'] ?? 0),
                $this->moneyToDecimal($row['total_commission'] ?? 0),
            ])->all(),
            'csvFooter' => [
                'Total',
                (int) $rows->sum('total_transactions'),
                (int) $rows->sum('total_services'),
                $this->sumMoney($rows, 'service_revenue'),
                (int) $rows->sum('total_products'),
                $this->sumMoney($rows, 'product_revenue'),
                $this->sumMoney($rows, 'total_commission'),
            ],
        ];
    }

    public function buildProductSalesTable(Collection $rows): array
    {
        $headers = [
            'Nama produk',
            'Qty terjual',
            'Harga jual rata-rata',
            'Total omzet',
        ];

        $totalQty = (int) $rows->sum('total_qty_sold');
        $totalRevenueMinorUnits = $this->sumMoneyMinorUnits($rows, 'total_revenue');
        $averageSellingPriceMinorUnits = $totalQty > 0
            ? $this->divideMinorUnits($totalRevenueMinorUnits, $totalQty, Money::ROUND_HALF_UP)
            : 0;
        $totalRevenue = $this->moneyFromMinorUnits($totalRevenueMinorUnits);
        $averageSellingPrice = $this->moneyFromMinorUnits($averageSellingPriceMinorUnits);

        return [
            'headers' => $headers,
            'displayRows' => $rows->map(fn (array $row): array => [
                $row['product_name'] ?? '-',
                number_format((int) ($row['total_qty_sold'] ?? 0), 0, ',', '.'),
                format_rupiah($row['average_selling_price'] ?? 0),
                format_rupiah($row['total_revenue'] ?? 0),
            ])->all(),
            'displayFooter' => [
                'Total',
                number_format($totalQty, 0, ',', '.'),
                format_rupiah($averageSellingPrice),
                format_rupiah($totalRevenue),
            ],
            'csvRows' => $rows->map(fn (array $row): array => [
                $row['product_name'] ?? '',
                (int) ($row['total_qty_sold'] ?? 0),
                $this->moneyToDecimal($row['average_selling_price'] ?? 0),
                $this->moneyToDecimal($row['total_revenue'] ?? 0),
            ])->all(),
            'csvFooter' => [
                'Total',
                $totalQty,
                $averageSellingPrice,
                $totalRevenue,
            ],
        ];
    }
}
