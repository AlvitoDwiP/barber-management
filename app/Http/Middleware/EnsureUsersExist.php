<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUsersExist
{
    public function handle(Request $request, Closure $next): Response
    {
        if (User::query()->exists()) {
            return $next($request);
        }

        return redirect()
            ->route('owner.setup.create')
            ->with('status', 'Buat akun owner pertama terlebih dahulu sebelum menggunakan halaman autentikasi lainnya.');
    }
}
