<?php

namespace App\Models;

use Database\Factories\UsuarioFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    public const PAPEIS = ['ADMIN', 'GESTOR_COMARCA', 'EDITOR', 'DEMO'];

    public const CREATED_AT = 'criado_em';

    public const UPDATED_AT = 'atualizado_em';

    protected $table = 'usuarios';

    /**
     * `papel` e `ativo` ficam fora do fillable de propósito: só Actions
     * (`CriarUsuario`, `AlterarPapel`, `DefinirAtivo`) os alteram, para que
     * nenhum request consiga escalar privilégio por mass assignment.
     */
    protected $fillable = [
        'nome',
        'email',
        'senha',
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

    public function ehAdmin(): bool
    {
        return $this->papel === 'ADMIN';
    }

    public function ehDemo(): bool
    {
        return $this->papel === 'DEMO';
    }

    /** @return HasMany<Acesso, $this> */
    public function acessos(): HasMany
    {
        return $this->hasMany(Acesso::class);
    }

    /** @return HasMany<Pedido, $this> */
    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class);
    }
}
