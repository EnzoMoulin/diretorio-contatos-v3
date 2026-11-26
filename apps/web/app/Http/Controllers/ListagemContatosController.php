<?php

namespace App\Http\Controllers;

use App\Actions\ListarContatos;
use App\Models\Comarca;
use App\Models\Local;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ListagemContatosController extends Controller
{
    /**
     * Um canal por rota. O rótulo do `tipo` é o do banco; o resto é o texto da
     * tela — a listagem é a mesma, o eixo muda.
     *
     * @var array<string, array{tipo: string, singular: string, plural: string}>
     */
    private const CANAIS = [
        'telefones' => ['tipo' => 'Telefone', 'singular' => 'telefone', 'plural' => 'telefones'],
        'whatsapp' => ['tipo' => 'WhatsApp', 'singular' => 'WhatsApp', 'plural' => 'WhatsApp'],
        'emails' => ['tipo' => 'E-mail', 'singular' => 'e-mail', 'plural' => 'e-mails'],
    ];

    public function __invoke(Request $request, ListarContatos $acao, string $canal): View
    {
        abort_unless(isset(self::CANAIS[$canal]), 404);

        $dados = $request->validate([
            'comarca' => ['nullable', 'string', 'max:8'],
            'local' => ['nullable', 'string', 'max:8'],
            'q' => ['nullable', 'string', 'max:80'],
            'pagina' => ['nullable', 'integer', 'min:1'],
        ]);

        $texto = static fn (mixed $valor): ?string => is_string($valor) && trim($valor) !== '' ? trim($valor) : null;

        $lista = $acao->executar(
            tipo: self::CANAIS[$canal]['tipo'],
            comarca: $texto($dados['comarca'] ?? null),
            local: $texto($dados['local'] ?? null),
            q: $texto($dados['q'] ?? null) ?? '',
            pagina: (int) ($dados['pagina'] ?? 1),
            porPagina: 100,
        );

        return view('lista-contatos', [
            'canal' => $canal,
            'rotulo' => self::CANAIS[$canal],
            'lista' => $lista,
            'comarcas' => $this->comarcas(),
            'locais' => $this->locais(),
            'canais' => $this->canais($lista['totais'], $canal),
        ]);
    }

    /**
     * @return list<array{chave: string, nome: string}>
     */
    private function comarcas(): array
    {
        return Comarca::query()
            ->orderBy('ordem_num')
            ->orderBy('ordem_idx')
            ->get(['chave', 'nome'])
            ->map(static fn ($c): array => ['chave' => (string) $c->chave, 'nome' => (string) $c->nome])
            ->all();
    }

    /**
     * Fóruns agrupados pela comarca, com a Sede no começo: é a ordem em que o
     * catálogo lê, e o `optgroup` deixa a lista de 73 locais navegável.
     *
     * @return array<string, list<array{chave: string, nome: string}>>
     */
    private function locais(): array
    {
        $linhas = Local::query()
            ->leftJoin('comarcas', 'comarcas.id', '=', 'locais.comarca_id')
            ->orderBy('locais.ordem_num')
            ->orderBy('locais.ordem_idx')
            ->get([
                'locais.chave as chave',
                'locais.nome as nome',
                'comarcas.nome as comarca_nome',
            ]);

        $grupos = [];

        foreach ($linhas as $linha) {
            $grupo = $linha->comarca_nome === null ? 'Sede' : (string) $linha->comarca_nome;
            $grupos[$grupo][] = ['chave' => (string) $linha->chave, 'nome' => (string) $linha->nome];
        }

        return $grupos;
    }

    /**
     * @param  array<string, int>  $totais
     * @return list<array{rota: string, rotulo: string, total: int|null, atual: bool}>
     */
    private function canais(array $totais, string $canal): array
    {
        return [
            ['rota' => 'telefones', 'rotulo' => 'Telefones', 'total' => $totais['Telefone'] ?? 0, 'atual' => $canal === 'telefones'],
            ['rota' => 'whatsapp', 'rotulo' => 'WhatsApp', 'total' => $totais['WhatsApp'] ?? 0, 'atual' => $canal === 'whatsapp'],
            ['rota' => 'emails', 'rotulo' => 'E-mails', 'total' => $totais['E-mail'] ?? 0, 'atual' => $canal === 'emails'],
            ['rota' => 'ramais', 'rotulo' => 'Ramais', 'total' => null, 'atual' => false],
        ];
    }
}
