<?php

namespace App\Models;

use Database\Factories\UsuarioFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property string $nome
 * @property string $email
 * @property string $senha
 * @property string $papel
 * @property bool $ativo
 */
class Usuario extends Authenticatable
{
    /** @use HasFactory<UsuarioFactory> */
    use HasFactory;

    use Notifiable;

    public const CREATED_AT = 'criado_em';

    public const UPDATED_AT = 'atualizado_em';

    protected $table = 'usuarios';

    protected $fillable = [
        'nome',
        'email',
        'senha',
        'papel',
        'ativo',
    ];

    protected $hidden = [
        'senha',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'senha' => 'hashed',
            'ativo' => 'boolean',
            'email_verificado_em' => 'datetime',
            'criado_em' => 'datetime',
            'atualizado_em' => 'datetime',
        ];
    }

    public function getAuthPassword(): string
    {
        return $this->senha;
    }

    public function getAuthPasswordName(): string
    {
        return 'senha';
    }
}
