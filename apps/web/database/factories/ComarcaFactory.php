<?php

namespace Database\Factories;

use App\Models\Comarca;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Comarca> */
class ComarcaFactory extends Factory
{
    protected $model = Comarca::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'chave' => sprintf('MUN%04d', fake()->unique()->numberBetween(1, 9999)),
            'nome' => fake()->unique()->city(),
            'codigo_ibge' => '',
            'ordem_num' => fake()->randomFloat(2, 1, 999),
            'ordem_idx' => 0,
        ];
    }
}
