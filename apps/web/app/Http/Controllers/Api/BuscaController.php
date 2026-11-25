<?php

namespace App\Http\Controllers\Api;

use App\Actions\Buscar;
use App\Http\Controllers\Controller;
use App\Http\Requests\BuscaRequest;
use App\Http\Resources\BuscaResource;

class BuscaController extends Controller
{
    /**
     * O teto é aplicado aqui, não como 422: o contrato do v2 cortava o limite em
     * vez de recusar, e um cliente que pede 50 deve receber 20 — não erro.
     */
    public function __invoke(BuscaRequest $request, Buscar $acao): BuscaResource
    {
        $pedido = (int) ($request->validated('limite') ?? Buscar::LIMITE_PADRAO);

        return new BuscaResource($acao->executar(
            (string) ($request->validated('q') ?? ''),
            max(1, min(Buscar::LIMITE_MAXIMO, $pedido)),
        ));
    }
}
