<?php

use App\Http\Controllers\Api\MedicoController;
use App\Http\Controllers\Api\PacienteController;
use App\Http\Controllers\Api\RecomendacionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - MediRecomienda
|--------------------------------------------------------------------------
*/

// ── Público (sin autenticación) ──────────────────────────────────────────────
// Analiza síntomas con GPT-4o-mini + scoring de keywords. No guarda en BD.
Route::post('/analizar', [RecomendacionController::class, 'analizar']);

// ── Autenticado con Sanctum ──────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', fn (Request $request) => $request->user());

    // Pacientes
    Route::apiResource('pacientes', PacienteController::class);

    // Médicos (solo lectura pública con auth)
    Route::apiResource('medicos', MedicoController::class)->only(['index', 'show']);

    // Recomendaciones autenticadas (guarda en BD)
    Route::get('recomendaciones',                                   [RecomendacionController::class, 'index']);
    Route::post('recomendaciones',                                  [RecomendacionController::class, 'store']);
    Route::get('recomendaciones/{consulta}',                        [RecomendacionController::class, 'show']);
    Route::patch('recomendaciones/{consulta}/seleccionar/{recomendacion}',
                                                                    [RecomendacionController::class, 'seleccionar']);
});
