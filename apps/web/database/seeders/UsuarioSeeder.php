<?php

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) config('diretorio.admin.email');
        $senha = (string) config('diretorio.admin.senha');

        if ($senha === '') {
            $senha = Str::password(20);
            $this->command?->warn("Senha gerada para {$email}: {$senha}");
        }

        $usuario = Usuario::firstOrNew(['email' => $email]);

        if (! $usuario->exists) {
            $usuario->nome = 'Administrador';
            $usuario->senha = $senha;
        }

        // `papel` e `ativo` não são fillable; o seeder escreve direto, como as
        // Actions de administração.
        $usuario->papel = 'ADMIN';
        $usuario->ativo = true;
        $usuario->save();
    }
}
