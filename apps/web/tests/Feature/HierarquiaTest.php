<?php

use App\Models\Comarca;
use App\Models\Local;
use App\Models\No;
use App\Models\TelefoneUtil;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('devolve a hierarquia no formato do contrato do v2', function () {
    Local::factory()->sede()->create(['nome' => 'Sede Administrativa', 'ordem_num' => 1]);
    $vitoria = Comarca::factory()->create(['nome' => 'Vitória', 'ordem_num' => 2]);
    $vilaVelha = Comarca::factory()->create(['nome' => 'Vila Velha', 'ordem_num' => 3]);

    $forumCivel = Local::factory()->create(['nome' => 'Fórum Cível', 'comarca_id' => $vitoria->id]);
    $forumCriminal = Local::factory()->create(['nome' => 'Fórum Criminal', 'comarca_id' => $vilaVelha->id]);

    No::factory()->count(2)->create(['local_id' => $forumCivel->id]);
    No::factory()->create(['local_id' => $forumCriminal->id]);
    TelefoneUtil::factory()->count(2)->create();

    $resposta = $this->getJson('/api/v1/hierarquia')->assertOk();

    $resposta->assertExactJson([
        'foruns_total' => 2,
        'locais' => 3,
        'nos_total' => 3,
        'ordem' => ['Sede Administrativa', 'Vitória', 'Vila Velha'],
        'uteis' => 2,
    ]);

    $contrato = json_decode(
        file_get_contents(dirname(base_path(), 2).'/docs/paridade/esperado.json'),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );

    expect(array_keys($resposta->json()))
        ->toEqualCanonicalizing(array_keys($contrato['rotas']['hierarquia']));
});

it('mostra a capa com os totais do diretório', function () {
    Local::factory()->sede()->create();
    Comarca::factory()->create(['nome' => 'Vitória']);
    Local::factory()->create(['nome' => 'Fórum Cível']);
    TelefoneUtil::factory()->create();

    $this->get('/')
        ->assertOk()
        ->assertSee('Diretório de Contatos')
        ->assertSee('Vitória');
});
