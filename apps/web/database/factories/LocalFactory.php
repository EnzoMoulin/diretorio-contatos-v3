<?php

namespace Database\Factories;

use App\Models\Comarca;
use App\Models\Local;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Local> */
class LocalFactory extends Factory
{
    protected $model = Local::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'chave' => sprintf('FOR%04d', fake()->unique()->numberBetween(1, 9999)),
            'tipo' => 'FORUM',
            'comarca_id' => Comarca::factory(),
            'nome' => 'Fórum '.fake()->unique()->lastName(),
            'endereco' => fake()->streetAddress(),
            'cep' => '29000-000',
            'email' => '',
            'observacao' => '',
            'ordem_num' => 1,
            'ordem_idx' => 0,
        ];
    }

    public function sede(): static
    {
        return $this->state(fn (): array => [
            'chave' => 'MUN0000',
            'tipo' => 'SEDE',
            'comarca_id' => null,
            'nome' => 'Sede Administrativa',
        ]);
    }
}
