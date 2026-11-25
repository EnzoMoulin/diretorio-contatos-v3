<?php

namespace App\Http\Controllers\Api;

use App\Actions\ObterHierarquia;
use App\Http\Controllers\Controller;
use App\Http\Resources\HierarquiaResource;

class HierarquiaController extends Controller
{
    public function __invoke(ObterHierarquia $acao): HierarquiaResource
    {
        return new HierarquiaResource($acao->executar());
    }
}
