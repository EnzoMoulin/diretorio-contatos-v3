<?php

namespace Database\Factories;

use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Usuario> */
class UsuarioFactory extends Factory
{
    protected $model = Usuario::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'nome' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'senha' => 'password',
        ];
    }

    /**
     * `papel` e `ativo` não são fillable; a factory os define depois do make,
     * antes do save, para não depender de mass assignment.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Usuario $usuario): void {
            $usuario->papel ??= 'EDITOR';
            $usuario->ativo ??= true;
        });
    }

    public function admin(): static
    {
        return $this->afterMaking(function (Usuario $usuario): void {
            $usuario->papel = 'ADMIN';
        });
    }

    public function gestor(): static
    {
        return $this->afterMaking(function (Usuario $usuario): void {
            $usuario->papel = 'GESTOR_COMARCA';
        });
    }

    public function demo(): static
    {
        return $this->afterMaking(function (Usuario $usuario): void {
            $usuario->papel = 'DEMO';
        });
    }

    public function inativo(): static
    {
        return $this->afterMaking(function (Usuario $usuario): void {
            $usuario->ativo = false;
        });
    }
}
