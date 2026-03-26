@extends('layouts.app', ['activeNav' => 'debug'])

@section('title', 'Debug')

@section('content')
    <div class="max-w-4xl mx-auto space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold">Dashboard</h1>
                <p class="text-sm text-slate-400">Sesión contra la API (Sanctum) vía Bearer token.</p>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="js-logout-form">
                @csrf
                <button type="submit" class="js-logout-btn rounded-lg border border-slate-700 bg-slate-900/60 px-3 py-2 text-sm hover:bg-slate-900">
                    <span class="js-logout-label">Salir</span>
                    <span class="js-logout-loading hidden items-center justify-center gap-2">
                        <span class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-white/35 border-t-white"></span>
                        <span>Saliendo...</span>
                    </span>
                </button>
            </form>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="rounded-xl border border-slate-800 bg-slate-900/60 shadow-xl shadow-black/20 p-4">
                <h2 class="font-medium mb-2">Usuario (API /me)</h2>
                <pre class="text-xs bg-black/40 text-slate-100 rounded-lg border border-slate-800 p-3 overflow-auto">{{ json_encode($user, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
            <div class="rounded-xl border border-slate-800 bg-slate-900/60 shadow-xl shadow-black/20 p-4">
                <h2 class="font-medium mb-2">Token</h2>
                <div class="text-sm text-slate-200 space-y-2">
                    <div><span class="font-medium text-slate-300">Expira:</span> {{ $token_expires_at ?? 'N/D' }}</div>
                    <div class="text-xs text-slate-500">Guardado en session como <span class="font-mono text-slate-400">api_token</span>.</div>
                </div>
            </div>
        </div>
    </div>
@endsection

