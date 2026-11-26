<?php

namespace App\Actions;

use App\Models\Auditoria;
use App\Models\Usuario;

final class DefinirAtivo
{
    public function executar(Usuario $usuario, bool $ativo, Usuario $ator): Usuario
    {
        $anterior = $usuario->ativo;

        if ($anterior === $ativo) {
            return $usuario;
        }

        $usuario->ativo = $ativo;
        $usuario->save();

        Auditoria::registrar(
            $ator->id,
            'usuario.ativo_alterado',
            'usuario',
            $usuario->email,
            ['ativo' => $anterior],
            ['ativo' => $ativo],
        );

        return $usuario;
    }
}
