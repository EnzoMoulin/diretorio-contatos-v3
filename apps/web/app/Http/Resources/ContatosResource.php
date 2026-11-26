<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property array{
 *     tipo: string|null,
 *     filtros: array{comarca: string|null, local: string|null, no: string|null, q: string},
 *     total: int,
 *     pagina: int,
 *     por_pagina: int,
 *     paginas: int,
 *     totais: array<string, int>,
 *     itens: list<array<string, mixed>>
 * } $resource
 */
class ContatosResource extends JsonResource
{
    /** O contrato do v2 não tem envelope `data`. */
    public static $wrap = null;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'tipo' => $this->resource['tipo'],
            'filtros' => $this->resource['filtros'],
            'total' => $this->resource['total'],
            'pagina' => $this->resource['pagina'],
            'por_pagina' => $this->resource['por_pagina'],
            'paginas' => $this->resource['paginas'],
            'totais' => $this->resource['totais'],
            'itens' => $this->resource['itens'],
        ];
    }
}
