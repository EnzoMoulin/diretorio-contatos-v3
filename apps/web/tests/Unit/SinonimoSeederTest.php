<?php

use Database\Seeders\SinonimoSeeder;
use Illuminate\Support\Str;

/**
 * O banco tem `CHECK (termo = norm_texto(termo))`: um termo com acento ou maiúscula
 * derruba o `migrate --seed`, e derruba o CI — mas só depois do deploy do schema.
 * Este teste pega antes, na máquina de quem edita o léxico.
 */
it('mantém o léxico normalizado, sem termo repetido e com expansão preenchida', function () {
    $normalizar = fn (string $texto): string => trim(
        preg_replace('/[^a-z0-9]+/', ' ', Str::lower(Str::ascii($texto))) ?? '',
    );

    expect(SinonimoSeeder::LEXICO)->not->toBeEmpty();

    foreach (SinonimoSeeder::LEXICO as $termo => $expansao) {
        expect($termo)->toBe($normalizar($termo))
            ->and($expansao)->not->toBeEmpty();

        foreach ($expansao as $frase) {
            expect($frase)->toBe($normalizar($frase))
                ->and($frase)->not->toBe('');
        }
    }
});
