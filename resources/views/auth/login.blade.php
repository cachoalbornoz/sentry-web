<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ingreso</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 flex items-center justify-center p-6">
    <div class="w-full max-w-md rounded-xl border border-slate-800 bg-slate-900/60 shadow-xl shadow-black/30 p-6">
        <h1 class="text-xl font-semibold mb-1">SentryGuard</h1>
        <p class="text-sm text-slate-400 mb-4">Ingreso para monitores y administradores</p>

        @if ($errors->any())
            <div class="mb-4 rounded border border-red-900/50 bg-red-950/40 text-red-200 p-3 text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.submit') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium mb-1" for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required
                    class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-slate-100 placeholder:text-slate-600 focus:outline-none focus:ring-2 focus:ring-slate-400/40" />
            </div>

            <div>
                <label class="block text-sm font-medium mb-1" for="password">Contraseña</label>
                <input id="password" name="password" type="password" required
                    class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-slate-100 placeholder:text-slate-600 focus:outline-none focus:ring-2 focus:ring-slate-400/40" />
            </div>

            <button type="submit"
                class="w-full rounded-lg bg-slate-100 text-slate-950 py-2 font-semibold hover:bg-white focus:outline-none focus:ring-2 focus:ring-white/20">
                Ingresar
            </button>
        </form>

        <p class="mt-4 text-xs text-slate-500">
            API: <span class="font-mono text-slate-400">{{ config('services.sentry_api.base_url') }}</span>
        </p>
    </div>
</body>
</html>

