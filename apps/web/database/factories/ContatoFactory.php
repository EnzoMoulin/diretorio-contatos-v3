<?php

namespace Database\Factories;

use App\Models\Contato;
use App\Models\Local;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Contato> */
class ContatoFactory extends Factory
{
    protected $model = Contato::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'chave' => sprintf('C%06d', fake()->unique()->numberBetween(1, 999999)),
            'local_id' => Local::factory(),
            'no_id' => null,
            'tipo' => 'Telefone',
            'descricao' => 'Geral',
            'valor' => '(27) 3333-4444',
            'ordem_num' => 1,
            'ordem_idx' => 0,
            'fonte' => 'teste',
        ];
    }
}
