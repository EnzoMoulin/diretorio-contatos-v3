<?php

namespace App\Actions;

use App\Models\Comarca;
use App\Models\Local;
use App\Models\No;
use App\Models\TelefoneUtil;

/**
 * Primeira leitura do v3: os cinco números da capa e a ordem das comarcas.
 *
 * O contrato é o de `docs/paridade/esperado.json` (rota `hierarquia` do v2):
 * `foruns_total`, `locais`, `nos_total`, `ordem` e `uteis`. A Sede entra em
 * `ordem` como o primeiro item, sem ser comarca, como no v2.
 */
final class ObterHierarquia
{
    /** @return array{foruns_total: int, locais: int, nos_total: int, ordem: list<string>, uteis: int} */
    public function executar(): array
    {
        $comarcas = Comarca::query()
            ->whereNull('arquivado_em')
            ->orderBy('ordem_num')
            ->orderBy('ordem_idx')
            ->orderBy('nome')
            ->pluck('nome');

        $sede = Local::query()
            ->where('tipo', 'SEDE')
            ->whereNull('arquivado_em')
            ->orderBy('ordem_num')
            ->value('nome');

        return [
            'foruns_total' => Local::query()
                ->where('tipo', 'FORUM')
                ->whereNull('arquivado_em')
                ->count(),
            'locais' => $comarcas->count() + ($sede === null ? 0 : 1),
            'nos_total' => No::query()
                ->whereNull('arquivado_em')
                ->count(),
            'ordem' => $sede === null
                ? $comarcas->values()->all()
                : [$sede, ...$comarcas->values()->all()],
            'uteis' => TelefoneUtil::query()->count(),
        ];
    }
}
