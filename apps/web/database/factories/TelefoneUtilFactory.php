<?php

namespace Database\Factories;

use App\Models\TelefoneUtil;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TelefoneUtil> */
class TelefoneUtilFactory extends Factory
{
    protected $model = TelefoneUtil::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'chave' => sprintf('UTL%04d', fake()->unique()->numberBetween(1, 9999)),
            'nome' => fake()->unique()->words(3, true),
            'tipo' => 'Telefone',
            'valor' => '(27) 3232-XXXX',
            'ordem_idx' => 0,
        ];
    }
}
