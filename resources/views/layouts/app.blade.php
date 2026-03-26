<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sentry Guard')</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('isotipo-grises.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 p-4 md:p-6">
    <div class="w-full space-y-4">
        @include('layouts.navbar', ['activeNav' => $activeNav ?? ''])
        @include('layouts.profile-sidebar')

        <div class="grid grid-cols-1 gap-3">
            <aside class="hidden" id="layout-left-panel">
                @yield('leftPanel')
            </aside>

            <main id="layout-center-panel">
                @yield('content')
            </main>
        </div>

    </div>

    <script>
        window.SENTRY_LAYOUT = {
            apiStatusUrl: @json(route('x.objetivos')),
            loginUrl: @json(route('login.form')),
        };

        // Fallback inline: garantiza navbar/perfil/logout aun con bundle JS desactualizado.
        if (!window.__sentryLayoutShellInitialized) {
            window.__sentryLayoutShellInitialized = true;
            document.addEventListener('DOMContentLoaded', () => {
                const clockEl = document.getElementById('topbar-clock');
                if (clockEl) {
                    const renderClock = () => { clockEl.textContent = new Date().toLocaleTimeString('es-AR'); };
                    renderClock();
                    setInterval(renderClock, 1000);
                }

                const toggle = document.getElementById('profile-menu-toggle');
                const panel = document.getElementById('profile-menu-panel');
                if (toggle && panel) {
                    toggle.addEventListener('click', (e) => {
                        e.stopPropagation();
                        panel.classList.toggle('hidden');
                    });
                    panel.addEventListener('click', (e) => e.stopPropagation());
                    document.addEventListener('click', () => panel.classList.add('hidden'));
                    document.addEventListener('keydown', (e) => {
                        if (e.key === 'Escape') panel.classList.add('hidden');
                    });
                }

                document.querySelectorAll('.js-logout-form').forEach((form) => {
                    form.addEventListener('submit', () => {
                        const btn = form.querySelector('.js-logout-btn');
                        const label = form.querySelector('.js-logout-label');
                        const loading = form.querySelector('.js-logout-loading');
                        if (!btn || !label || !loading) return;
                        btn.disabled = true;
                        label.classList.add('hidden');
                        loading.classList.remove('hidden');
                        loading.classList.add('inline-flex');
                        label.style.display = 'none';
                        loading.style.display = 'inline-flex';
                    });
                });

                const apiUrl = window.SENTRY_LAYOUT?.apiStatusUrl;
                const dot = document.getElementById('api-status-dot');
                const text = document.getElementById('api-status-text');
                const setApiStatus = (ok) => {
                    if (!dot || !text) return;
                    if (ok) {
                        dot.className = 'inline-block h-2.5 w-2.5 rounded-full bg-emerald-400';
                        text.style.color = 'rgba(167, 243, 208, 0.85)';
                        text.textContent = 'Conectado';
                    } else {
                        dot.className = 'inline-block h-2.5 w-2.5 rounded-full bg-red-400';
                        text.style.color = 'rgba(254, 205, 211, 0.85)';
                        text.textContent = 'Desconectado';
                    }
                };
                if (apiUrl) {
                    const checkApi = async () => {
                        try {
                            const res = await fetch(apiUrl, { method: 'GET', headers: { Accept: 'application/json' }, cache: 'no-store' });
                            const data = await res.json().catch(() => null);
                            if (res.status === 401 && data?.session_expired) {
                                const loginUrl = window.SENTRY_LAYOUT?.loginUrl;
                                if (loginUrl) window.location.href = loginUrl;
                                return;
                            }
                            setApiStatus(res.ok);
                        } catch (_) {
                            setApiStatus(false);
                        }
                    };
                    checkApi();
                    setInterval(checkApi, 15000);
                }
            });
        }
    </script>
    @stack('scripts')
</body>
</html>
