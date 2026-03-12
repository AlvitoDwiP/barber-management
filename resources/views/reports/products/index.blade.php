<x-app-layout>
    <x-slot name="header">
        <x-report-page-header title="Laporan Produk" />
    </x-slot>

    @php
        $rows = collect($rows ?? []);

        $tableRows = $rows->map(function ($row) {
            $stockRemaining = (int) ($row['stock_remaining'] ?? 0);
            $stockLabel = $stockRemaining < 5 ? ' (Stok rendah)' : '';

            return [
                $row['product_name'] ?? '-',
                number_format((int) ($row['total_qty_sold'] ?? 0), 0, ',', '.'),
                format_rupiah($row['total_revenue'] ?? 0),
                number_format($stockRemaining, 0, ',', '.').$stockLabel,
            ];
        })->all();
    @endphp

    <div class="space-y-6">
        <x-report-table
            :headers="['Produk', 'Qty terjual', 'Pendapatan produk', 'Stok tersisa']"
            :rows="$tableRows"
            empty-message="Belum ada penjualan produk."
        />
    </div>
</x-app-layout>
