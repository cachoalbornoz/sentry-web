@extends('layouts.admin', ['user' => $user ?? session('api_user')])

@section('title', 'Panel de administración')

@php
    $activeAdminNav = 'home';
@endphp

@section('content')
    <div class="text-center">
        <h1 class="text-2xl font-semibold text-white">Panel de administración</h1>
        <p class="mt-2 text-slate-400">Elegí una sección para continuar</p>
    </div>

    <div class="mt-10 grid max-w-3xl grid-cols-1 gap-6 sm:grid-cols-2 sm:gap-8 mx-auto">
        <a href="{{ route('admin.crud') }}"
           class="group flex min-h-[140px] flex-col items-center justify-center rounded-2xl border border-slate-700/80 bg-slate-900/50 px-6 py-10 text-center shadow-lg transition
                  hover:-translate-y-0.5 hover:border-sky-500/50 hover:bg-slate-800/80 hover:shadow-sky-900/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500/50">
            <span class="text-lg font-semibold text-slate-100 group-hover:text-white">ABM / CRUD</span>
            <span class="mt-2 text-sm text-slate-400 group-hover:text-slate-300">Objetivos, usuarios, clientes</span>
        </a>
        <a href="{{ route('admin.settings') }}"
           class="group flex min-h-[140px] flex-col items-center justify-center rounded-2xl border border-slate-700/80 bg-slate-900/50 px-6 py-10 text-center shadow-lg transition
                  hover:-translate-y-0.5 hover:border-violet-500/50 hover:bg-slate-800/80 hover:shadow-violet-900/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-500/50">
            <span class="text-lg font-semibold text-slate-100 group-hover:text-white">Settings</span>
            <span class="mt-2 text-sm text-slate-400 group-hover:text-slate-300">Configuración global (próximamente)</span>
        </a>
    </div>
@endsection
