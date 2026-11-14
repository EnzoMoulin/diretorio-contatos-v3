<?php

use App\Http\Controllers\PainelController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PainelController::class, 'inicio'])->name('inicio');
Route::get('/comarca/{chave}', [PainelController::class, 'comarca'])->name('comarca');
Route::get('/local/{chave}', [PainelController::class, 'local'])->name('local');
Route::get('/contato/{chave}', [PainelController::class, 'contato'])->name('contato');
Route::get('/busca', [PainelController::class, 'busca'])->name('busca');
Route::get('/municipio/{ibge}', [PainelController::class, 'municipio'])->name('municipio');

Route::get('/geo/es-municipios.geojson', function () {
    return response()->file(base_path('data/es-municipios.geojson'), [
        'Content-Type' => 'application/geo+json',
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->name('geo');
