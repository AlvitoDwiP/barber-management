<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index()
    {
        return view('dashboard');
    }

    public function create()
    {
        abort(501);
    }

    public function store(Request $request)
    {
        abort(501);
    }

    public function show(string $id)
    {
        abort(501);
    }

    public function edit(string $id)
    {
        abort(501);
    }

    public function update(Request $request, string $id)
    {
        abort(501);
    }

    public function destroy(string $id)
    {
        abort(501);
    }
}
