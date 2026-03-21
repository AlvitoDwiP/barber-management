<x-app-layout>
    <x-slot name="header">
        <x-report-page-header
            title="Laporan Metode Pembayaran"
            subtitle="Laporan ini fokus pada arus pembayaran transaksi per metode. Gunakan halaman ini untuk membaca kas masuk, bukan laba operasional."
        />
    </x-slot>

    @php
        $rows = collect($rows ?? []);
        $year = (int) ($year ?? now()->year);
        $yearOptions = collect(range(now()->year, now()->year - 4));
        $sumMoney = fn (string $field): string => $rows->reduce(
            fn (string $carry, $row): string => \App\Support\Money::parse($carry)
                ->add(\App\Support\Money::parse($row[$field] ?? 0))
                ->toDecimal(),
            '0.00',
        );

        $tableRows = $rows->map(function ($row) {
            return [
                \Illuminate\Support\Carbon::createFromDate(null, (int) ($row['month_number'] ?? 1), 1)->locale('id')->translatedFormat('F'),
                format_rupiah($row['total_cash'] ?? 0),
                format_rupiah($row['total_qr'] ?? 0),
                format_rupiah($row['cash_in'] ?? 0),
                number_format((int) ($row['total_transactions'] ?? 0), 0, ',', '.'),
            ];
        })->all();

        $footer = [
            'Total',
            format_rupiah($sumMoney('total_cash')),
            format_rupiah($sumMoney('total_qr')),
            format_rupiah($sumMoney('cash_in')),
            number_format((int) $rows->sum('total_transactions'), 0, ',', '.'),
        ];
        $hasPaymentData = (int) $rows->sum('total_transactions') > 0;
    @endphp

    <div class="space-y-6">
        <section class="admin-card p-4 sm:p-5">
            <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Kas masuk</p>
                    <p class="mt-1 text-sm leading-6 text-slate-600">Kas masuk di halaman ini adalah total pembayaran transaksi yang diterima lewat cash dan QR.</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Batas laporan</p>
                    <p class="mt-1 text-sm leading-6 text-slate-600">Halaman ini tidak menghitung pengeluaran atau laba operasional. Gunakan laporan harian atau bulanan untuk membaca profit.</p>
                </div>
            </div>
        </section>

        <x-report-filter
            :action="route('reports.payment')"
            :showDateRange="false"
            :showYear="false"
            :filterKeys="['year']"
            helperText="Pilih tahun untuk melihat arus pembayaran transaksi per bulan."
        >
            <div>
                <label for="year" class="text-sm font-medium text-slate-700">Tahun</label>
                <select
                    id="year"
                    name="year"
                    class="form-brand-control"
                >
                    @foreach ($yearOptions as $optionYear)
                        <option value="{{ $optionYear }}" @selected((int) $year === (int) $optionYear)>{{ $optionYear }}</option>
                    @endforeach
                </select>
                @error('year')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </x-report-filter>

        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="transaction-metric-card">
                <p class="transaction-metric-label">Tahun laporan</p>
                <p class="transaction-metric-value">{{ $year }}</p>
            </article>
            <article class="transaction-metric-card">
                <p class="transaction-metric-label">Total cash</p>
                <p class="transaction-metric-value">{{ format_rupiah($sumMoney('total_cash')) }}</p>
            </article>
            <article class="transaction-metric-card">
                <p class="transaction-metric-label">Total QR</p>
                <p class="transaction-metric-value">{{ format_rupiah($sumMoney('total_qr')) }}</p>
            </article>
            <article class="transaction-metric-card">
                <p class="transaction-metric-label">Kas masuk</p>
                <p class="transaction-metric-value">{{ format_rupiah($sumMoney('cash_in')) }}</p>
            </article>
        </section>

        @if ($hasPaymentData)
            <x-report-table
                :headers="['Bulan', 'Cash', 'QR', 'Kas Masuk', 'Jumlah transaksi']"
                :rows="$tableRows"
                :footer="$footer"
            />
        @else
            <section class="admin-card">
                <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
                    <h3 class="text-base font-semibold text-slate-900">Belum ada arus pembayaran pada tahun ini</h3>
                    <p class="mt-2 text-sm text-slate-500">
                        Belum ada transaksi yang tercatat untuk tahun {{ $year }}, jadi kas masuk belum bisa diringkas.
                    </p>
                    <p class="mt-1 text-sm text-slate-500">
                        Pilih tahun lain atau input transaksi dulu agar laporan pembayaran mulai terisi.
                    </p>
                </div>
            </section>
        @endif
    </div>
</x-app-layout>
