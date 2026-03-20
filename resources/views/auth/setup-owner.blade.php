<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="mb-6">
        <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-amber-800">
            Setup Awal
        </span>
        <h1 class="mt-4 text-2xl font-semibold text-gray-900">Buat akun owner pertama</h1>
        <p class="mt-2 text-sm leading-6 text-gray-600">
            Setup ini hanya tersedia saat aplikasi belum memiliki pengguna. Setelah akun owner pertama berhasil dibuat,
            halaman ini akan otomatis ditutup.
        </p>
    </div>

    <form method="POST" action="{{ route('owner.setup.store') }}">
        @csrf

        <div>
            <x-input-label for="name" value="Nama Owner" />
            <x-text-input id="name" class="mt-1 block w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="email" value="Email Login" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" value="Password" />
            <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" value="Konfirmasi Password" />
            <x-text-input id="password_confirmation" class="mt-1 block w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="mt-6 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
            Gunakan email owner aktif dan password yang kuat. Setelah setup selesai, login berikutnya dilakukan melalui halaman login biasa.
        </div>

        <div class="mt-6 flex items-center justify-end">
            <x-primary-button>
                Simpan dan Masuk
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
