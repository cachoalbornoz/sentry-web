@extends('layouts.admin', ['user' => $user ?? session('api_user')])

@section('title', 'Settings')

@php
    $activeAdminNav = 'settings';
@endphp

@section('content')
    <div>
        <a href="{{ route('admin.home') }}" class="text-sm text-sky-400 hover:text-sky-300">← Volver al panel</a>
        <h1 class="mt-3 text-2xl font-semibold text-white">Settings</h1>
        <p class="mt-2 text-slate-400">Esta sección se completará con preferencias y parámetros de la aplicación.</p>
    </div>
    <p class="mt-8 rounded-xl border border-dashed border-slate-700 p-8 text-center text-slate-500">Próximamente</p>
@endsection
