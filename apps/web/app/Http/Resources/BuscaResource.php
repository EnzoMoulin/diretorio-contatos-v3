<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property array{termo: string, expansao: list<string>, total: int, resultados: list<array<string, mixed>>} $resource
 */
class BuscaResource extends JsonResource
{
    /** O contrato não tem envelope `data`. */
    public static $wrap = null;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'termo' => $this->resource['termo'],
            'expansao' => $this->resource['expansao'],
            'total' => $this->resource['total'],
            'resultados' => $this->resource['resultados'],
        ];
    }
}
