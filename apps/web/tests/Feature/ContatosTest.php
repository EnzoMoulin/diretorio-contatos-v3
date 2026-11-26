<?php

use App\Models\Comarca;
use App\Models\Contato;
use App\Models\Local;
use App\Models\No;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

/**
 * Um fórum com vara e cartório, e a Sede: os seis contatos cobrem os quatro
 * canais, o contato do local (sem nó), o da vara e o do setor — que é o recorte
 * que a listagem precisa distinguir.
 *
 * @return array{comarca: Comarca, forum: Local, vara: No, cartorio: No, sede: Local}
 */
function contatos_fixture(): array
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
        'nome' => '1ª Vara Cível',
        'ordem_num' => 1,
    ]);

    $cartorio = No::factory()->create([
        'local_id' => $forum->id,
        'tipo' => 'SETOR',
        'nome' => 'Cartório',
        'pai_id' => $vara->id,
        'ordem_num' => 1,
    ]);

    $sede = Local::factory()->sede()->create(['ordem_num' => 1]);

    Contato::factory()->create([
        'local_id' => $sede->id,
        'tipo' => 'Ramal',
        'valor' => 'XXXX',
        'descricao' => 'Presidência',
        'ordem_num' => 1,
        'ordem_idx' => 0,
    ]);

    Contato::factory()->create([
        'local_id' => $sede->id,
        'tipo' => 'Telefone',
        'valor' => '(27) 3333-0003',
        'descricao' => 'Atendimento',
        'ordem_num' => 2,
        'ordem_idx' => 1,
    ]);

    Contato::factory()->create([
        'local_id' => $forum->id,
        'tipo' => 'Telefone',
        'valor' => '(27) 3333-0001',
        'descricao' => 'Atendimento',
        'ordem_num' => 1,
        'ordem_idx' => 2,
    ]);

    Contato::factory()->create([
        'local_id' => $forum->id,
        'no_id' => $vara->id,
        'tipo' => 'Telefone',
        'valor' => '(27) 3333-0002',
        'descricao' => 'Gabinete',
        'ordem_num' => 1,
        'ordem_idx' => 3,
    ]);

    Contato::factory()->create([
        'local_id' => $forum->id,
        'no_id' => $vara->id,
        'tipo' => 'WhatsApp',
        'valor' => '(27) 99999-0001',
        'descricao' => 'Gabinete',
        'ordem_num' => 2,
        'ordem_idx' => 4,
    ]);

    Contato::factory()->create([
        'local_id' => $forum->id,
        'no_id' => $cartorio->id,
        'tipo' => 'E-mail',
        'valor' => 'cartorio@exemplo.jus.br',
        'descricao' => 'Cartório',
        'ordem_num' => 1,
        'ordem_idx' => 5,
    ]);

    return compact('vitoria', 'forum', 'vara', 'cartorio', 'sede');
}

it('devolve os contatos na ordem do seed, com a contagem por canal', function () {
    contatos_fixture();

    $resposta = $this->getJson('/api/v1/contatos')->assertOk()->json();

    expect($resposta['total'])->toBe(6)
        ->and($resposta['tipo'])->toBeNull()
        ->and($resposta['totais'])->toMatchArray([
            'Telefone' => 3,
            'WhatsApp' => 1,
            'Ramal' => 1,
            'E-mail' => 1,
            'Fax' => 0,
        ])
        ->and(array_column($resposta['itens'], 'chave'))->toHaveCount(6)
        // Sede (ordem 1) antes do fórum (ordem 2), e dentro do local vale o
        // `ordem_num`/`ordem_idx` do seed.
        ->and($resposta['itens'][0]['valor'])->toBe('XXXX')
        ->and($resposta['itens'][1]['valor'])->toBe('(27) 3333-0003');
});

it('filtra por tipo aceitando a forma normalizada', function (string $entrada, int $esperado) {
    contatos_fixture();

    $resposta = $this->getJson('/api/v1/contatos?tipo='.urlencode($entrada))->assertOk()->json();

    expect($resposta['total'])->toBe($esperado)
        ->and($resposta['tipo'])->not->toBeNull();
})->with([
    'minúsculo' => ['telefone', 3],
    'caixa alta' => ['TELEFONE', 3],
    'e-mail com hífen' => ['E-MAIL', 1],
    'e-mail sem hífen' => ['email', 1],
    'ramal' => ['Ramal', 1],
    'whatsapp' => ['whatsapp', 1],
]);

it('recusa tipo que não existe com 422', function () {
    contatos_fixture();

    $this->getJson('/api/v1/contatos?tipo=telegram')
        ->assertStatus(422)
        ->assertJsonValidationErrors('tipo');
});

it('filtra por comarca, local e nó', function () {
    $f = contatos_fixture();

    $porComarca = $this->getJson('/api/v1/contatos?comarca='.$f['vitoria']->chave)->assertOk()->json();
    $porSede = $this->getJson('/api/v1/contatos?local=MUN0000')->assertOk()->json();
    $porVara = $this->getJson('/api/v1/contatos?no='.$f['vara']->chave)->assertOk()->json();

    expect($porComarca['total'])->toBe(4)
        // O nó inclui os filhos: o cartório é o telefone que o balcão procura
        // pelo nome da vara.
        ->and($porVara['total'])->toBe(3)
        ->and($porSede['total'])->toBe(2)
        ->and($porSede['totais']['Telefone'])->toBe(1)
        ->and($porSede['itens'][0]['local'])->toBe('Sede Administrativa');
});

it('chave inexistente devolve lista vazia, não erro', function () {
    contatos_fixture();

    $resposta = $this->getJson('/api/v1/contatos?comarca=MUN9999')->assertOk()->json();

    expect($resposta['total'])->toBe(0)
        ->and($resposta['paginas'])->toBe(0)
        ->and($resposta['itens'])->toBe([])
        ->and($resposta['totais']['Telefone'])->toBe(0);
});

it('busca sem acento e por dígito', function () {
    contatos_fixture();

    // `cartorio` sem acento acha "Cartório": é o que o `LIKE` do v2 errava.
    $semAcento = $this->getJson('/api/v1/contatos?q=cartorio')->assertOk()->json();
    $porDigito = $this->getJson('/api/v1/contatos?q=33330002')->assertOk()->json();

    expect($semAcento['total'])->toBe(1)
        ->and($semAcento['itens'][0]['descricao'])->toBe('Cartório')
        ->and($porDigito['total'])->toBe(1)
        ->and($porDigito['itens'][0]['valor'])->toBe('(27) 3333-0002');
});

it('pagina a lista e mantém o total do recorte', function () {
    contatos_fixture();

    $primeira = $this->getJson('/api/v1/contatos?por_pagina=2&pagina=1')->assertOk()->json();
    $ultima = $this->getJson('/api/v1/contatos?por_pagina=2&pagina=3')->assertOk()->json();

    expect($primeira['total'])->toBe(6)
        ->and($primeira['paginas'])->toBe(3)
        ->and($primeira['itens'])->toHaveCount(2)
        ->and($ultima['itens'])->toHaveCount(2)
        ->and(array_column($primeira['itens'], 'chave'))
        ->not->toEqual(array_column($ultima['itens'], 'chave'));
});
