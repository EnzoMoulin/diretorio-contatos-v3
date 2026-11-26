<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $nome
 * @property string $email
 * @property string $motivo
 * @property string $motivo_recusa
 * @property string $nivel
 * @property string|null $alvo_chave
 * @property int|null $usuario_id
 * @property string $status
 * @property int|null $decidido_por
 * @property \Illuminate\Support\Carbon|null $decidido_em
 */
class Pedido extends Model
{
    public const CREATED_AT = 'criado_em';

    public const UPDATED_AT = 'atualizado_em';

    protected $table = 'pedidos';

    protected $fillable = [
        'nome',
        'email',
        'motivo',
        'motivo_recusa',
        'nivel',
        'alvo_chave',
        'usuario_id',
        'status',
        'decidido_por',
        'decidido_em',
    ];

    protected function casts(): array
    {
        return [
            'decidido_em' => 'datetime',
            'criado_em' => 'datetime',
            'atualizado_em' => 'datetime',
        ];
    }

    /** @return BelongsTo<Usuario, $this> */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    /** @return BelongsTo<Usuario, $this> */
    public function decididoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'decidido_por');
    }
}
