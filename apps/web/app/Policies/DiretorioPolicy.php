<?php

namespace App\Policies;

use App\Escopo\EscopoResolvido;
use App\Escopo\ResolvedorDeEscopo;
use App\Models\Comarca;
use App\Models\Contato;
use App\Models\Local;
use App\Models\No;
use App\Models\Usuario;

/**
 * Uma regra, dois usos: a mesma `EscopoResolvido` que responde `editar`
 * alimenta `Contato::editaveisPor()`. O papel decide se escreve; o escopo
 * decide onde.
 */
final class DiretorioPolicy
{
    public function __construct(private readonly ResolvedorDeEscopo $escopos)
    {
    }

    public function editar(Usuario $usuario, Comarca|Local|No|Contato $alvo): bool
    {
        $escopo = $this->escopos->para($usuario);

        return $escopo->escreve && $this->cobre($escopo, $alvo);
    }

    public function ver(Usuario $usuario, Comarca|Local|No|Contato $alvo): bool
    {
        $escopo = $this->escopos->para($usuario);

        if ($escopo->vazio() && ! $escopo->tudo) {
            return false;
        }

        return $this->cobre($escopo, $alvo);
    }

    private function cobre(EscopoResolvido $escopo, Comarca|Local|No|Contato $alvo): bool
    {
        if ($alvo instanceof Comarca) {
            return $escopo->cobreComarca((int) $alvo->id);
        }

        if ($alvo instanceof Local) {
            return $escopo->cobreLocal((int) $alvo->id);
        }

        if ($alvo instanceof No) {
            return $escopo->cobreNo((int) $alvo->id);
        }

        return $escopo->cobreContato($alvo);
    }
}
