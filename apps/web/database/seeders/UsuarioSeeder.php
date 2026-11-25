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

        Usuario::firstOrCreate(
            ['email' => $email],
            [
                'nome' => 'Administrador',
                'senha' => $senha,
                'papel' => 'ADMIN',
                'ativo' => true,
            ],
        );
    }
}
