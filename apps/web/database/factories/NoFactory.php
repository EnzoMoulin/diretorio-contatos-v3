<?php

namespace Database\Factories;

use App\Models\Local;
use App\Models\No;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<No> */
class NoFactory extends Factory
{
    protected $model = No::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'chave' => sprintf('UNI%04d', fake()->unique()->numberBetween(1, 9999)),
            'origem' => 'seed',
            'local_id' => Local::factory(),
            'pai_id' => null,
            'tipo' => 'UNIDADE',
            'nome' => 'Unidade '.fake()->unique()->word(),
            'endereco' => '',
            'cep' => '',
            'observacao' => '',
            'selecionavel' => true,
            'virtual' => false,
            'ordem_num' => 1,
            'ordem_idx' => 0,
        ];
    }
}
