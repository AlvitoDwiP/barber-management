<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        return view('reports.index');
    }

    public function daily(): View
    {
        return view('reports.daily.index');
    }

    public function monthly(): View
    {
        return view('reports.monthly.index');
    }

    public function payment(): View
    {
        return view('reports.payment.index');
    }

    public function products(): View
    {
        return view('reports.products.index');
    }

    public function employees(): View
    {
        return view('reports.employees.index');
    }
}

