@php($user = session('api_user', []))
@php($name = trim((string)($user['nombre'] ?? 'Usuario')))
@php($email = trim((string)($user['email'] ?? '')))
@php($rol = trim((string)($user['rol'] ?? 'Administrador')))

<aside id="profile-menu-panel" class="profile-menu-panel hidden fixed w-72 overflow-y-auto border-l border-slate-700 p-5 shadow-2xl shadow-black flex flex-col">
    <div class="mx-auto mb-4 mt-2 flex h-16 w-16 items-center justify-center rounded-full bg-slate-600/70 text-2xl text-slate-100">
        {{ strtoupper(substr($name, 0, 1)) }}
    </div>
    <div class="profile-menu-name text-center font-medium text-slate-100">{{ $name }}</div>
    <div class="mt-1 text-center text-sm text-slate-300">{{ $email }}</div>
    <div class="mt-3 text-center">
        <span class="inline-block rounded border border-slate-500 px-2 py-1 text-xs text-slate-200">{{ $rol }}</span>
    </div>
    <div class="mt-auto pt-8 pb-2 flex min-h-[190px] flex-col">
        <a href="{{ route('debug') }}" class="block w-full rounded-none bg-slate-500 px-4 py-3 text-left text-sm text-white hover:bg-slate-400">Ver Perfil</a>
        <form method="POST" action="{{ route('logout') }}" class="js-logout-form mt-auto">
            @csrf
            <button type="submit" class="profile-logout-btn js-logout-btn mx-auto block w-full max-w-[220px] rounded-md px-4 py-3 text-center text-sm font-medium text-white hover:bg-red-500 disabled:cursor-not-allowed disabled:opacity-70 disabled:hover:bg-[#dc2626]">
                <span class="js-logout-label">Cerrar sesión</span>
                <span class="js-logout-loading hidden items-center justify-center gap-2">
                    <span class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-white/35 border-t-white"></span>
                    <span>Cerrando...</span>
                </span>
            </button>
        </form>
    </div>
</aside>
