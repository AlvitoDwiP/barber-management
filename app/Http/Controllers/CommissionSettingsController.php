<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCommissionSettingsRequest;
use App\Services\CommissionSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CommissionSettingsController extends Controller
{
    public function __construct(
        private readonly CommissionSettingsService $commissionSettingsService,
    ) {
    }

    public function edit(): View
    {
        return view('settings.commission.edit', [
            'settings' => $this->commissionSettingsService->get(),
        ]);
    }

    public function update(UpdateCommissionSettingsRequest $request): RedirectResponse
    {
        $this->commissionSettingsService->update($request->validated());

        return redirect()
            ->route('settings.commission.edit')
            ->with('success', 'Pengaturan komisi default berhasil diperbarui.');
    }
}
