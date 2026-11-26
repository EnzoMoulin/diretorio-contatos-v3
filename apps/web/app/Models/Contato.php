<?php

namespace App\Models;

use App\Escopo\EscopoResolvido;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $chave
 * @property int $local_id
 * @property int|null $no_id
 * @property string $tipo
 * @property string $descricao
 * @property string $valor
 * @property string $fonte
 * @property \Illuminate\Support\Carbon|null $vigente_desde
 */
class Contato extends Model
{
    /** @use HasFactory<\Database\Factories\ContatoFactory> */
    use HasFactory;

    protected $table = 'contatos';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'ordem_num' => 'float',
            'ordem_idx' => 'integer',
            'vigente_desde' => 'date',
            'criado_em' => 'datetime',
            'atualizado_em' => 'datetime',
        ];
    }

    /** @return BelongsTo<Local, $this> */
    public function local(): BelongsTo
    {
        return $this->belongsTo(Local::class);
    }

    /** @return BelongsTo<No, $this> */
    public function no(): BelongsTo
    {
        return $this->belongsTo(No::class);
    }

    /**
     * Contatos que o escopo pode editar. A Policy e a listagem usam a mesma
     * resolução — não há regra duplicada.
     *
     * @param  Builder<Contato>  $query
     * @return Builder<Contato>
     */
    public function scopeEditaveisPor(Builder $query, EscopoResolvido $escopo): Builder
    {
        $escopo->filtrarEditaveis($query);

        return $query;
    }

    /**
     * Contatos que o escopo pode ver (o demo vê tudo, inclusive sem escrever).
     *
     * @param  Builder<Contato>  $query
     * @return Builder<Contato>
     */
    public function scopeVisiveisPor(Builder $query, EscopoResolvido $escopo): Builder
    {
        $escopo->filtrarVisiveis($query);

        return $query;
    }
}
