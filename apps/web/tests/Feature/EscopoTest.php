<?php

use App\Actions\AlterarPapel;
use App\Actions\ConcederAcesso;
use App\Actions\CriarUsuario;
use App\Actions\DefinirAtivo;
use App\Actions\RevogarAcesso;
use App\Escopo\ResolvedorDeEscopo;
use App\Models\Auditoria;
use App\Models\Comarca;
use App\Models\Contato;
use App\Models\Local;
use App\Models\No;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * @return array<string, Comarca|Local|No|Contato>
 */
function fixtureEscopo(): array
{
    $sede = Local::factory()->sede()->create();
    $sedeNo = No::factory()->create(['chave' => 'UNI0100', 'local_id' => $sede->id]);
    $sedeContato = Contato::factory()->create([
        'chave' => 'C010000',
        'local_id' => $sede->id,
        'no_id' => $sedeNo->id,
    ]);

    $vitoria = Comarca::factory()->create(['chave' => 'MUN0001', 'nome' => 'Vitória']);
    $forum = Local::factory()->create(['chave' => 'FOR0001', 'comarca_id' => $vitoria->id]);
    $unidade = No::factory()->create(['chave' => 'UNI0001', 'local_id' => $forum->id]);
    $setor = No::factory()->create([
        'chave' => 'UNI0002',
        'local_id' => $forum->id,
        'pai_id' => $unidade->id,
    ]);
    $contatoLocal = Contato::factory()->create([
        'chave' => 'C000001',
        'local_id' => $forum->id,
        'no_id' => null,
    ]);
    $contatoUnidade = Contato::factory()->create([
        'chave' => 'C000002',
        'local_id' => $forum->id,
        'no_id' => $unidade->id,
    ]);
    $contatoSetor = Contato::factory()->create([
        'chave' => 'C000003',
        'local_id' => $forum->id,
        'no_id' => $setor->id,
    ]);

    $vilaVelha = Comarca::factory()->create(['chave' => 'MUN0002', 'nome' => 'Vila Velha']);
    $forumVila = Local::factory()->create(['chave' => 'FOR0002', 'comarca_id' => $vilaVelha->id]);
    $contatoVila = Contato::factory()->create([
        'chave' => 'C000004',
        'local_id' => $forumVila->id,
    ]);

    return [
        'sede' => $sede,
        'sedeNo' => $sedeNo,
        'sedeContato' => $sedeContato,
        'vitoria' => $vitoria,
        'forum' => $forum,
        'unidade' => $unidade,
        'setor' => $setor,
        'contatoLocal' => $contatoLocal,
        'contatoUnidade' => $contatoUnidade,
        'contatoSetor' => $contatoSetor,
        'vilaVelha' => $vilaVelha,
        'forumVila' => $forumVila,
        'contatoVila' => $contatoVila,
    ];
}

it('deixa o admin editar fora de escopo e o demo só ler', function () {
    $f = fixtureEscopo();
    $admin = Usuario::factory()->admin()->create();
    $demo = Usuario::factory()->demo()->create();

    expect(Gate::forUser($admin)->allows('editar', $f['contatoVila']))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('editar', $f['sede']))->toBeTrue();

    expect(Gate::forUser($demo)->allows('ver', $f['contatoVila']))->toBeTrue()
        ->and(Gate::forUser($demo)->allows('ver', $f['forumVila']))->toBeTrue()
        ->and(Gate::forUser($demo)->allows('editar', $f['contatoVila']))->toBeFalse()
        ->and(Gate::forUser($demo)->allows('editar', $f['sede']))->toBeFalse();
});

it('deixa o gestor de comarca editar dentro da comarca, e só nela', function () {
    $f = fixtureEscopo();
    $gestor = Usuario::factory()->gestor()->create();

    app(ConcederAcesso::class)->executar($gestor, 'comarca', 'MUN0001', $gestor);

    expect(Gate::forUser($gestor)->allows('editar', $f['vitoria']))->toBeTrue()
        ->and(Gate::forUser($gestor)->allows('editar', $f['forum']))->toBeTrue()
        ->and(Gate::forUser($gestor)->allows('editar', $f['unidade']))->toBeTrue()
        ->and(Gate::forUser($gestor)->allows('editar', $f['contatoSetor']))->toBeTrue()
        ->and(Gate::forUser($gestor)->allows('editar', $f['contatoLocal']))->toBeTrue()
        ->and(Gate::forUser($gestor)->allows('editar', $f['vilaVelha']))->toBeFalse()
        ->and(Gate::forUser($gestor)->allows('editar', $f['contatoVila']))->toBeFalse();
});

it('deixa o editor de local editar o local e seus nós, não o vizinho', function () {
    $f = fixtureEscopo();
    $editor = Usuario::factory()->create();

    app(ConcederAcesso::class)->executar($editor, 'local', 'FOR0001');

    expect(Gate::forUser($editor)->allows('editar', $f['forum']))->toBeTrue()
        ->and(Gate::forUser($editor)->allows('editar', $f['unidade']))->toBeTrue()
        ->and(Gate::forUser($editor)->allows('editar', $f['contatoLocal']))->toBeTrue()
        ->and(Gate::forUser($editor)->allows('editar', $f['contatoSetor']))->toBeTrue()
        ->and(Gate::forUser($editor)->allows('editar', $f['forumVila']))->toBeFalse()
        ->and(Gate::forUser($editor)->allows('ver', $f['contatoVila']))->toBeFalse();
});

it('faz o acesso de nó cobrir o nó e os filhos, sem subir para o local', function () {
    $f = fixtureEscopo();
    $editor = Usuario::factory()->create();

    app(ConcederAcesso::class)->executar($editor, 'no', 'UNI0001');

    expect(Gate::forUser($editor)->allows('editar', $f['unidade']))->toBeTrue()
        ->and(Gate::forUser($editor)->allows('editar', $f['setor']))->toBeTrue()
        ->and(Gate::forUser($editor)->allows('editar', $f['contatoUnidade']))->toBeTrue()
        ->and(Gate::forUser($editor)->allows('editar', $f['contatoSetor']))->toBeTrue()
        ->and(Gate::forUser($editor)->allows('editar', $f['forum']))->toBeFalse()
        ->and(Gate::forUser($editor)->allows('editar', $f['contatoLocal']))->toBeFalse();
});

it('aceita a Sede como local e recusa MUN0000 como comarca', function () {
    $f = fixtureEscopo();
    $editor = Usuario::factory()->create();

    app(ConcederAcesso::class)->executar($editor, 'local', 'MUN0000');

    expect(Gate::forUser($editor)->allows('editar', $f['sede']))->toBeTrue()
        ->and(Gate::forUser($editor)->allows('editar', $f['sedeNo']))->toBeTrue()
        ->and(Gate::forUser($editor)->allows('editar', $f['sedeContato']))->toBeTrue();

    expect(fn () => app(ConcederAcesso::class)->executar($editor, 'comarca', 'MUN0000'))
        ->toThrow(DomainException::class);
});

it('recusa chave órfã ou de outro nível', function () {
    $editor = Usuario::factory()->create();

    expect(fn () => app(ConcederAcesso::class)->executar($editor, 'local', 'FOR9999'))
        ->toThrow(DomainException::class);

    expect(fn () => app(ConcederAcesso::class)->executar($editor, 'no', 'UNI9999'))
        ->toThrow(DomainException::class);

    expect(fn () => app(ConcederAcesso::class)->executar($editor, 'comarca', 'FOR0001'))
        ->toThrow(DomainException::class);
});

it('não deixa usuário inativo editar nem ver', function () {
    $f = fixtureEscopo();
    $inativo = Usuario::factory()->inativo()->create();

    app(ConcederAcesso::class)->executar($inativo, 'comarca', 'MUN0001');

    expect(Gate::forUser($inativo)->allows('editar', $f['forum']))->toBeFalse()
        ->and(Gate::forUser($inativo)->allows('ver', $f['forum']))->toBeFalse();
});

it('resolve o escopo uma vez por requisição', function () {
    $editor = Usuario::factory()->create();
    app(ConcederAcesso::class)->executar($editor, 'local', 'FOR0001');

    DB::flushQueryLog();
    DB::enableQueryLog();

    $resolvedor = app(ResolvedorDeEscopo::class);
    $resolvedor->para($editor);
    $depoisDaPrimeira = count(DB::getQueryLog());

    $resolvedor->para($editor);
    $depoisDaSegunda = count(DB::getQueryLog());

    DB::disableQueryLog();

    expect($depoisDaPrimeira)->toBeLessThanOrEqual(6)
        ->and($depoisDaSegunda)->toBe($depoisDaPrimeira);
});

it('aplica o mesmo escopo na consulta de contatos', function () {
    $f = fixtureEscopo();
    $editor = Usuario::factory()->create();
    $admin = Usuario::factory()->admin()->create();
    $demo = Usuario::factory()->demo()->create();

    app(ConcederAcesso::class)->executar($editor, 'local', 'FOR0001');

    $resolvedor = app(ResolvedorDeEscopo::class);
    $escopoEditor = $resolvedor->para($editor);
    $escopoAdmin = $resolvedor->para($admin);
    $escopoDemo = $resolvedor->para($demo);

    expect(Contato::query()->editaveisPor($escopoEditor)->pluck('chave')->all())
        ->toEqualCanonicalizing(['C000001', 'C000002', 'C000003'])
        ->and(Contato::query()->editaveisPor($escopoAdmin)->count())->toBe(5)
        ->and(Contato::query()->editaveisPor($escopoDemo)->count())->toBe(0)
        ->and(Contato::query()->visiveisPor($escopoDemo)->count())->toBe(5)
        ->and(Contato::query()->editaveisPor($escopoEditor)->whereKey($f['contatoVila']->id)->exists())->toBeFalse();
});

it('barra papel e ativo no mass assignment', function () {
    $editor = Usuario::factory()->create();

    expect(fn () => $editor->fill(['papel' => 'ADMIN']))
        ->toThrow(MassAssignmentException::class);

    expect(fn () => $editor->fill(['ativo' => false]))
        ->toThrow(MassAssignmentException::class);

    expect($editor->fresh()->papel)->toBe('EDITOR');
});

it('cria usuário, troca papel e desativa com auditoria', function () {
    $admin = Usuario::factory()->admin()->create();

    $usuario = app(CriarUsuario::class)->executar(
        'Nova Pessoa',
        'nova@example.com',
        'segredo',
        'EDITOR',
        $admin,
    );

    app(AlterarPapel::class)->executar($usuario, 'GESTOR_COMARCA', $admin);
    app(DefinirAtivo::class)->executar($usuario, false, $admin);

    $usuario->refresh();

    expect($usuario->papel)->toBe('GESTOR_COMARCA')
        ->and($usuario->ativo)->toBeFalse()
        ->and(Auditoria::query()->where('entidade', 'usuario')->count())->toBe(3);

    $troca = Auditoria::query()->where('acao', 'usuario.papel_alterado')->firstOrFail();

    expect($troca->antes)->toBe(['papel' => 'EDITOR'])
        ->and($troca->depois)->toBe(['papel' => 'GESTOR_COMARCA'])
        ->and($troca->ator_id)->toBe($admin->id);
});

it('revoga acesso e deixa o retrato na auditoria', function () {
    $admin = Usuario::factory()->admin()->create();
    $editor = Usuario::factory()->create();

    app(ConcederAcesso::class)->executar($editor, 'local', 'FOR0001', $admin);

    expect(app(RevogarAcesso::class)->executar($editor, 'local', 'FOR0001', $admin))->toBeTrue()
        ->and(app(RevogarAcesso::class)->executar($editor, 'local', 'FOR0001', $admin))->toBeFalse()
        ->and(Auditoria::query()->where('acao', 'acesso.revogado')->count())->toBe(1);

    $revogacao = Auditoria::query()->where('acao', 'acesso.revogado')->firstOrFail();

    expect($revogacao->antes)->toMatchArray([
        'usuario_id' => $editor->id,
        'nivel' => 'local',
        'alvo_chave' => 'FOR0001',
    ])->and($revogacao->depois)->toBeNull();
});
