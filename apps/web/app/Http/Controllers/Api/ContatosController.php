<?php

namespace App\Http\Controllers\Api;

use App\Actions\ListarContatos;
use App\Http\Controllers\Controller;
use App\Http\Requests\ContatosRequest;
use App\Http\Resources\ContatosResource;

class ContatosController extends Controller
{
    public function __invoke(ContatosRequest $request, ListarContatos $acao): ContatosResource
    {
        return new ContatosResource($acao->executar(
            $this->texto($request->validated('tipo')),
            $this->texto($request->validated('comarca')),
            $this->texto($request->validated('local')),
            $this->texto($request->validated('no')),
            (string) ($this->texto($request->validated('q')) ?? ''),
            (int) ($request->validated('pagina') ?? 1),
            (int) ($request->validated('por_pagina') ?? ListarContatos::POR_PAGINA),
        ));
    }

    private function texto(mixed $valor): ?string
    {
        if (! is_string($valor)) {
            return null;
        }

        $valor = trim($valor);

        return $valor === '' ? null : $valor;
    }
}
