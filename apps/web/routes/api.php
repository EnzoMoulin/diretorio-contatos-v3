<?php

use App\Http\Controllers\Api\BuscaController;
use App\Http\Controllers\Api\HierarquiaController;
use Illuminate\Support\Facades\Route;

Route::get('/v1/hierarquia', HierarquiaController::class)
    ->name('api.v1.hierarquia');

Route::get('/v1/busca', BuscaController::class)
    ->name('api.v1.busca');
