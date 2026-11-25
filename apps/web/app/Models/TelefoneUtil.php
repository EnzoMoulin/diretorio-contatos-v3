<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $chave
 * @property string $nome
 * @property string $tipo
 * @property string $valor
 */
class TelefoneUtil extends Model
{
    /** @use HasFactory<\Database\Factories\TelefoneUtilFactory> */
    use HasFactory;

    protected $table = 'telefones_uteis';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'ordem_idx' => 'integer',
            'vigente_desde' => 'date',
            'criado_em' => 'datetime',
            'atualizado_em' => 'datetime',
        ];
    }
}
