<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        Sebelum lanjut menggunakan aplikasi, verifikasi dulu email owner melalui tautan yang baru kami kirim. Jika emailnya belum masuk, Anda bisa minta kirim ulang dari halaman ini.
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-600">
            Link verifikasi baru sudah dikirim ke email yang Anda daftarkan.
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button>
                    Kirim Ulang Email Verifikasi
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Keluar
            </button>
        </form>
    </div>
</x-guest-layout>
