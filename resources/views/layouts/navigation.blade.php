@php
    $mobile = $mobile ?? false;

    $resolveRoute = static fn (string $name): ?string => \Illuminate\Support\Facades\Route::has($name) ? route($name) : null;

    $menuItems = [
        [
            'label' => 'Dashboard',
            'route' => 'dashboard',
            'href' => $resolveRoute('dashboard'),
            'active' => request()->routeIs('dashboard'),
            'icon' => 'dashboard',
        ],
        [
            'label' => 'Transaksi',
            'route' => 'transactions.index',
            'href' => $resolveRoute('transactions.index'),
            'active' => request()->routeIs('transactions.*'),
            'icon' => 'transactions',
        ],
        [
            'label' => 'Pegawai',
            'route' => 'employees.index',
            'href' => $resolveRoute('employees.index'),
            'active' => request()->routeIs('employees.*'),
            'icon' => 'employees',
        ],
        [
            'label' => 'Layanan',
            'route' => 'services.index',
            'href' => $resolveRoute('services.index'),
            'active' => request()->routeIs('services.*'),
            'icon' => 'services',
        ],
        [
            'label' => 'Produk',
            'route' => 'products.index',
            'href' => $resolveRoute('products.index'),
            'active' => request()->routeIs('products.*'),
            'icon' => 'products',
        ],
        [
            'label' => 'Pengeluaran',
            'route' => 'expenses.index',
            'href' => $resolveRoute('expenses.index'),
            'active' => request()->routeIs('expenses.*'),
            'icon' => 'expenses',
        ],
        [
            'label' => 'Penggajian',
            'route' => 'payroll.index',
            'href' => $resolveRoute('payroll.index'),
            'active' => request()->routeIs('payroll.*'),
            'icon' => 'payrolls',
        ],
        [
            'label' => 'Laporan',
            'route' => 'reports.index',
            'href' => $resolveRoute('reports.index'),
            'active' => request()->routeIs('reports.*'),
            'icon' => 'reports',
        ],
    ];
@endphp

<nav class="space-y-1">
    <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-[0.12em] text-[#E9CDBF]">Navigasi</p>

    @foreach ($menuItems as $item)
        @php
            $isEnabled = filled($item['href']);
        @endphp

        @if ($isEnabled)
            <a
                href="{{ $item['href'] }}"
                @class([
                    'admin-nav-link',
                    'admin-nav-link-active' => $item['active'],
                ])
                @if ($mobile)
                    @click="sidebarOpen = false"
                @endif
            >
                <span class="admin-nav-icon" aria-hidden="true">
                    @if ($item['icon'] === 'dashboard')
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.5l9-9 9 9M5.25 11.25V20.25h13.5V11.25" />
                        </svg>
                    @elseif ($item['icon'] === 'transactions')
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 3.75h12a1.5 1.5 0 011.5 1.5v13.5a1.5 1.5 0 01-1.5 1.5H6a1.5 1.5 0 01-1.5-1.5V5.25a1.5 1.5 0 011.5-1.5z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 8.25h7.5M8.25 12h7.5M8.25 15.75h4.5" />
                        </svg>
                    @elseif ($item['icon'] === 'employees')
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 7.5a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 20.25a7.5 7.5 0 0115 0" />
                        </svg>
                    @elseif ($item['icon'] === 'services')
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 4.5l5.25 5.25-9.75 9.75H4.5V14.25L14.25 4.5z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 5.25l5.25 5.25" />
                        </svg>
                    @elseif ($item['icon'] === 'products')
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 7.5L12 3l8.25 4.5L12 12 3.75 7.5z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 7.5V16.5L12 21l8.25-4.5V7.5" />
                        </svg>
                    @elseif ($item['icon'] === 'expenses')
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 4.5h10.5A1.5 1.5 0 0118.75 6v12A1.5 1.5 0 0117.25 19.5H6.75A1.5 1.5 0 015.25 18V6A1.5 1.5 0 016.75 4.5z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9.75h7.5M8.25 13.5h4.5" />
                        </svg>
                    @elseif ($item['icon'] === 'payrolls')
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 6.75A2.25 2.25 0 016.75 4.5h10.5a2.25 2.25 0 012.25 2.25v10.5a2.25 2.25 0 01-2.25 2.25H6.75a2.25 2.25 0 01-2.25-2.25V6.75z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 12h9M12 7.5v9" />
                        </svg>
                    @elseif ($item['icon'] === 'reports')
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 19.5h13.5" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 16.5V10.5M12 16.5V7.5M16.5 16.5V12" />
                        </svg>
                    @endif
                </span>
                <span>{{ $item['label'] }}</span>
            </a>
        @else
            <div class="admin-nav-link admin-nav-link-disabled" title="Route {{ $item['route'] }} belum tersedia">
                <span class="admin-nav-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </span>
                <span>{{ $item['label'] }}</span>
            </div>
        @endif
    @endforeach
</nav>
