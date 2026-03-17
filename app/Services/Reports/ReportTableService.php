<?php

namespace App\Services\Reports;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ReportTableService
{
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
                format_rupiah($rows->sum('service_revenue')),
                format_rupiah($rows->sum('product_revenue')),
                format_rupiah($rows->sum('total_revenue')),
                format_rupiah($rows->sum('cash')),
                format_rupiah($rows->sum('qr')),
                format_rupiah($rows->sum('expenses')),
                format_rupiah($rows->sum('net_income')),
            ],
            'csvRows' => $rows->map(fn (array $row) => [
                $row['report_date'] ?? '',
                (int) ($row['total_transactions'] ?? 0),
                (float) ($row['service_revenue'] ?? 0),
                (float) ($row['product_revenue'] ?? 0),
                (float) ($row['total_revenue'] ?? 0),
                (float) ($row['cash'] ?? 0),
                (float) ($row['qr'] ?? 0),
                (float) ($row['expenses'] ?? 0),
                (float) ($row['net_income'] ?? 0),
            ])->all(),
            'csvFooter' => [
                'Total',
                (int) $rows->sum('total_transactions'),
                (float) $rows->sum('service_revenue'),
                (float) $rows->sum('product_revenue'),
                (float) $rows->sum('total_revenue'),
                (float) $rows->sum('cash'),
                (float) $rows->sum('qr'),
                (float) $rows->sum('expenses'),
                (float) $rows->sum('net_income'),
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
                format_rupiah($rows->sum('service_revenue')),
                format_rupiah($rows->sum('product_revenue')),
                format_rupiah($rows->sum('total_revenue')),
                format_rupiah($rows->sum('employee_commissions')),
                format_rupiah($rows->sum('expenses')),
                format_rupiah($rows->sum('net_profit')),
            ],
            'csvRows' => $rows->map(function (array $row) use ($year): array {
                return [
                    Carbon::createFromDate($year, $row['month_number'], 1)->locale('id')->translatedFormat('F Y'),
                    (float) ($row['service_revenue'] ?? 0),
                    (float) ($row['product_revenue'] ?? 0),
                    (float) ($row['total_revenue'] ?? 0),
                    (float) ($row['employee_commissions'] ?? 0),
                    (float) ($row['expenses'] ?? 0),
                    (float) ($row['net_profit'] ?? 0),
                ];
            })->all(),
            'csvFooter' => [
                'Total',
                (float) $rows->sum('service_revenue'),
                (float) $rows->sum('product_revenue'),
                (float) $rows->sum('total_revenue'),
                (float) $rows->sum('employee_commissions'),
                (float) $rows->sum('expenses'),
                (float) $rows->sum('net_profit'),
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
                format_rupiah($rows->sum('service_revenue')),
                number_format((int) $rows->sum('total_products'), 0, ',', '.'),
                format_rupiah($rows->sum('product_revenue')),
                format_rupiah($rows->sum('total_commission')),
            ],
            'csvRows' => $rows->map(fn (array $row): array => [
                $row['employee_name'] ?? '',
                (int) ($row['total_transactions'] ?? 0),
                (int) ($row['total_services'] ?? 0),
                (float) ($row['service_revenue'] ?? 0),
                (int) ($row['total_products'] ?? 0),
                (float) ($row['product_revenue'] ?? 0),
                (float) ($row['total_commission'] ?? 0),
            ])->all(),
            'csvFooter' => [
                'Total',
                (int) $rows->sum('total_transactions'),
                (int) $rows->sum('total_services'),
                (float) $rows->sum('service_revenue'),
                (int) $rows->sum('total_products'),
                (float) $rows->sum('product_revenue'),
                (float) $rows->sum('total_commission'),
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
        $totalRevenue = (float) $rows->sum('total_revenue');
        $averageSellingPrice = $totalQty > 0 ? $totalRevenue / $totalQty : 0.0;

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
                (float) ($row['average_selling_price'] ?? 0),
                (float) ($row['total_revenue'] ?? 0),
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
