<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $usuario_id
 * @property string $nivel
 * @property string $alvo_chave
 * @property int|null $concedido_por
 * @property \Illuminate\Support\Carbon $criado_em
 */
class Acesso extends Model
{
    public const NIVEIS = ['comarca', 'local', 'no'];

    protected $table = 'acessos';

    public $timestamps = false;

    protected $fillable = [
        'usuario_id',
        'nivel',
        'alvo_chave',
        'concedido_por',
    ];

    protected function casts(): array
    {
        return [
            'criado_em' => 'datetime',
        ];
    }

    /** @return BelongsTo<Usuario, $this> */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    /** @return BelongsTo<Usuario, $this> */
    public function concedidoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'concedido_por');
    }
}
