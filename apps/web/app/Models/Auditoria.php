<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rastro das tabelas do app (usuario, acesso, pedido), separado do `historico`
 * do dado e fora do expurgo de 180 dias. `antes`/`depois` são retratos JSONB.
 *
 * @property int $id
 * @property int|null $ator_id
 * @property string $acao
 * @property string $entidade
 * @property string $entidade_chave
 * @property array<string, mixed>|null $antes
 * @property array<string, mixed>|null $depois
 * @property \Illuminate\Support\Carbon $criado_em
 */
class Auditoria extends Model
{
    protected $table = 'auditoria';

    public $timestamps = false;

    protected $fillable = [
        'ator_id',
        'acao',
        'entidade',
        'entidade_chave',
        'antes',
        'depois',
    ];

    protected function casts(): array
    {
        return [
            'antes' => 'array',
            'depois' => 'array',
            'criado_em' => 'datetime',
        ];
    }

    /** @return BelongsTo<Usuario, $this> */
    public function ator(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'ator_id');
    }

    /**
     * @param  array<string, mixed>|null  $antes
     * @param  array<string, mixed>|null  $depois
     */
    public static function registrar(
        ?int $atorId,
        string $acao,
        string $entidade,
        string $chave = '',
        ?array $antes = null,
        ?array $depois = null,
    ): self {
        return self::create([
            'ator_id' => $atorId,
            'acao' => $acao,
            'entidade' => $entidade,
            'entidade_chave' => $chave,
            'antes' => $antes,
            'depois' => $depois,
        ]);
    }
}
