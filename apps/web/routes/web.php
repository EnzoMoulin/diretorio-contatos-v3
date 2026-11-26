<?php

use App\Http\Controllers\InicioController;
use App\Http\Controllers\RamaisController;
use Illuminate\Support\Facades\Route;

Route::get('/', InicioController::class)->name('inicio');

Route::get('/ramais', RamaisController::class)->name('ramais');
