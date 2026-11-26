<?php

namespace App\Actions;

use App\Models\Auditoria;
use App\Models\Usuario;
use DomainException;

/**
 * Criar usuário é Action porque `papel` e `ativo` não são fillable. A senha
 * entra em texto e é hasheada pelo cast do model.
 */
final class CriarUsuario
{
    public function executar(
        string $nome,
        string $email,
        string $senha,
        string $papel = 'EDITOR',
        ?Usuario $ator = null,
    ): Usuario {
        $papel = strtoupper(trim($papel));

        if (! in_array($papel, Usuario::PAPEIS, true)) {
            throw new DomainException("Papel inválido: {$papel}.");
        }

        $usuario = new Usuario();
        $usuario->fill([
            'nome' => $nome,
            'email' => $email,
            'senha' => $senha,
        ]);
        $usuario->papel = $papel;
        $usuario->ativo = true;
        $usuario->save();

        Auditoria::registrar(
            $ator?->id,
            'usuario.criado',
            'usuario',
            $usuario->email,
            null,
            [
                'nome' => $usuario->nome,
                'email' => $usuario->email,
                'papel' => $usuario->papel,
                'ativo' => $usuario->ativo,
            ],
        );

        return $usuario;
    }
}
