@extends('layouts.app', ['activeNav' => 'objetivos'])

@section('title', 'Objetivos')

@section('content')
    <section id="objetivos-page"
             data-objetivos-url="{{ route('x.objetivos') }}"
             data-eventos-list-url="{{ route('x.eventos') }}"
             data-detalle-url="{{ route('x.objetivos.detalle', ['objetivo' => '__OBJETIVO__']) }}"
             data-contactos-url="{{ route('x.objetivos.contactos', ['objetivo' => '__OBJETIVO__']) }}"
             data-eventos-url="{{ route('x.objetivos.eventos', ['objetivo' => '__OBJETIVO__']) }}"
             data-zonas-url="{{ route('x.objetivos.zonas', ['objetivo' => '__OBJETIVO__']) }}"
             data-login-url="{{ route('login.form') }}"
             data-has-objetivos-scope="{{ ($hasObjetivoScope ?? false) ? '1' : '0' }}"
             data-allowed-objetivos-ids='@json($allowedObjetivoIds ?? [])'>
        <div class="rounded-xl border border-slate-800 bg-slate-900/25 p-4">
            <div class="objetivos-toolbar">
                <input id="objetivos-search" class="objetivos-search" type="search" placeholder="Buscar por nombre o descripción">
            </div>
        </div>

        <section class="objetivos-stats" id="objetivos-stats">
            <article class="objetivos-stat">
                <div class="objetivos-stat-label">Total</div>
                <div class="objetivos-stat-value" id="stat-total">0</div>
            </article>
            <article class="objetivos-stat">
                <div class="objetivos-stat-label">En línea</div>
                <div class="objetivos-stat-value" id="stat-online">0</div>
            </article>
            <article class="objetivos-stat">
                <div class="objetivos-stat-label">Críticos</div>
                <div class="objetivos-stat-value" id="stat-critico">0</div>
            </article>
            <article class="objetivos-stat">
                <div class="objetivos-stat-label">Inactivos</div>
                <div class="objetivos-stat-value" id="stat-offline">0</div>
            </article>
            <article class="objetivos-stat">
                <div class="objetivos-stat-label">Sin señal</div>
                <div class="objetivos-stat-value" id="stat-muerto">0</div>
            </article>
        </section>

        <div id="objetivos-loading" class="objetivos-loading">
            <div class="objetivos-loading-spinner"></div>
            Cargando objetivos...
        </div>

        <div id="objetivos-empty" class="objetivos-empty hidden"></div>
        <div id="objetivos-grid" class="objetivos-grid hidden"></div>
    </section>

    <div id="objetivo-modal-backdrop" class="objetivo-modal-backdrop hidden">
        <div class="objetivo-modal" role="dialog" aria-modal="true" aria-labelledby="objetivo-modal-headline">
            <div class="objetivo-modal-header">
                <div class="objetivo-modal-title-wrap">
                    <div id="objetivo-modal-icon" class="objetivo-icon estado-desconocido"></div>
                    <div class="objetivo-modal-title-text">
                        <h2>Detalle del Objetivo</h2>
                        <div id="objetivo-modal-headline" class="objetivo-headline">—</div>
                        <div id="objetivo-modal-status" class="objetivo-status mt-2">
                            <span class="objetivo-status-dot"></span>
                            <span>—</span>
                        </div>
                    </div>
                </div>
                <button id="objetivo-modal-close" class="objetivo-modal-close" type="button" aria-label="Cerrar detalle">×</button>
            </div>

            <div class="objetivo-modal-tabs">
                <button type="button" class="objetivo-modal-tab is-active" data-tab="datos">Datos</button>
                <button type="button" class="objetivo-modal-tab" data-tab="contactos">Contactos</button>
                <button type="button" class="objetivo-modal-tab" data-tab="eventos">Eventos</button>
                <button type="button" class="objetivo-modal-tab" data-tab="zonas">Zonas</button>
            </div>

            <div class="objetivo-modal-body">
                <div id="objetivo-tab-content" class="objetivo-tab-panel"></div>
            </div>
        </div>
    </div>
@endsection

