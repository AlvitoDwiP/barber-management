<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreFirstOwnerRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FirstOwnerSetupController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (User::query()->exists()) {
            return redirect()
                ->route('login')
                ->with('status', 'Setup owner pertama sudah ditutup karena akun owner sudah tersedia.');
        }

        return view('auth.setup-owner');
    }

    public function store(StoreFirstOwnerRequest $request): RedirectResponse
    {
        if (User::query()->exists()) {
            return redirect()
                ->route('login')
                ->with('status', 'Setup owner pertama sudah ditutup karena akun owner sudah tersedia.');
        }

        $user = DB::transaction(function () use ($request): User {
            $user = User::query()->create($request->validated());

            event(new Registered($user));

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->intended(route('dashboard', absolute: false))
            ->with('status', 'Akun owner pertama berhasil dibuat.');
    }
}
