<?php

namespace App\Actions;

use App\Models\Acesso;
use App\Models\Auditoria;
use App\Models\Comarca;
use App\Models\Local;
use App\Models\No;
use App\Models\Usuario;
use DomainException;

/**
 * Conceder acesso é a única porta para `acessos`. A chave é validada contra a
 * tabela do nível: `MUN0000` passa como local (a Sede), não como comarca, e
 * chave órfã ou bem formada que não existe falha fechado.
 */
final class ConcederAcesso
{
    public function executar(Usuario $usuario, string $nivel, string $chave, ?Usuario $ator = null): Acesso
    {
        $nivel = strtolower(trim($nivel));
        $chave = trim($chave);

        if (! in_array($nivel, Acesso::NIVEIS, true)) {
            throw new DomainException("Nível de acesso inválido: {$nivel}.");
        }

        if (! $this->existe($nivel, $chave)) {
            throw new DomainException("A chave {$chave} não existe no nível {$nivel}.");
        }

        $acesso = Acesso::firstOrNew([
            'usuario_id' => $usuario->id,
            'nivel' => $nivel,
            'alvo_chave' => $chave,
        ]);

        $acesso->concedido_por = $ator?->id;

        if ($acesso->exists && ! $acesso->isDirty()) {
            return $acesso;
        }

        $acesso->save();

        Auditoria::registrar(
            $ator?->id,
            'acesso.concedido',
            'acesso',
            "{$nivel}:{$chave}",
            null,
            [
                'usuario_id' => $usuario->id,
                'nivel' => $nivel,
                'alvo_chave' => $chave,
                'concedido_por' => $ator?->id,
            ],
        );

        return $acesso;
    }

    private function existe(string $nivel, string $chave): bool
    {
        return match ($nivel) {
            'comarca' => Comarca::query()->where('chave', $chave)->whereNull('arquivado_em')->exists(),
            'local' => Local::query()->where('chave', $chave)->whereNull('arquivado_em')->exists(),
            'no' => No::query()->where('chave', $chave)->whereNull('arquivado_em')->exists(),
            default => false,
        };
    }
}
