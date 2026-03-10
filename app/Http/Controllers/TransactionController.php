<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(): View
    {
        return view('transactions.index');
    }

    public function create(): View
    {
        return view('transactions.create');
    }

    public function store(Request $request): RedirectResponse
    {
        abort(501);
    }

    public function show(string $id): View
    {
        return view('transactions.show', compact('id'));
    }

    public function edit(string $id): View
    {
        return view('transactions.edit', compact('id'));
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        abort(501);
    }

    public function destroy(string $id): RedirectResponse
    {
        abort(501);
    }
}
