<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property array{foruns_total: int, locais: int, nos_total: int, ordem: list<string>, uteis: int} $resource
 */
class HierarquiaResource extends JsonResource
{
    /** O contrato do v2 não tem envelope `data`. */
    public static $wrap = null;

    /** @return array<string, int|list<string>> */
    public function toArray(Request $request): array
    {
        return [
            'foruns_total' => $this->resource['foruns_total'],
            'locais' => $this->resource['locais'],
            'nos_total' => $this->resource['nos_total'],
            'ordem' => $this->resource['ordem'],
            'uteis' => $this->resource['uteis'],
        ];
    }
}
