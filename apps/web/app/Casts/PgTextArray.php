<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Coluna `TEXT[]` do Postgres lida como `list<string>`.
 *
 * O cast `array` do Eloquent grava JSON (`["a"]`), e o Postgres recusa JSON como
 * literal de array — por isso este cast: `{a,"b c"}` na entrada, lista na saída.
 *
 * @implements CastsAttributes<list<string>, list<string>>
 */
final class PgTextArray implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     * @return list<string>
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): array
    {
        if (! is_string($value)) {
            return [];
        }

        $interno = trim($value, '{}');

        if ($interno === '') {
            return [];
        }

        preg_match_all('/"((?:[^"\\\\]|\\\\.)*)"|([^,]+)/', $interno, $partes, PREG_SET_ORDER);

        $itens = [];

        foreach ($partes as $parte) {
            $itens[] = isset($parte[1]) && $parte[1] !== ''
                ? stripcslashes($parte[1])
                : $parte[2];
        }

        return $itens;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        $itens = array_map(
            fn (mixed $item): string => '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], (string) $item).'"',
            (array) $value,
        );

        return '{'.implode(',', $itens).'}';
    }
}
