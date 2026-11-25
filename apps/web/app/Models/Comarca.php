<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $chave
 * @property string $nome
 * @property string $codigo_ibge
 * @property float|null $ordem_num
 * @property int $ordem_idx
 * @property \Illuminate\Support\Carbon|null $arquivado_em
 */
class Comarca extends Model
{
    /** @use HasFactory<\Database\Factories\ComarcaFactory> */
    use HasFactory;

    protected $table = 'comarcas';

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

    /** @return HasMany<Local, $this> */
    public function locais(): HasMany
    {
        return $this->hasMany(Local::class);
    }
}
