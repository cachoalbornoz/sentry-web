@extends('layouts.guest')

@section('title', 'Ingreso')

@push('styles')
    <style>
        #login-screen {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: #090b0f;
        }
        #login-card {
            width: 100%;
            max-width: 420px;
            border: 1px solid #181c24;
            background: #0c0f14;
            padding: 40px 48px;
            box-shadow: 0 24px 50px rgba(0, 0, 0, 0.45);
            color: #f1f5f9;
        }
        #login-card label {
            color: #e2e8f0;
        }
        #login-card input {
            height: 48px;
            width: 100%;
            border: 1px solid #3b4352;
            background: #1b1f28;
            color: #fff;
        }
        #password-wrap {
            position: relative;
        }
        #password {
            padding-right: 48px;
        }
        #toggle-password {
            position: absolute;
            top: 0;
            right: 0;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            background: transparent;
            border: 0;
        }
        #toggle-password:hover {
            color: #e2e8f0;
        }
        #login-card input::placeholder {
            color: #64748b;
        }
        #login-card input:focus {
            outline: none;
            border-color: #5a6476;
        }
        #login-submit {
            height: 56px;
            width: 100%;
            background: #2563eb;
            color: #fff;
            font-size: 18px;
            font-weight: 600;
            border: 0;
        }
        #login-submit:hover:not(:disabled) {
            background: #1d4ed8;
        }
        #login-submit-loading {
            display: none;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
    </style>
@endpush

@section('content')
    <div id="login-screen" class="min-h-screen flex items-center justify-center p-6">
        <div id="login-card" class="w-full max-w-[420px] border border-[#181c24] bg-[#0c0f14] px-12 py-10 shadow-2xl shadow-black/40">
            <div class="mb-10 flex justify-center">
                <img src="{{ asset('logo-grises.svg') }}" alt="Sentry Guard" class="h-24 w-auto object-contain opacity-90">
            </div>

            @if ($errors->any())
                <div class="mb-5 border border-red-900/50 bg-red-950/30 px-3 py-2 text-sm text-red-200">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.submit') }}" class="space-y-6">
                @csrf

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-100" for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required
                        class="h-12 w-full border border-[#3b4352] bg-[#1b1f28] px-4 text-base text-white placeholder:text-slate-500 focus:outline-none focus:border-[#5a6476]" />
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-100" for="password">Contraseña</label>
                    <div id="password-wrap" class="relative">
                        <input id="password" name="password" type="password" required
                            class="h-12 w-full border border-[#3b4352] bg-[#1b1f28] px-4 pr-12 text-base text-white placeholder:text-slate-500 focus:outline-none focus:border-[#5a6476]" />
                        <button type="button" id="toggle-password" class="absolute inset-y-0 right-0 flex w-12 items-center justify-center text-slate-400 hover:text-slate-200" aria-label="Mostrar contraseña">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <button id="login-submit" type="submit"
                    class="mt-2 h-14 w-full bg-blue-600 text-[18px] font-semibold text-white transition-colors hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300/30 disabled:cursor-not-allowed disabled:opacity-70 disabled:hover:bg-blue-600">
                    <span id="login-submit-label">Iniciar sesión</span>
                    <span id="login-submit-loading" class="hidden items-center justify-center gap-2">
                        <span class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-white/35 border-t-white"></span>
                        <span>Procesando...</span>
                    </span>
                </button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.getElementById('toggle-password')?.addEventListener('click', () => {
            const input = document.getElementById('password');
            if (!input) return;
            input.type = input.type === 'password' ? 'text' : 'password';
        });

        document.querySelector('form[action="{{ route('login.submit') }}"]')?.addEventListener('submit', () => {
            const btn = document.getElementById('login-submit');
            const label = document.getElementById('login-submit-label');
            const loading = document.getElementById('login-submit-loading');
            if (!btn || !label || !loading) return;
            btn.disabled = true;
            label.classList.add('hidden');
            loading.classList.remove('hidden');
            loading.classList.add('inline-flex');
            label.style.display = 'none';
            loading.style.display = 'inline-flex';
        });
    </script>
@endpush

