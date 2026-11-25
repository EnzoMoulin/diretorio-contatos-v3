<?php

namespace App\Models;

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
}
