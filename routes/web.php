<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\CrudHubController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ObjetivoController;
use App\Http\Controllers\Admin\SettingsPageController;
use App\Http\Controllers\AuthWebController;
use App\Http\Controllers\DashboardWebController;
use App\Http\Controllers\HomeWebController;
use App\Http\Controllers\ApiProxyController;
use App\Http\Controllers\SseProxyController;
use App\Http\Controllers\TileProxyController;
use App\Support\AdminRole;

Route::get('/', function (Request $request) {
    if (AdminRole::isElevated($request->session()->get('api_user'))) {
        return redirect()->route('admin.home');
    }

    return redirect()->route('dashboard');
})->middleware('api.token');

Route::get('/login', [AuthWebController::class, 'showLogin'])->name('login.form');
Route::post('/login', [AuthWebController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthWebController::class, 'logout'])->name('logout');

Route::get('/dashboard', HomeWebController::class)
    ->middleware('api.token')
    ->name('dashboard');

Route::get('/objetivos', function (Request $request) {
    if (AdminRole::isElevated($request->session()->get('api_user'))) {
        return redirect()->route('admin.home');
    }

    return view('objetivos');
})->middleware('api.token')->name('objetivos');

Route::prefix('admin')
    ->middleware(['api.token', 'admin.elevated'])
    ->name('admin.')
    ->group(function () {
        Route::get('/', DashboardController::class)->name('home');
        Route::get('/crud', CrudHubController::class)->name('crud');
        Route::get('/settings', SettingsPageController::class)->name('settings');
        Route::get('/objetivos', [ObjetivoController::class, 'index'])->name('objetivos.index');
        Route::get('/objetivos/nuevo', [ObjetivoController::class, 'create'])->name('objetivos.create');
        Route::post('/objetivos', [ObjetivoController::class, 'store'])->name('objetivos.store');
        Route::get('/objetivos/{objetivo}', [ObjetivoController::class, 'show'])->name('objetivos.show');
        Route::get('/objetivos/{objetivo}/editar', [ObjetivoController::class, 'edit'])->name('objetivos.edit');
        Route::put('/objetivos/{objetivo}', [ObjetivoController::class, 'update'])->name('objetivos.update');
        Route::delete('/objetivos/{objetivo}', [ObjetivoController::class, 'destroy'])->name('objetivos.destroy');
    });

Route::get('/debug', DashboardWebController::class)
    ->middleware('api.token')
    ->name('debug');

Route::prefix('/x')->middleware('api.token')->group(function () {
    Route::get('/eventos', [ApiProxyController::class, 'eventos'])->name('x.eventos');
    Route::get('/objetivos', [ApiProxyController::class, 'objetivos'])->name('x.objetivos');
    Route::get('/objetivos/eventos/{objetivo}', [ApiProxyController::class, 'objetivoEventos'])->name('x.objetivos.eventos');
    Route::get('/objetivos/zonas/{objetivo}', [ApiProxyController::class, 'objetivoZonas'])->name('x.objetivos.zonas');
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
