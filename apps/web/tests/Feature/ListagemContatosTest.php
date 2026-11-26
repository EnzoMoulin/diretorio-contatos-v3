<?php

use App\Models\Contato;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

// O fixture é o `contatos_fixture()` de ContatosTest.php — Vitória, Fórum
// Cível, 1ª Vara, Cartório e Sede. Aqui o que se prova é a tela: o recorte, o
// link do canal, a máscara e a navegação.

it('lista os telefones com o link de discagem', function () {
    contatos_fixture();

    $this->get('/telefones')
        ->assertOk()
        ->assertSee('Telefones')
        ->assertSee('(27) 3333-0003')
        ->assertSee('(27) 3333-0001')
        ->assertSee('(27) 3333-0002')
        ->assertSee('tel:2733330002', false)
        ->assertSee('1ª Vara Cível')
        ->assertSee('Fórum Cível');
});

it('recorta por comarca e esconde o que está fora', function () {
    $f = contatos_fixture();

    $this->get('/telefones?comarca='.$f['vitoria']->chave)
        ->assertOk()
        ->assertSee('(27) 3333-0001')
        ->assertDontSee('(27) 3333-0003');
});

it('mostra os e-mails com o link de correio', function () {
    contatos_fixture();

    $this->get('/emails')
        ->assertOk()
        ->assertSee('cartorio@exemplo.jus.br')
        ->assertSee('mailto:cartorio@exemplo.jus.br', false);
});

it('linka o WhatsApp e deixa o mascarado como texto', function () {
    $f = contatos_fixture();

    Contato::factory()->create([
        'local_id' => $f['forum']->id,
        'tipo' => 'WhatsApp',
        'valor' => '(27) 9XXXX-XXXX',
        'descricao' => 'Balcão',
        'ordem_num' => 1,
        'ordem_idx' => 11,
    ]);

    $resposta = $this->get('/whatsapp')->assertOk();

    $resposta->assertSee('(27) 99999-0001')
        ->assertSee('wa.me/5527999990001', false)
        ->assertSee('(27) 9XXXX-XXXX');

    // O mascarado aparece, mas não vira link: só o número do fixture tem href.
    expect(substr_count($resposta->getContent(), 'wa.me'))->toBe(1);
});

it('filtra pelo texto do contato', function () {
    contatos_fixture();

    $this->get('/telefones?q=gabinete')
        ->assertOk()
        ->assertSee('(27) 3333-0002')
        ->assertDontSee('(27) 3333-0003');
});

it('não inventa rota para canal que não existe', function () {
    $this->get('/fax')->assertNotFound();
});
