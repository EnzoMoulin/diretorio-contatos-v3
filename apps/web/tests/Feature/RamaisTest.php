<?php

use App\Models\Comarca;
use App\Models\Contato;
use App\Models\Local;
use App\Models\No;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

/**
 * Duas comarcas na árvore: um fórum com vara e cartório, e a Sede com uma
 * presidência. Os quatro ramais cobrem número completo, prefixo comum e o
 * valor mascarado do seed de demonstração.
 *
 * @return array{forum: Local, vara: No, cartorio: No, sede: Local, presidencia: No}
 */
function ramais_fixture(): array
{
    $vitoria = Comarca::factory()->create(['nome' => 'Vitória', 'ordem_num' => 2]);

    $forum = Local::factory()->create([
        'nome' => 'Fórum Cível',
        'comarca_id' => $vitoria->id,
        'ordem_num' => 2,
    ]);

    $vara = No::factory()->create([
        'local_id' => $forum->id,
        'tipo' => 'VARA',
        'nome' => 'Vara Cível',
        'pai_id' => null,
        'ordem_num' => 1,
    ]);

    $cartorio = No::factory()->create([
        'local_id' => $forum->id,
        'tipo' => 'SETOR',
        'nome' => 'Cartório',
        'pai_id' => $vara->id,
        'ordem_num' => 1,
    ]);

    $sede = Local::factory()->sede()->create(['nome' => 'Sede Administrativa', 'ordem_num' => 1]);

    $presidencia = No::factory()->create([
        'local_id' => $sede->id,
        'tipo' => 'UNIDADE',
        'nome' => 'Presidência',
        'pai_id' => null,
        'ordem_num' => 1,
    ]);

    $ramais = [
        ['local' => $forum, 'no' => $cartorio, 'valor' => '(27) 3333-0045', 'ordem' => 3],
        ['local' => $forum, 'no' => $cartorio, 'valor' => '(27) 3333-0012', 'ordem' => 2],
        ['local' => $forum, 'no' => $vara, 'valor' => '(27) 3333-0011', 'ordem' => 1],
        ['local' => $sede, 'no' => $presidencia, 'valor' => 'XXXX', 'ordem' => 1],
    ];

    foreach ($ramais as $i => $ramal) {
        Contato::factory()->create([
            'chave' => sprintf('C%06d', $i + 1),
            'local_id' => $ramal['local']->id,
            'no_id' => $ramal['no']->id,
            'tipo' => 'Ramal',
            'valor' => $ramal['valor'],
            'ordem_num' => $ramal['ordem'],
        ]);
    }

    return compact('forum', 'vara', 'cartorio', 'sede', 'presidencia');
}

it('lista os ramais em ordem numérica com comarca, unidade e setor', function () {
    ramais_fixture();

    $resposta = $this->get('/ramais')->assertOk();

    $resposta->assertSeeInOrder(['(27) 3333-0011', '(27) 3333-0012', '(27) 3333-0045'])
        ->assertSee('Vitória')
        ->assertSee('Fórum Cível')
        ->assertSee('Vara Cível')
        ->assertSee('Cartório')
        ->assertSee('Sede')
        ->assertSee('Presidência');

    // Uma linha por ramal, sem duplicar as linhas da tabela.
    expect(substr_count($resposta->getContent(), 'class="numero"'))->toBe(4);
});

it('acha por prefixo curto sem exigir o número inteiro', function () {
    ramais_fixture();

    $resposta = $this->get('/ramais?q=001')->assertOk();

    $resposta->assertSee('0011')
        ->assertSee('0012')
        ->assertDontSee('0045')
        ->assertSee('2 ramais para “001”');
});

it('devolve estado vazio para ramal inexistente', function () {
    ramais_fixture();

    $this->get('/ramais?q=9999')
        ->assertOk()
        ->assertSee('Nenhum ramal com esse número')
        ->assertDontSee('class="numero"', false);
});

it('pede um número quando a busca não tem dígito', function () {
    ramais_fixture();

    $this->get('/ramais?q=cartorio')
        ->assertOk()
        ->assertSee('Digite um número')
        ->assertDontSee('class="numero"', false);
});

it('mostra o valor mascarado e avisa que a base é de demonstração', function () {
    ramais_fixture();

    $this->get('/ramais')
        ->assertOk()
        ->assertSee('XXXX')
        ->assertSee('Base de demonstração')
        ->assertSee('1 de 4');
});

it('traz a folha de impressão em A4', function () {
    ramais_fixture();

    $this->get('/ramais')
        ->assertOk()
        ->assertSee('size: A4 portrait', false)
        ->assertSee('Imprimir');
});
