<?php

namespace App\Http\Controllers;

use App\Actions\ListarRamais;
use App\Http\Requests\RamaisRequest;
use Illuminate\View\View;

class RamaisController extends Controller
{
    public function __invoke(RamaisRequest $request, ListarRamais $acao): View
    {
        return view('ramais', $acao->executar((string) ($request->validated('q') ?? '')));
    }
}
