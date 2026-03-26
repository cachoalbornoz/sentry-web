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
                    <span id="api-status-badge" class="inline-flex items-center gap-1.5 rounded-md border border-slate-700 px-2 py-1 text-xs text-slate-300">
                        <span id="api-status-dot" class="inline-block h-2.5 w-2.5 rounded-full bg-slate-500"></span>
                        <span id="api-status-text">Verificando API...</span>
                    </span>
                    <a href="{{ route('debug') }}" class="rounded-md border border-slate-700 bg-slate-900/60 px-2.5 py-1 text-slate-100 hover:bg-slate-800">Perfil</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="rounded-md border border-slate-700 bg-slate-900/60 px-2.5 py-1 text-slate-100 hover:bg-slate-800">Salir</button>
                    </form>
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
                text.textContent = 'Conectado';
                return;
            }
            dot.className = 'inline-block h-2.5 w-2.5 rounded-full bg-red-400';
            text.textContent = 'Sin conexión';
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
        });
    </script>
</body>
</html>
