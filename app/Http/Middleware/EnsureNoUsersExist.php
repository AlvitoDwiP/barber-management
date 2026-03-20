<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNoUsersExist
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! User::query()->exists()) {
            return $next($request);
        }

        if ($request->user() !== null) {
            return redirect()
                ->route('dashboard')
                ->with('status', 'Setup owner pertama sudah ditutup karena akun owner sudah tersedia.');
        }

        return redirect()
            ->route('login')
            ->with('status', 'Setup owner pertama sudah ditutup karena akun owner sudah tersedia.');
    }
}
