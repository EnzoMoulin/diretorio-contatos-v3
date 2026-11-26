<?php

namespace App\Escopo;

use App\Models\Contato;
use Illuminate\Database\Eloquent\Builder;

/**
 * Resultado de uma leitura de `acessos`, já expandido de chaves para ids.
 * É calculado uma vez por requisição e consumido pela Policy e pelos escopos
 * de consulta — nunca por linha.
 */
final class EscopoResolvido
{
    /**
     * @param  list<int>  $comarcas
     * @param  list<int>  $locais
     * @param  list<int>  $nos
     */
    public function __construct(
        public readonly bool $tudo,
        public readonly bool $escreve,
        public readonly array $comarcas = [],
        public readonly array $locais = [],
        public readonly array $nos = [],
    ) {
    }

    public static function nada(): self
    {
        return new self(false, false);
    }

    public static function tudo(bool $escreve): self
    {
        return new self(true, $escreve);
    }

    public function vazio(): bool
    {
        return ! $this->tudo && $this->comarcas === [] && $this->locais === [] && $this->nos === [];
    }

    public function cobreComarca(int $id): bool
    {
        return $this->tudo || in_array($id, $this->comarcas, true);
    }

    public function cobreLocal(int $id): bool
    {
        return $this->tudo || in_array($id, $this->locais, true);
    }

    public function cobreNo(int $id): bool
    {
        return $this->tudo || in_array($id, $this->nos, true);
    }

    public function cobreContato(Contato $contato): bool
    {
        if ($this->tudo) {
            return true;
        }

        if ($contato->no_id !== null && in_array((int) $contato->no_id, $this->nos, true)) {
            return true;
        }

        return in_array((int) $contato->local_id, $this->locais, true);
    }

    /** @param  Builder<Contato>  $query */
    public function filtrarVisiveis(Builder $query): void
    {
        if ($this->tudo) {
            return;
        }

        $this->aplicar($query);
    }

    /** @param  Builder<Contato>  $query */
    public function filtrarEditaveis(Builder $query): void
    {
        if (! $this->escreve) {
            $query->whereRaw('1 = 0');

            return;
        }

        if ($this->tudo) {
            return;
        }

        $this->aplicar($query);
    }

    /** @param  Builder<Contato>  $query */
    private function aplicar(Builder $query): void
    {
        if ($this->vazio()) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function (Builder $q): void {
            $q->whereIn('local_id', $this->locais);

            if ($this->nos !== []) {
                $q->orWhereIn('no_id', $this->nos);
            }
        });
    }
}
