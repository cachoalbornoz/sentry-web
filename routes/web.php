<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthWebController;
use App\Http\Controllers\DashboardWebController;
use App\Http\Controllers\HomeWebController;
use App\Http\Controllers\ApiProxyController;
use App\Http\Controllers\SseProxyController;
use App\Http\Controllers\TileProxyController;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/login', [AuthWebController::class, 'showLogin'])->name('login.form');
Route::post('/login', [AuthWebController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthWebController::class, 'logout'])->name('logout');

Route::get('/dashboard', HomeWebController::class)
    ->middleware('api.token')
    ->name('dashboard');

Route::view('/objetivos', 'objetivos')
    ->middleware('api.token')
    ->name('objetivos');

Route::get('/debug', DashboardWebController::class)
    ->middleware('api.token')
    ->name('debug');

Route::prefix('/x')->middleware('api.token')->group(function () {
    Route::get('/eventos', [ApiProxyController::class, 'eventos'])->name('x.eventos');
    Route::get('/objetivos', [ApiProxyController::class, 'objetivos'])->name('x.objetivos');
    Route::get('/objetivos/{objetivo}', [ApiProxyController::class, 'objetivoDetalle'])->name('x.objetivos.detalle');
    Route::get('/objetivos/contactos/{objetivo}', [ApiProxyController::class, 'objetivoContactos'])->name('x.objetivos.contactos');
    Route::get('/cedulacion/tipos', [ApiProxyController::class, 'cedulacionTipos'])->name('x.cedulacion.tipos');
    Route::get('/cedulacion/observaciones', [ApiProxyController::class, 'cedulacionObservaciones'])->name('x.cedulacion.observaciones');
    Route::post('/cedulacion/guardar', [ApiProxyController::class, 'guardarCedulacion'])->name('x.cedulacion.guardar');
    Route::get('/sse/dashboard', [SseProxyController::class, 'dashboard'])->name('x.sse.dashboard');
});

// Tiles sin auth (no exponen datos sensibles y simplifica debug/bloqueos).
Route::get('/x/tiles/carto-dark/{z}/{x}/{y}', [TileProxyController::class, 'cartoDark'])->name('x.tiles.carto');
Route::get('/x/tiles/stadia-dark/{z}/{x}/{y}', [TileProxyController::class, 'stadiaDark'])->name('x.tiles.stadia');
