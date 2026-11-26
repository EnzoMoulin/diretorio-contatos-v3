<?php

namespace App\Actions;

use App\Models\Acesso;
use App\Models\Auditoria;
use App\Models\Usuario;
use DomainException;

/**
 * Revogar apaga a linha de `acessos`, mas deixa o retrato em `auditoria`.
 * Revogar chave já revogada (ou órfã) devolve `false`, sem exceção.
 */
final class RevogarAcesso
{
    public function executar(Usuario $usuario, string $nivel, string $chave, ?Usuario $ator = null): bool
    {
        $nivel = strtolower(trim($nivel));
        $chave = trim($chave);

        if (! in_array($nivel, Acesso::NIVEIS, true)) {
            throw new DomainException("Nível de acesso inválido: {$nivel}.");
        }

        $acesso = Acesso::query()
            ->where('usuario_id', $usuario->id)
            ->where('nivel', $nivel)
            ->where('alvo_chave', $chave)
            ->first();

        if ($acesso === null) {
            return false;
        }

        Auditoria::registrar(
            $ator?->id,
            'acesso.revogado',
            'acesso',
            "{$nivel}:{$chave}",
            [
                'usuario_id' => $usuario->id,
                'nivel' => $nivel,
                'alvo_chave' => $chave,
                'concedido_por' => $acesso->concedido_por,
            ],
            null,
        );

        $acesso->delete();

        return true;
    }
}
