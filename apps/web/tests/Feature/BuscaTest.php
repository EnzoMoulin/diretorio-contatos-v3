<?php

use App\Models\Comarca;
use App\Models\Contato;
use App\Models\Local;
use App\Models\No;
use App\Models\Sinonimo;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Estrutura mínima com uma palavra canônica de cada família: cartório (setor),
 * família (vara) e sucessões (setor).
 */
function busca_fixture(): void
{
    $vitoria = Comarca::factory()->create(['nome' => 'Vitória']);
    $forum = Local::factory()->create(['nome' => 'Fórum Cível', 'comarca_id' => $vitoria->id]);

    foreach (['Cartório de Notas', 'Vara de Família', 'Órfãos e Sucessões'] as $i => $nome) {
        No::factory()->create(['local_id' => $forum->id, 'nome' => $nome, 'ordem_num' => $i + 1]);
    }

    Contato::factory()->create([
        'local_id' => $forum->id,
        'descricao' => 'Cartório',
        'valor' => '(27) 3333-0000',
    ]);
}

/** @return list<string> */
function busca_chaves(array $resposta): array
{
    return array_map(
        fn (array $linha): string => $linha['tipo'].':'.$linha['chave'],
        $resposta['resultados'],
    );
}

it('acha pelo vocabulário do cidadão o mesmo conjunto do termo canônico', function (string $termo, string $canonico) {
    busca_fixture();
    Sinonimo::query()->create(['termo' => $termo, 'expansao' => [$canonico]]);

    $porSinonimo = $this->getJson('/api/v1/busca?q='.urlencode($termo).'&limite=20')->assertOk()->json();
    $canonica = $this->getJson('/api/v1/busca?q='.urlencode($canonico).'&limite=20')->assertOk()->json();

    expect($porSinonimo['expansao'])->toBe([$canonico])
        ->and($porSinonimo['total'])->toBeGreaterThan(0)
        ->and(busca_chaves($porSinonimo))->toEqual(busca_chaves($canonica));
})->with([
    'reconhecimento de firma' => ['reconhecimento de firma', 'cartorio'],
    'pensão alimentícia' => ['pensao alimenticia', 'familia'],
    'herança' => ['heranca', 'sucessoes'],
]);

it('sem sinônimo cadastrado o vocabulário do cidadão não acha nada', function () {
    busca_fixture();

    $this->getJson('/api/v1/busca?q=reconhecimento+de+firma')
        ->assertOk()
        ->assertJsonPath('total', 0)
        ->assertJsonPath('resultados', []);

    $this->getJson('/api/v1/busca?q=cartorio')->assertOk()->assertJsonPath('total', 2);
});

it('exige todas as palavras da mesma frase', function () {
    busca_fixture();

    $resposta = $this->getJson('/api/v1/busca?q=cartorio+notas')->assertOk()->json();

    expect($resposta['total'])->toBe(1)
        ->and($resposta['resultados'][0]['rotulo'])->toBe('Cartório de Notas')
        ->and($resposta['resultados'][0]['contexto'])->toBe('Fórum Cível · Vitória');
});

it('ignora acento e caixa como o resto da busca', function () {
    busca_fixture();

    $comAcento = $this->getJson('/api/v1/busca?q='.urlencode('CARTÓRIO'))->assertOk()->json();
    $semAcento = $this->getJson('/api/v1/busca?q=cartorio')->assertOk()->json();

    expect($comAcento['total'])->toBeGreaterThan(0)
        ->and(busca_chaves($comAcento))->toEqual(busca_chaves($semAcento));
});

it('corta no limite pedido e informa o total antes do corte', function () {
    $sede = Local::factory()->sede()->create();

    foreach (range(1, 5) as $i) {
        No::factory()->create(['local_id' => $sede->id, 'nome' => 'Unidade cadeia '.$i]);
    }

    $resposta = $this->getJson('/api/v1/busca?q=unidade&limite=2')->assertOk()->json();

    expect($resposta['total'])->toBe(5)
        ->and($resposta['resultados'])->toHaveCount(2);
});

it('não devolve mais que o teto de 20, mesmo pedindo 50', function () {
    $sede = Local::factory()->sede()->create();

    foreach (range(1, 21) as $i) {
        No::factory()->create(['local_id' => $sede->id, 'nome' => 'Unidade cadeia '.$i]);
    }

    $resposta = $this->getJson('/api/v1/busca?q=unidade&limite=50')->assertOk()->json();

    expect($resposta['total'])->toBe(21)
        ->and($resposta['resultados'])->toHaveCount(20);
});

it('consulta vazia devolve lista vazia, não o diretório inteiro', function () {
    busca_fixture();

    $this->getJson('/api/v1/busca?q=')
        ->assertOk()
        ->assertJsonPath('total', 0)
        ->assertJsonPath('resultados', []);
});
