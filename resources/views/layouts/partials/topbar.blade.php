@php
    $profileUrl = \Illuminate\Support\Facades\Route::has('profile.edit') ? route('profile.edit') : null;
    $logoutUrl = \Illuminate\Support\Facades\Route::has('logout') ? route('logout') : null;
@endphp

<header class="sticky top-0 z-30 border-b border-[#E6D6CD] bg-white/95 backdrop-blur">
    <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
        <div class="flex min-w-0 items-center gap-3">
            <button
                type="button"
                class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-[#E6D6CD] text-slate-600 transition hover:bg-[#FAF3EF] hover:text-[#7D4026] lg:hidden"
                @click="sidebarOpen = true"
                aria-label="Buka sidebar"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            <h1 class="truncate text-sm font-semibold text-slate-900">Hair Studio Admin Panel</h1>
        </div>

        <div class="relative" @click.outside="userMenuOpen = false">
            <button
                type="button"
                class="inline-flex items-center gap-3 rounded-lg border border-[#E6D6CD] bg-white px-3 py-2 text-sm text-slate-700 transition hover:bg-[#FAF3EF]"
                @click="userMenuOpen = !userMenuOpen"
            >
                <span class="hidden text-right sm:block">
                    <span class="block text-xs text-slate-500">Admin</span>
                    <span class="block max-w-[140px] truncate font-medium text-slate-900">{{ Auth::user()->name }}</span>
                </span>
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-[#934C2D] text-xs font-semibold text-white">
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                </span>
            </button>

            <div
                x-cloak
                x-show="userMenuOpen"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="opacity-0 translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 translate-y-1"
                class="absolute right-0 mt-2 w-56 overflow-hidden rounded-xl border border-[#E6D6CD] bg-white shadow-xl"
            >
                @if ($profileUrl)
                    <a href="{{ $profileUrl }}" class="block px-4 py-2 text-sm text-slate-700 transition hover:bg-[#FAF3EF] hover:text-[#7D4026]">Profil</a>
                @endif

                @if ($logoutUrl)
                    <form method="POST" action="{{ $logoutUrl }}">
                        @csrf
                        <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-red-600 transition hover:bg-red-50">Keluar</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</header>
