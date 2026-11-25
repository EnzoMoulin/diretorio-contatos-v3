<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $chave
 * @property string $tipo
 * @property int|null $comarca_id
 * @property string $nome
 * @property string $endereco
 * @property string $cep
 * @property string $email
 * @property float|null $ordem_num
 * @property int $ordem_idx
 * @property \Illuminate\Support\Carbon|null $arquivado_em
 */
class Local extends Model
{
    /** @use HasFactory<\Database\Factories\LocalFactory> */
    use HasFactory;

    protected $table = 'locais';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'ordem_num' => 'float',
            'ordem_idx' => 'integer',
            'arquivado_em' => 'datetime',
            'criado_em' => 'datetime',
            'atualizado_em' => 'datetime',
        ];
    }

    /** @param Builder<Local> $query */
    public function scopeAtivos(Builder $query): void
    {
        $query->whereNull('arquivado_em');
    }

    /** @return BelongsTo<Comarca, $this> */
    public function comarca(): BelongsTo
    {
        return $this->belongsTo(Comarca::class);
    }

    /** @return HasMany<No, $this> */
    public function nos(): HasMany
    {
        return $this->hasMany(No::class);
    }

    /** @return HasMany<Contato, $this> */
    public function contatos(): HasMany
    {
        return $this->hasMany(Contato::class);
    }
}
