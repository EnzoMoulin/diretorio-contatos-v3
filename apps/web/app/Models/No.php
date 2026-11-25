<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $chave
 * @property int $local_id
 * @property int|null $pai_id
 * @property string $tipo
 * @property string $nome
 * @property bool $selecionavel
 * @property bool $virtual
 * @property \Illuminate\Support\Carbon|null $arquivado_em
 */
class No extends Model
{
    /** @use HasFactory<\Database\Factories\NoFactory> */
    use HasFactory;

    protected $table = 'nos';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'selecionavel' => 'boolean',
            'virtual' => 'boolean',
            'ordem_num' => 'float',
            'ordem_idx' => 'integer',
            'arquivado_em' => 'datetime',
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
    public function pai(): BelongsTo
    {
        return $this->belongsTo(self::class, 'pai_id');
    }

    /** @return HasMany<No, $this> */
    public function filhos(): HasMany
    {
        return $this->hasMany(self::class, 'pai_id');
    }

    /** @return HasMany<Contato, $this> */
    public function contatos(): HasMany
    {
        return $this->hasMany(Contato::class);
    }
}
