<?php

namespace App\Http\Controllers;

use App\Actions\ObterHierarquia;
use Illuminate\View\View;

class InicioController extends Controller
{
    public function __invoke(ObterHierarquia $acao): View
    {
        return view('inicio', ['hierarquia' => $acao->executar()]);
    }
}
