<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Objetivos</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('isotipo-grises.svg') }}">
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 p-4 md:p-6">
    <div class="w-full space-y-4">
        <header class="w-full bg-[#0d0f14] px-3 py-2.5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3.5">
                    <img src="{{ asset('isotipo-grises.svg') }}" alt="Sentry" class="h-5 w-auto object-contain opacity-90">
                    <span class="h-6 w-px bg-slate-700/80"></span>
                    <nav class="flex items-center gap-0.5 text-sm">
                        <a href="{{ route('dashboard') }}" class="inline-flex h-9 items-center border-b-2 border-transparent px-3 text-slate-200 hover:text-white hover:border-slate-500">Inicio</a>
                        <a href="{{ route('objetivos') }}" class="inline-flex h-9 items-center border-b-2 border-[#3b82f6] px-3 text-slate-100">Objetivos</a>
                    </nav>
                </div>
                <div class="flex items-center gap-2 md:gap-2.5 text-xs">
                    <span id="topbar-clock" class="font-medium text-slate-200">--:--:--</span>
                    <span id="api-status-badge" class="inline-flex items-center gap-1.5 px-1 py-1 text-xs">
                        <span id="api-status-dot" class="inline-block h-2.5 w-2.5 rounded-full bg-slate-500"></span>
                        <span id="api-status-text" class="text-slate-300">Verificando API...</span>
                    </span>
                    <div class="relative">
                        <button id="profile-menu-toggle" type="button" class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-slate-700 bg-slate-900/60 text-slate-100 hover:bg-slate-800" aria-label="Perfil">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="8" r="4"></circle>
                                <path d="M4 20c1.7-4 5-6 8-6s6.3 2 8 6"></path>
                            </svg>
                        </button>
                        <div id="profile-menu-panel" class="hidden fixed w-72 overflow-y-auto border-l border-slate-700 p-5 shadow-2xl shadow-black flex flex-col" style="background-color:#2a2b2f; top:44px; right:0; bottom:0; z-index:130000;">
                            @php($user = session('api_user', []))
                            @php($name = trim((string)($user['nombre'] ?? 'Usuario')))
                            @php($email = trim((string)($user['email'] ?? '')))
                            @php($rol = trim((string)($user['rol'] ?? 'Administrador')))

                            <div class="mx-auto mb-4 mt-2 flex h-16 w-16 items-center justify-center rounded-full bg-slate-600/70 text-2xl text-slate-100">
                                {{ strtoupper(substr($name, 0, 1)) }}
                            </div>
                            <div class="text-center font-medium text-slate-100" style="font-size:28px;line-height:1.1;">{{ $name }}</div>
                            <div class="mt-1 text-center text-sm text-slate-300">{{ $email }}</div>
                            <div class="mt-3 text-center">
                                <span class="inline-block rounded border border-slate-500 px-2 py-1 text-xs text-slate-200">{{ $rol }}</span>
                            </div>
                            <div class="mt-auto pt-8 pb-2 flex min-h-[190px] flex-col">
                                <a href="{{ route('debug') }}" class="block w-full rounded-none bg-slate-500 px-4 py-3 text-left text-sm text-white hover:bg-slate-400">Ver Perfil</a>
                                <form method="POST" action="{{ route('logout') }}" class="js-logout-form mt-auto">
                                    @csrf
                                    <button type="submit" class="js-logout-btn mx-auto block w-full max-w-[220px] rounded-md px-4 py-3 text-center text-sm font-medium text-white hover:bg-red-500 disabled:cursor-not-allowed disabled:opacity-70 disabled:hover:bg-[#dc2626]" style="background:#dc2626;border:1px solid rgba(254,202,202,.35);">
                                        <span class="js-logout-label">Cerrar sesión</span>
                                        <span class="js-logout-loading hidden items-center justify-center gap-2">
                                            <span class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-white/35 border-t-white"></span>
                                            <span>Cerrando...</span>
                                        </span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <section class="rounded-xl border border-slate-800 bg-slate-900/40 p-6">
            <h1 class="text-xl font-semibold">Objetivos</h1>
            <p class="mt-2 text-sm text-slate-400">Pantalla en construcción. Ya está lista la navegación superior y estado de conexión.</p>
        </section>
    </div>

    <script>
        function startClock() {
            const clockEl = document.getElementById('topbar-clock');
            if (!clockEl) return;
            const render = () => { clockEl.textContent = new Date().toLocaleTimeString('es-AR'); };
            render();
            setInterval(render, 1000);
        }

        function setApiConnectionStatus(isConnected) {
            const dot = document.getElementById('api-status-dot');
            const text = document.getElementById('api-status-text');
            if (!dot || !text) return;
            if (isConnected) {
                dot.className = 'inline-block h-2.5 w-2.5 rounded-full bg-emerald-400';
                text.style.color = 'rgba(167, 243, 208, 0.85)';
                text.textContent = 'Conectado';
                return;
            }
            dot.className = 'inline-block h-2.5 w-2.5 rounded-full bg-red-400';
            text.style.color = 'rgba(254, 205, 211, 0.85)';
            text.textContent = 'Desconectado';
        }

        async function checkApiConnection() {
            try {
                const res = await fetch('{{ route('x.objetivos') }}', {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' },
                    cache: 'no-store',
                });
                setApiConnectionStatus(res.ok);
            } catch (_) {
                setApiConnectionStatus(false);
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            startClock();
            checkApiConnection();
            setInterval(checkApiConnection, 15000);
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
                });
            });
        });
    </script>
</body>
</html>
