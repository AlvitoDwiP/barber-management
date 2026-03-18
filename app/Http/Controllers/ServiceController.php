<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Models\Service;
use App\Services\CommissionSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function __construct(
        private readonly CommissionSettingsService $commissionSettingsService,
    ) {
    }

    public function index(): View
    {
        $services = Service::query()->latest()->get();

        return view('services.index', compact('services'));
    }

    public function create(): View
    {
        return view('services.create', [
            'defaultCommissionValue' => $this->commissionSettingsService->getDefaultServiceCommission()['commission_value'],
        ]);
    }

    public function store(StoreServiceRequest $request): RedirectResponse
    {
        Service::query()->create($request->validated());

        return redirect()
            ->route('services.index')
            ->with('success', 'Data layanan berhasil ditambahkan.');
    }

    public function show(Service $service): RedirectResponse
    {
        return redirect()->route('services.edit', $service);
    }

    public function edit(Service $service): View
    {
        return view('services.edit', [
            'service' => $service,
            'defaultCommissionValue' => $this->commissionSettingsService->getDefaultServiceCommission()['commission_value'],
        ]);
    }

    public function update(UpdateServiceRequest $request, Service $service): RedirectResponse
    {
        $service->update($request->validated());

        return redirect()
            ->route('services.index')
            ->with('success', 'Data layanan berhasil diperbarui.');
    }

    public function destroy(Service $service): RedirectResponse
    {
        $service->delete();

        return redirect()
            ->route('services.index')
            ->with('success', 'Data layanan berhasil dihapus.');
    }
}
