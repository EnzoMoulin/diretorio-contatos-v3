<?php

namespace App\Http\Controllers;

use App\Actions\ListarContatos;
use App\Models\Comarca;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ListagemContatosController extends Controller
{
    /**
     * Um canal por rota. O rótulo do `tipo` é o do banco; o resto é o texto da
     * tela — a listagem é a mesma, o eixo muda. O nome da rota da folha fica
     * inteiro de propósito: concatenar `'canal.'.$canal` derruba as telas.
     *
     * @var array<string, array{tipo: string, singular: string, plural: string, folha: string}>
     */
    private const CANAIS = [
        'telefones' => ['tipo' => 'Telefone', 'singular' => 'telefone', 'plural' => 'telefones', 'folha' => 'canal.telefones.impressao'],
        'whatsapp' => ['tipo' => 'WhatsApp', 'singular' => 'WhatsApp', 'plural' => 'WhatsApp', 'folha' => 'canal.whatsapp.impressao'],
        'emails' => ['tipo' => 'E-mail', 'singular' => 'e-mail', 'plural' => 'e-mails', 'folha' => 'canal.emails.impressao'],
    ];

    public function __invoke(Request $request, ListarContatos $acao, string $canal): View
    {
        abort_unless(isset(self::CANAIS[$canal]), 404);

        $filtros = $this->filtros($request);

        $lista = $acao->executar(
            tipo: self::CANAIS[$canal]['tipo'],
            comarca: $filtros['comarca'],
            local: $filtros['local'],
            no: $filtros['no'],
            q: $filtros['q'],
            pagina: $filtros['pagina'],
            porPagina: 100,
        );

        return view('lista-contatos', [
            'canal' => $canal,
            'rotulo' => self::CANAIS[$canal],
            'lista' => $lista,
            'recorte' => $this->recorte($filtros),
            'comarcas' => $this->comarcas(),
            'locais' => $this->locais(),
            'nos' => $this->nos(),
            'canais' => $this->canais($lista['totais'], $canal),
        ]);
    }

    /**
     * A folha A4 do recorte inteiro: mesma consulta da tela, sem `LIMIT` e
     * sem os controles. Papel não pagina, então o que sai é o recorte todo —
     * não a página de 100 linhas que está na tela.
     */
    public function impressao(Request $request, ListarContatos $acao, string $canal): View
    {
        abort_unless(isset(self::CANAIS[$canal]), 404);

        $filtros = $this->filtros($request);

        $lista = $acao->executar(
            tipo: self::CANAIS[$canal]['tipo'],
            comarca: $filtros['comarca'],
            local: $filtros['local'],
            no: $filtros['no'],
            q: $filtros['q'],
            pagina: 1,
            porPagina: null,
        );

        return view('lista-contatos-impressao', [
            'canal' => $canal,
            'rotulo' => self::CANAIS[$canal],
            'lista' => $lista,
            'recorte' => $this->recorte($filtros),
        ]);
    }

    /**
     * @return array{comarca: string|null, local: string|null, no: string|null, q: string, pagina: int}
     */
    private function filtros(Request $request): array
    {
        $dados = $request->validate([
            'comarca' => ['nullable', 'string', 'max:8'],
            'local' => ['nullable', 'string', 'max:8'],
            'no' => ['nullable', 'string', 'max:8'],
            'q' => ['nullable', 'string', 'max:80'],
            'pagina' => ['nullable', 'integer', 'min:1'],
        ]);

        $texto = static fn (mixed $valor): ?string => is_string($valor) && trim($valor) !== '' ? trim($valor) : null;

        return [
            'comarca' => $texto($dados['comarca'] ?? null),
            'local' => $texto($dados['local'] ?? null),
            'no' => $texto($dados['no'] ?? null),
            'q' => $texto($dados['q'] ?? null) ?? '',
            'pagina' => (int) ($dados['pagina'] ?? 1),
        ];
    }

    /**
     * Os nomes do recorte, na ordem em que se lê: comarca · local · nó · busca.
     * A tela usa para dizer sobre o que é a lista; a folha, no cabeçalho.
     *
     * @param  array{comarca: string|null, local: string|null, no: string|null, q: string, pagina: int}  $filtros
     * @return list<string>
     */
    private function recorte(array $filtros): array
    {
        $partes = [];

        if ($filtros['comarca'] !== null) {
            $nome = Comarca::query()->where('chave', $filtros['comarca'])->value('nome');

            if ($nome !== null) {
                $partes[] = (string) $nome;
            }
        }

        if ($filtros['local'] !== null) {
            $linha = DB::table('locais')->where('chave', $filtros['local'])->first(['nome']);

            if ($linha !== null) {
                /** @var array<string, mixed> $campos */
                $campos = (array) $linha;
                $partes[] = (string) $campos['nome'];
            }
        }

        if ($filtros['no'] !== null) {
            $linha = DB::table('nos')
                ->leftJoin('nos as pai', 'pai.id', '=', 'nos.pai_id')
                ->where('nos.chave', $filtros['no'])
                ->first(['nos.nome as nome', 'pai.nome as pai_nome']);

            if ($linha !== null) {
                /** @var array<string, mixed> $campos */
                $campos = (array) $linha;
                $partes[] = $campos['pai_nome'] === null
                    ? (string) $campos['nome']
                    : (string) $campos['pai_nome'].' › '.(string) $campos['nome'];
            }
        }

        if ($filtros['q'] !== '') {
            $partes[] = 'busca “'.$filtros['q'].'”';
        }

        return $partes;
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
     * @return list<array{rotulo: string, comarca: string|null, itens: list<array{chave: string, nome: string}>}>
     */
    private function locais(): array
    {
        $linhas = DB::table('locais')
            ->leftJoin('comarcas', 'comarcas.id', '=', 'locais.comarca_id')
            ->orderBy('locais.ordem_num')
            ->orderBy('locais.ordem_idx')
            ->get([
                'locais.chave as chave',
                'locais.nome as nome',
                'comarcas.chave as comarca_chave',
                'comarcas.nome as comarca_nome',
            ]);

        $grupos = [];

        foreach ($linhas as $linha) {
            /** @var array<string, mixed> $campos */
            $campos = (array) $linha;

            $chaveComarca = $campos['comarca_chave'] === null ? null : (string) $campos['comarca_chave'];
            $grupo = $chaveComarca ?? 'sede';

            if (! isset($grupos[$grupo])) {
                $grupos[$grupo] = [
                    'rotulo' => $campos['comarca_nome'] === null ? 'Sede' : (string) $campos['comarca_nome'],
                    'comarca' => $chaveComarca,
                    'itens' => [],
                ];
            }

            $grupos[$grupo]['itens'][] = [
                'chave' => (string) $campos['chave'],
                'nome' => (string) $campos['nome'],
            ];
        }

        return array_values($grupos);
    }

    /**
     * Unidades e setores para o filtro por nó, agrupados pelo local. O rótulo
     * do setor carrega o pai (`1ª Vara Cível › Cartório`) porque a API inclui
     * os filhos diretos: escolher a vara já traz o cartório, e o filtro precisa
     * deixar isso visível.
     *
     * @return array<string, array{rotulo: string, comarca: string|null, local: string, itens: list<array{chave: string, nome: string}>}>
     */
    private function nos(): array
    {
        $linhas = DB::table('nos')
            ->join('locais', 'locais.id', '=', 'nos.local_id')
            ->leftJoin('comarcas', 'comarcas.id', '=', 'locais.comarca_id')
            ->leftJoin('nos as pai', 'pai.id', '=', 'nos.pai_id')
            ->orderBy('locais.ordem_num')
            ->orderBy('locais.ordem_idx')
            ->orderByRaw('coalesce(pai.ordem_num, nos.ordem_num) ASC NULLS LAST')
            ->orderByRaw('coalesce(pai.ordem_idx, nos.ordem_idx)')
            ->orderByRaw('nos.pai_id ASC NULLS FIRST')
            ->orderByRaw('nos.ordem_num ASC NULLS LAST')
            ->orderBy('nos.ordem_idx')
            ->orderBy('nos.chave')
            ->get([
                'nos.chave as chave',
                'nos.nome as nome',
                'nos.local_id as local_id',
                'locais.chave as local_chave',
                'locais.nome as local_nome',
                'locais.tipo as local_tipo',
                'comarcas.chave as comarca_chave',
                'comarcas.nome as comarca_nome',
                'pai.nome as pai_nome',
            ]);

        $grupos = [];

        foreach ($linhas as $linha) {
            /** @var array<string, mixed> $campos */
            $campos = (array) $linha;

            $chaveLocal = (string) $campos['local_chave'];

            if (! isset($grupos[$chaveLocal])) {
                $grupos[$chaveLocal] = [
                    'rotulo' => $campos['local_tipo'] === 'SEDE'
                        ? (string) $campos['local_nome']
                        : trim(($campos['comarca_nome'] ?? '').' · '.$campos['local_nome'], ' ·'),
                    'comarca' => $campos['comarca_chave'] === null ? null : (string) $campos['comarca_chave'],
                    'local' => $chaveLocal,
                    'itens' => [],
                ];
            }

            $nome = (string) $campos['nome'];

            $grupos[$chaveLocal]['itens'][] = [
                'chave' => (string) $campos['chave'],
                'nome' => $campos['pai_nome'] === null ? $nome : (string) $campos['pai_nome'].' › '.$nome,
            ];
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
            ['rota' => 'canal.telefones', 'rotulo' => 'Telefones', 'total' => $totais['Telefone'] ?? 0, 'atual' => $canal === 'telefones'],
            ['rota' => 'canal.whatsapp', 'rotulo' => 'WhatsApp', 'total' => $totais['WhatsApp'] ?? 0, 'atual' => $canal === 'whatsapp'],
            ['rota' => 'canal.emails', 'rotulo' => 'E-mails', 'total' => $totais['E-mail'] ?? 0, 'atual' => $canal === 'emails'],
            ['rota' => 'ramais', 'rotulo' => 'Ramais', 'total' => null, 'atual' => false],
        ];
    }
}
