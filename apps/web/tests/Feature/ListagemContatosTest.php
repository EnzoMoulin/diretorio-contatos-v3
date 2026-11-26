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
        ->assertSee('Cartório')
        ->assertSee('Fórum Cível');
});

it('recorta por comarca e esconde o que está fora', function () {
    $f = contatos_fixture();

    $this->get('/telefones?comarca='.$f['comarca']->chave)
        ->assertOk()
        ->assertSee('(27) 3333-0001')
        ->assertDontSee('(27) 3333-0003');
});

it('mostra os e-mails com o link de correio', function () {
    $f = contatos_fixture();

    Contato::factory()->create([
        'local_id' => $f['forum']->id,
        'tipo' => 'E-mail',
        'valor' => 'cartorio@exemplo.jus.br',
        'descricao' => 'Cartório',
        'ordem_num' => 1,
        'ordem_idx' => 10,
    ]);

    $this->get('/emails')
        ->assertOk()
        ->assertSee('cartorio@exemplo.jus.br')
        ->assertSee('mailto:cartorio@exemplo.jus.br', false);
});

it('mostra o WhatsApp e não linka o que está mascarado', function () {
    $f = contatos_fixture();

    Contato::factory()->create([
        'local_id' => $f['forum']->id,
        'tipo' => 'WhatsApp',
        'valor' => '(27) 9XXXX-XXXX',
        'descricao' => 'Gabinete',
        'ordem_num' => 1,
        'ordem_idx' => 11,
    ]);

    $this->get('/whatsapp')
        ->assertOk()
        ->assertSee('(27) 9XXXX-XXXX')
        ->assertDontSee('wa.me', false);
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
