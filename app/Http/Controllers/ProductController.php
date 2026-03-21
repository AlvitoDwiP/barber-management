<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Services\CommissionSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private readonly CommissionSettingsService $commissionSettingsService,
    ) {
    }

    public function index(): View
    {
        $products = Product::query()->latest()->get();

        return view('products.index', compact('products'));
    }

    public function create(): View
    {
        return view('products.create', [
            'defaultCommissionValue' => $this->commissionSettingsService->getDefaultProductCommission()['commission_value'],
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        Product::query()->create($request->validated());

        return redirect()
            ->route('products.index')
            ->with('success', 'Produk berhasil ditambahkan.');
    }

    public function show(Product $product): RedirectResponse
    {
        return redirect()->route('products.edit', $product);
    }

    public function edit(Product $product): View
    {
        return view('products.edit', [
            'product' => $product,
            'defaultCommissionValue' => $this->commissionSettingsService->getDefaultProductCommission()['commission_value'],
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated());

        return redirect()
            ->route('products.index')
            ->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()
            ->route('products.index')
            ->with('success', 'Produk berhasil dihapus.');
    }
}
