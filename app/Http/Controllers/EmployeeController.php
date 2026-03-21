<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(): View
    {
        $employees = Employee::query()
            ->withCount(['transactions', 'payrollResults', 'freelancePayments'])
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return view('employees.index', compact('employees'));
    }

    public function create(): View
    {
        return view('employees.create');
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        Employee::query()->create($request->validated());

        return redirect()
            ->route('employees.index')
            ->with('success', 'Pegawai berhasil ditambahkan.');
    }

    public function show(Employee $employee): RedirectResponse
    {
        return redirect()->route('employees.edit', $employee);
    }

    public function edit(Employee $employee): View
    {
        return view('employees.edit', compact('employee'));
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $employee->update($request->validated());

        return redirect()
            ->route('employees.index')
            ->with('success', 'Pegawai berhasil diperbarui.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        if ($employee->canBeDeletedPhysically()) {
            $employee->delete();

            return redirect()
                ->route('employees.index')
                ->with('success', 'Pegawai berhasil dihapus.');
        }

        if ($employee->isActive()) {
            $employee->update(['is_active' => false]);

            return redirect()
                ->route('employees.index')
                ->with('success', 'Pegawai punya data historis, jadi dinonaktifkan dan tetap disimpan.');
        }

        return redirect()
            ->route('employees.index')
            ->with('success', 'Pegawai ini sudah nonaktif dan tetap disimpan karena punya data historis.');
    }
}
