@php
    $dashboardUrl = \Illuminate\Support\Facades\Route::has('dashboard') ? route('dashboard') : '#';
@endphp

<aside class="admin-sidebar fixed inset-y-0 left-0 z-40 hidden w-72 border-r border-[#8B533B] lg:flex lg:flex-col">
    <div class="flex h-16 items-center border-b border-[#8B533B] px-6">
        <a href="{{ $dashboardUrl }}" class="flex items-center gap-3">
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-[#F3E5DD]/50 bg-[#F3E5DD]/20 text-sm font-semibold text-[#F9EEE8]">HS</span>
            <div>
                <p class="text-sm font-semibold text-[#FFF8F5]">Hair Studio</p>
                <p class="text-xs text-[#E9CDBF]">Management System</p>
            </div>
        </a>
    </div>

    <div class="flex-1 overflow-y-auto px-4 py-5">
        @include('layouts.navigation')
    </div>

    <div class="border-t border-[#8B533B] px-6 py-4">
        <p class="text-xs text-[#E9CDBF]">Login sebagai</p>
        <p class="truncate text-sm font-medium text-[#FFF8F5]">{{ Auth::user()->name }}</p>
    </div>
</aside>

<div
    x-cloak
    x-show="sidebarOpen"
    class="fixed inset-0 z-50 lg:hidden"
    role="dialog"
    aria-modal="true"
>
    <div x-show="sidebarOpen" x-transition.opacity class="absolute inset-0 bg-slate-950/50" @click="sidebarOpen = false"></div>

    <aside
        x-show="sidebarOpen"
        x-transition:enter="transform transition ease-out duration-200"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transform transition ease-in duration-150"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        class="admin-sidebar relative flex h-full w-72 flex-col border-r border-[#8B533B]"
    >
        <div class="flex h-16 items-center justify-between border-b border-[#8B533B] px-5">
            <a href="{{ $dashboardUrl }}" class="flex items-center gap-3">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-[#F3E5DD]/50 bg-[#F3E5DD]/20 text-xs font-semibold text-[#F9EEE8]">HS</span>
                <span class="text-sm font-semibold text-[#FFF8F5]">Hair Studio</span>
            </a>

            <button
                type="button"
                class="inline-flex h-9 w-9 items-center justify-center rounded-md text-[#F2DACE] transition hover:bg-[#A66445]/30 hover:text-white"
                @click="sidebarOpen = false"
                aria-label="Tutup sidebar"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto px-4 py-5">
            @include('layouts.navigation', ['mobile' => true])
        </div>

        <div class="border-t border-[#8B533B] px-6 py-4">
            <p class="text-xs text-[#E9CDBF]">{{ Auth::user()->email }}</p>
        </div>
    </aside>
</div>
