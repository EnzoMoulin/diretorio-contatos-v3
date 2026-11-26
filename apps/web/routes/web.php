<?php

use App\Http\Controllers\InicioController;
use App\Http\Controllers\ListagemContatosController;
use App\Http\Controllers\RamaisController;
use Illuminate\Support\Facades\Route;

Route::get('/', InicioController::class)->name('inicio');

Route::get('/ramais', RamaisController::class)->name('ramais');

// A listagem é a mesma; o eixo é o `tipo`. O `defaults` evita três controllers.
Route::get('/telefones', ListagemContatosController::class)
    ->name('canal.telefones')
    ->defaults('canal', 'telefones');

Route::get('/whatsapp', ListagemContatosController::class)
    ->name('canal.whatsapp')
    ->defaults('canal', 'whatsapp');

Route::get('/emails', ListagemContatosController::class)
    ->name('canal.emails')
    ->defaults('canal', 'emails');
