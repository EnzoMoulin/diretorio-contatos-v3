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

// A folha A4 é o recorte inteiro, sem paginação e sem os controles da tela.
Route::get('/telefones/impressao', [ListagemContatosController::class, 'impressao'])
    ->name('canal.telefones.impressao')
    ->defaults('canal', 'telefones');

Route::get('/whatsapp/impressao', [ListagemContatosController::class, 'impressao'])
    ->name('canal.whatsapp.impressao')
    ->defaults('canal', 'whatsapp');

Route::get('/emails/impressao', [ListagemContatosController::class, 'impressao'])
    ->name('canal.emails.impressao')
    ->defaults('canal', 'emails');
