<header class="w-full bg-[#0d0f14] px-3 py-2.5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3.5">
            <img src="{{ asset('isotipo-grises.svg') }}" alt="Sentry" class="h-5 w-auto object-contain opacity-90">
            <span class="h-6 w-px bg-slate-700/80"></span>
            <nav class="flex items-center gap-0.5 text-sm">
                <a href="{{ route('dashboard') }}"
                   class="inline-flex h-9 items-center border-b-2 px-3 {{ ($activeNav ?? '') === 'inicio' ? 'border-[#3b82f6] text-slate-100' : 'border-transparent text-slate-200 hover:text-white hover:border-slate-500' }}">
                    Inicio
                </a>
                <a href="{{ route('objetivos') }}"
                   class="inline-flex h-9 items-center border-b-2 px-3 {{ ($activeNav ?? '') === 'objetivos' ? 'border-[#3b82f6] text-slate-100' : 'border-transparent text-slate-200 hover:text-white hover:border-slate-500' }}">
                    Objetivos
                </a>
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
            </div>
        </div>
    </div>
</header>
