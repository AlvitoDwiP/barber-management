<div class="flex flex-wrap items-center gap-2">
    <a
        href="{{ route('payroll.index') }}"
        @class([
            'btn-brand-soft',
            '!bg-[#934C2D] !text-white hover:!bg-[#7A3E23]' => request()->routeIs('payroll.index') || request()->routeIs('payroll.show'),
        ])
    >
        Payroll
    </a>
    <a
        href="{{ route('payroll.freelance.index') }}"
        @class([
            'btn-brand-soft',
            '!bg-[#934C2D] !text-white hover:!bg-[#7A3E23]' => request()->routeIs('payroll.freelance.*'),
        ])
    >
        Freelance
    </a>
</div>
