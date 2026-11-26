<?php

namespace App\Escopo;

use App\Models\Acesso;
use App\Models\Comarca;
use App\Models\Local;
use App\Models\No;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Builder;

/**
 * Traduz as chaves de `acessos` em conjuntos de ids, uma vez por requisição.
 * A herança é a do v2: comarca cobre seus locais e nós; local cobre o local,
 * seus nós e os contatos pendurados nele; nó cobre o nó, seus descendentes e
 * os contatos do nó.
 */
final class ResolvedorDeEscopo
{
    /** @var array<int, EscopoResolvido> */
    private array $cache = [];

    public function para(?Usuario $usuario): EscopoResolvido
    {
        if ($usuario === null || ! $usuario->exists) {
            return EscopoResolvido::nada();
        }

        return $this->cache[$usuario->id] ??= $this->resolver($usuario);
    }

    private function resolver(Usuario $usuario): EscopoResolvido
    {
        if (! $usuario->ativo) {
            return EscopoResolvido::nada();
        }

        if ($usuario->ehDemo()) {
            return EscopoResolvido::tudo(escreve: false);
        }

        if ($usuario->ehAdmin()) {
            return EscopoResolvido::tudo(escreve: true);
        }

        $acessos = Acesso::query()
            ->where('usuario_id', $usuario->id)
            ->get(['nivel', 'alvo_chave']);

        if ($acessos->isEmpty()) {
            return EscopoResolvido::nada();
        }

        /** @var list<string> $chavesComarca */
        $chavesComarca = $acessos->where('nivel', 'comarca')->pluck('alvo_chave')->values()->all();
        /** @var list<string> $chavesLocal */
        $chavesLocal = $acessos->where('nivel', 'local')->pluck('alvo_chave')->values()->all();
        /** @var list<string> $chavesNo */
        $chavesNo = $acessos->where('nivel', 'no')->pluck('alvo_chave')->values()->all();

        $comarcas = $this->ids(
            Comarca::query()->whereIn('chave', $chavesComarca)->whereNull('arquivado_em')
        );

        $locais = [];
        $nos = [];

        if ($comarcas !== []) {
            $locais = $this->ids(
                Local::query()->whereIn('comarca_id', $comarcas)->whereNull('arquivado_em')
            );
            $nos = $this->ids(
                No::query()->whereIn('local_id', $locais)->whereNull('arquivado_em')
            );
        }

        if ($chavesLocal !== []) {
            $locaisDiretos = $this->ids(
                Local::query()->whereIn('chave', $chavesLocal)->whereNull('arquivado_em')
            );
            $locais = $this->juntar($locais, $locaisDiretos);
            $nos = $this->juntar(
                $nos,
                $this->ids(No::query()->whereIn('local_id', $locaisDiretos)->whereNull('arquivado_em'))
            );
        }

        if ($chavesNo !== []) {
            $nosDiretos = $this->ids(
                No::query()->whereIn('chave', $chavesNo)->whereNull('arquivado_em')
            );
            $nos = $this->juntar($nos, $this->descendentes($nosDiretos));
        }

        if ($comarcas === [] && $locais === [] && $nos === []) {
            return EscopoResolvido::nada();
        }

        return new EscopoResolvido(
            tudo: false,
            escreve: true,
            comarcas: $comarcas,
            locais: $locais,
            nos: $nos,
        );
    }

    /**
     * @return list<int>
     */
    private function ids(Builder $query): array
    {
        return $query->pluck('id')->map(static fn (mixed $id): int => (int) $id)->values()->all();
    }

    /**
     * @param  list<int>  $a
     * @param  list<int>  $b
     * @return list<int>
     */
    private function juntar(array $a, array $b): array
    {
        return array_values(array_unique([...$a, ...$b]));
    }

    /**
     * O nó coberto inclui os filhos, netos e assim por diante. A seed tem
     * profundidade 2 abaixo do local, mas a função não depende disso.
     *
     * @param  list<int>  $raizes
     * @return list<int>
     */
    private function descendentes(array $raizes): array
    {
        $todos = $raizes;
        $fronteira = $raizes;

        for ($nivel = 0; $nivel < 10 && $fronteira !== []; $nivel++) {
            $filhos = $this->ids(
                No::query()->whereIn('pai_id', $fronteira)->whereNull('arquivado_em')
            );
            $novos = array_values(array_diff($filhos, $todos));

            if ($novos === []) {
                break;
            }

            $todos = $this->juntar($todos, $novos);
            $fronteira = $novos;
        }

        return $todos;
    }
}
