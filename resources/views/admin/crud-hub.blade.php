@extends('layouts.admin', ['user' => $user ?? session('api_user')])

@section('title', 'ABM y CRUD')

@php
    $activeAdminNav = 'crud';
@endphp

@section('content')
    <div>
        <a href="{{ route('admin.home') }}" class="text-sm text-sky-400 hover:text-sky-300">← Volver al panel</a>
        <h1 class="mt-3 text-2xl font-semibold text-white">ABM y CRUD</h1>
        <p class="mt-1 text-slate-400">Gestioná entidades. Las secciones en gris aún se integran con la API.</p>
    </div>

    <ul class="mt-10 space-y-3">
        <li>
            <a href="{{ route('admin.objetivos.index') }}"
               class="block rounded-xl border border-slate-700 bg-slate-900/50 px-5 py-4 text-left transition hover:border-sky-500/45 hover:bg-slate-800/70">
                <span class="font-medium text-white">Objetivos</span>
                <span class="ml-2 text-sm text-slate-500">— alta, baja lógica, modificación, consulta</span>
            </a>
        </li>
        <li>
            <div class="rounded-xl border border-slate-800 bg-slate-900/20 px-5 py-4 text-slate-500">
                <span class="font-medium">Usuarios</span>
                <span class="ml-2 text-sm">(próximamente)</span>
            </div>
        </li>
        <li>
            <div class="rounded-xl border border-slate-800 bg-slate-900/20 px-5 py-4 text-slate-500">
                <span class="font-medium">Clientes</span>
                <span class="ml-2 text-sm">(próximamente)</span>
            </div>
        </li>
    </ul>
@endsection
