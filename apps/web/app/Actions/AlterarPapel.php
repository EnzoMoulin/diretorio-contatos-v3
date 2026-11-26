<?php

namespace App\Actions;

use App\Models\Auditoria;
use App\Models\Usuario;
use DomainException;

final class AlterarPapel
{
    public function executar(Usuario $usuario, string $papel, Usuario $ator): Usuario
    {
        $papel = strtoupper(trim($papel));

        if (! in_array($papel, Usuario::PAPEIS, true)) {
            throw new DomainException("Papel inválido: {$papel}.");
        }

        $anterior = $usuario->papel;

        if ($anterior === $papel) {
            return $usuario;
        }

        $usuario->papel = $papel;
        $usuario->save();

        Auditoria::registrar(
            $ator->id,
            'usuario.papel_alterado',
            'usuario',
            $usuario->email,
            ['papel' => $anterior],
            ['papel' => $papel],
        );

        return $usuario;
    }
}
