<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500&family=Playfair+Display:wght@400;500;600&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            * {
                box-sizing: border-box;
            }

            html,
            body {
                min-height: 100%;
                margin: 0;
            }

            body {
                position: relative;
                overflow-x: hidden;
                background: #F5F0EB;
                color: #2C1A0E;
                font-family: 'DM Sans', sans-serif;
            }

            .login-shell {
                position: relative;
                display: flex;
                min-height: 100vh;
                align-items: center;
                justify-content: center;
                padding: 32px 16px;
            }

            .login-shell::before,
            .login-shell::after {
                position: absolute;
                z-index: 0;
                border-radius: 999px;
                background: rgba(139, 90, 43, 0.08);
                content: '';
                filter: blur(16px);
            }

            .login-shell::before {
                top: -120px;
                left: -90px;
                width: 280px;
                height: 280px;
            }

            .login-shell::after {
                right: -120px;
                bottom: -130px;
                width: 320px;
                height: 320px;
            }

            .login-card {
                position: relative;
                z-index: 1;
                width: 100%;
                max-width: 420px;
                border: 0.5px solid rgba(139, 90, 43, 0.15);
                border-radius: 16px;
                background: #FFFFFF;
                padding: 40px 44px;
            }

            .brand-row {
                display: flex;
                align-items: center;
                gap: 14px;
                margin-bottom: 22px;
            }

            .brand-mark {
                display: inline-flex;
                height: 42px;
                width: 42px;
                align-items: center;
                justify-content: center;
                border-radius: 10px;
                background: #6B3A1F;
                flex-shrink: 0;
            }

            .brand-mark svg {
                width: 22px;
                height: 22px;
            }

            .brand-name {
                margin: 0;
                color: #2C1A0E;
                font-family: 'Playfair Display', serif;
                font-size: 17px;
                font-weight: 600;
                line-height: 1.2;
            }

            .brand-subtitle {
                margin: 3px 0 0;
                color: #9B7A60;
                font-size: 11px;
                font-weight: 500;
                letter-spacing: 0.06em;
                line-height: 1.4;
                text-transform: uppercase;
            }

            .divider {
                margin: 0 0 28px;
                border: 0;
                border-top: 0.5px solid rgba(139, 90, 43, 0.15);
            }

            .login-title {
                margin: 0;
                color: #2C1A0E;
                font-family: 'Playfair Display', serif;
                font-size: 22px;
                font-weight: 500;
                line-height: 1.2;
            }

            .login-description {
                margin: 12px 0 28px;
                color: #9B7A60;
                font-size: 13px;
                font-weight: 300;
                line-height: 1.6;
            }

            .status-banner {
                margin-bottom: 18px;
                border-radius: 8px;
                background: rgba(107, 58, 31, 0.08);
                padding: 10px 12px;
                color: #6B3A1F;
                font-size: 12px;
                line-height: 1.5;
            }

            .field-group + .field-group {
                margin-top: 18px;
            }

            .field-label {
                display: inline-block;
                margin-bottom: 7px;
                color: #6B3A1F;
                font-size: 12px;
                font-weight: 500;
                letter-spacing: 0.04em;
                line-height: 1.3;
                text-transform: uppercase;
            }

            .field-input {
                width: 100%;
                border: 1px solid rgba(139, 90, 43, 0.2);
                border-radius: 8px;
                background: #FAF6F2;
                padding: 11px 14px;
                color: #2C1A0E;
                font-size: 14px;
                font-family: 'DM Sans', sans-serif;
                line-height: 1.5;
                transition: border-color 0.2s ease, background-color 0.2s ease;
            }

            .field-input::placeholder {
                color: #C4AA96;
            }

            .field-input:focus {
                outline: none;
                border-color: #6B3A1F;
                background: #FFFFFF;
            }

            .field-input.input-error-state {
                border-color: #E24B4A;
            }

            .field-error {
                margin-top: 7px;
                color: #E24B4A;
                font-size: 12px;
                line-height: 1.5;
            }

            .form-options {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 16px;
                margin: 20px 0 24px;
            }

            .remember-label {
                display: inline-flex;
                align-items: center;
                gap: 9px;
                color: #9B7A60;
                font-size: 13px;
                line-height: 1.4;
                cursor: pointer;
            }

            .login-checkbox {
                appearance: none;
                -webkit-appearance: none;
                display: inline-block;
                width: 15px;
                height: 15px;
                margin: 0;
                border: 1px solid rgba(139, 90, 43, 0.35);
                border-radius: 4px;
                background: #FAF6F2;
                background-repeat: no-repeat;
                background-position: center;
                background-size: 9px;
                cursor: pointer;
                transition: background-color 0.2s ease, border-color 0.2s ease;
            }

            .login-checkbox:checked {
                border-color: #6B3A1F;
                background-color: #6B3A1F;
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' fill='none'%3E%3Cpath d='M2.5 6.2L4.9 8.6L9.5 3.8' stroke='white' stroke-width='1.7' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
            }

            .login-checkbox:focus {
                outline: none;
                border-color: #6B3A1F;
            }

            .forgot-link {
                display: inline-block;
                border-bottom: 1px solid rgba(107, 58, 31, 0.25);
                color: #6B3A1F;
                font-size: 13px;
                line-height: 1.4;
                text-decoration: none;
                transition: color 0.2s ease, border-color 0.2s ease;
            }

            .forgot-link:hover {
                color: #8B4A27;
                border-bottom-color: rgba(139, 74, 39, 0.35);
            }

            .submit-button {
                width: 100%;
                border: none;
                border-radius: 8px;
                background: #6B3A1F;
                padding: 13px 16px;
                color: #FFFFFF;
                font-size: 14px;
                font-weight: 500;
                font-family: 'DM Sans', sans-serif;
                letter-spacing: 0.06em;
                line-height: 1.3;
                text-transform: uppercase;
                cursor: pointer;
                transition: background-color 0.2s ease;
            }

            .submit-button:hover {
                background: #8B4A27;
            }

            .submit-button:focus {
                outline: none;
                background: #8B4A27;
            }

            .card-footer {
                margin-top: 24px;
                color: #C4AA96;
                font-size: 11px;
                line-height: 1.5;
                text-align: center;
            }

            @media (max-width: 480px) {
                .login-shell {
                    padding: 16px;
                }

                .login-card {
                    padding: 32px 24px;
                }

                .form-options {
                    flex-direction: column;
                    align-items: flex-start;
                }
            }
        </style>
    </head>
    <body>
        <main class="login-shell">
            <section class="login-card" aria-labelledby="login-title">
                <div class="brand-row">
                    <div class="brand-mark" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M13.1 5.2C14.8 5.2 16.2 6.6 16.2 8.3C16.2 10 14.8 11.4 13.1 11.4C11.4 11.4 10 10 10 8.3C10 6.6 11.4 5.2 13.1 5.2Z" fill="white"/>
                            <path d="M6 18.4C6.5 15.7 8.9 13.8 11.7 13.8H14.3C15.3 13.8 16.2 14 17 14.4" stroke="white" stroke-width="1.6" stroke-linecap="round"/>
                            <path d="M16.5 15L20 18.5" stroke="white" stroke-width="1.6" stroke-linecap="round"/>
                            <path d="M20 15L16.5 18.5" stroke="white" stroke-width="1.6" stroke-linecap="round"/>
                        </svg>
                    </div>

                    <div>
                        <p class="brand-name">Sejati Hair Studio</p>
                        <p class="brand-subtitle">Management System</p>
                    </div>
                </div>

                <hr class="divider">

                @if (session('status'))
                    <div class="status-banner">
                        {{ session('status') }}
                    </div>
                @endif

                <h1 id="login-title" class="login-title">Login Owner</h1>
                <p class="login-description">
                    Masuk menggunakan akun owner untuk mengakses transaksi, payroll, laporan, dan pengaturan aplikasi.
                </p>

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="field-group">
                        <label class="field-label" for="email">Email</label>
                        <input
                            id="email"
                            class="field-input @error('email') input-error-state @enderror"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="Masukkan email"
                            required
                            autofocus
                            autocomplete="username"
                        >
                        @error('email')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="field-group">
                        <label class="field-label" for="password">Kata Sandi</label>
                        <input
                            id="password"
                            class="field-input @error('password') input-error-state @enderror"
                            type="password"
                            name="password"
                            placeholder="Masukkan kata sandi"
                            required
                            autocomplete="current-password"
                        >
                        @error('password')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-options">
                        <label class="remember-label" for="remember">
                            <input
                                id="remember"
                                class="login-checkbox"
                                type="checkbox"
                                name="remember"
                                {{ old('remember') ? 'checked' : '' }}
                            >
                            <span>Tetap masuk</span>
                        </label>

                        @if (Route::has('password.request'))
                            <a class="forgot-link" href="{{ route('password.request') }}">
                                Lupa kata sandi?
                            </a>
                        @endif
                    </div>

                    <button class="submit-button" type="submit">Masuk</button>
                </form>

                <div class="card-footer">© Ver 1. 2026 · Sejati Hair Studio</div>
            </section>
        </main>
    </body>
</html>
