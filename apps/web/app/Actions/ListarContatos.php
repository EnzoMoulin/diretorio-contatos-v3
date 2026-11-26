<?php

namespace App\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Listagem de contatos por canal (issue #43).
 *
 * O v2 respondia `GET /api/contatos` com 1.807 linhas de uma vez e sem recorte
 * por tipo; a pergunta do balcão ("quais são os telefones desta comarca?")
 * exigia navegar nó a nó. Esta ação devolve a mesma leitura com o eixo `tipo`
 * e com os filtros do v2 — comarca, local, nó e texto —, paginada.
 *
 * Decisões que valem registro:
 *   - **`tipo` é comparado normalizado.** `E-MAIL` e `EMAIL` caem no rótulo
 *     `E-mail` do banco; `Fax` existe no CHECK do DDL e é aceito com zero linhas.
 *   - **Nó filtra a subárvore.** A profundidade máxima abaixo do local é 2, e o
 *     contato do cartório é o telefone que o balcão procura pelo nome da vara:
 *     filtrar por `UNI` inclui os filhos diretos.
 *   - **`q` é filtro sobre o texto normalizado, não busca.** Cada palavra casa
 *     por trecho (ou pelos dígitos) no rótulo do contato — descrição, valor,
 *     unidade, setor, local e comarca —, então `cartorio` acha `Cartório` e
 *     `vitoria` devolve os mesmos 166 da comarca. O v2 fazia `LIKE` sensível a
 *     acento justamente sobre essas colunas e devolvia 0 para `cartorio`.
 *     Semelhança, ranking e tolerância a erro de digitação continuam em
 *     `GET /api/v1/busca`.
 *   - **A ordem é a do seed**, por local e, dentro dele, pelo `ordem_idx` do
 *     catálogo — não pelo `ordem_num`, que numera por bloco de unidade e
 *     espalharia o WhatsApp do gabinete para o fim da lista — e nem pela data
 *     de criação.
 */
final class ListarContatos
{
    public const POR_PAGINA = 50;

    public const POR_PAGINA_MAXIMO = 200;

    /** Rótulo como está no banco, por forma normalizada da entrada. */
    private const TIPOS = [
        'TELEFONE' => 'Telefone',
        'WHATSAPP' => 'WhatsApp',
        'RAMAL' => 'Ramal',
        'E-MAIL' => 'E-mail',
        'EMAIL' => 'E-mail',
        'FAX' => 'Fax',
    ];

    /** Rótulo canônico do tipo, ou `null` quando a forma não existe. */
    public static function rotulo(?string $tipo): ?string
    {
        if ($tipo === null || trim($tipo) === '') {
            return null;
        }

        $normalizado = str_replace(['_', ' '], '-', strtoupper(trim(Str::ascii($tipo))));

        return self::TIPOS[$normalizado] ?? null;
    }

    /**
     * @return array{
     *     tipo: string|null,
     *     filtros: array{comarca: string|null, local: string|null, no: string|null, q: string},
     *     total: int,
     *     pagina: int,
     *     por_pagina: int,
     *     paginas: int,
     *     totais: array<string, int>,
     *     itens: list<array<string, mixed>>
     * }
     */
    public function executar(
        ?string $tipo = null,
        ?string $comarca = null,
        ?string $local = null,
        ?string $no = null,
        string $q = '',
        int $pagina = 1,
        int $porPagina = self::POR_PAGINA,
    ): array {
        $rotulo = self::rotulo($tipo);
        $q = trim($q);
        $pagina = max(1, $pagina);
        $porPagina = max(1, min(self::POR_PAGINA_MAXIMO, $porPagina));

        [$core, $parametros] = $this->consulta($comarca, $local, $no);
        [$condicaoTexto, $parametrosTexto] = $this->condicaoTexto($q);

        // `total` e `itens` respeitam o `tipo`; `totais` não — ele é justamente
        // a contagem por aba.
        $onde = $condicaoTexto === '' ? [] : [$condicaoTexto];
        $parametrosOnde = $parametrosTexto;

        if ($rotulo !== null) {
            $onde[] = 'b.tipo = ?';
            $parametrosOnde[] = $rotulo;
        }

        $total = $this->inteiro(
            'SELECT count(*) AS n FROM ('.$core.') b'.$this->onde($onde),
            array_merge($parametros, $parametrosOnde),
        );

        $totais = $this->totais($core, $parametros, $condicaoTexto, $parametrosTexto);

        $sql = 'SELECT b.* FROM ('.$core.') b'.$this->onde($onde)
            .' ORDER BY b.ordem_local NULLS LAST, b.ordem_local_idx,'
            .' b.ordem_contato_idx, b.chave'
            .' LIMIT ? OFFSET ?';

        /** @var list<object> $linhas */
        $linhas = DB::select($sql, array_merge(
            $parametros,
            $parametrosOnde,
            [$porPagina, ($pagina - 1) * $porPagina],
        ));

        $itens = [];

        foreach ($linhas as $linha) {
            /** @var array<string, mixed> $campos */
            $campos = (array) $linha;

            $itens[] = [
                'chave' => (string) $campos['chave'],
                'tipo' => (string) $campos['tipo'],
                'valor' => (string) $campos['valor'],
                'descricao' => (string) $campos['descricao'],
                'comarca' => $campos['comarca_nome'] === null ? null : (string) $campos['comarca_nome'],
                'comarca_chave' => $campos['comarca_chave'] === null ? null : (string) $campos['comarca_chave'],
                'local' => (string) $campos['local_nome'],
                'local_chave' => (string) $campos['local_chave'],
                'local_tipo' => (string) $campos['local_tipo'],
                'unidade' => (string) $campos['unidade'],
                'unidade_chave' => (string) $campos['unidade_chave'],
                'setor' => $campos['setor'] === null ? null : (string) $campos['setor'],
                'fonte' => (string) $campos['fonte'],
                'vigente_desde' => $campos['vigente_desde'] === null ? null : (string) $campos['vigente_desde'],
            ];
        }

        return [
            'tipo' => $rotulo,
            'filtros' => [
                'comarca' => $comarca,
                'local' => $local,
                'no' => $no,
                'q' => $q,
            ],
            'total' => $total,
            'pagina' => $pagina,
            'por_pagina' => $porPagina,
            'paginas' => $total === 0 ? 0 : (int) ceil($total / $porPagina),
            'totais' => $totais,
            'itens' => $itens,
        ];
    }

    /**
     * @param  list<string>  $condicoes
     */
    private function onde(array $condicoes): string
    {
        return $condicoes === [] ? '' : ' WHERE '.implode(' AND ', $condicoes);
    }

    /**
     * @param  list<mixed>  $parametros
     */
    private function inteiro(string $sql, array $parametros): int
    {
        $linha = DB::selectOne($sql, $parametros);

        if ($linha === null) {
            return 0;
        }

        /** @var array<string, mixed> $campos */
        $campos = (array) $linha;

        return (int) $campos['n'];
    }

    /**
     * Corpo comum das três consultas: filtros de escopo, sem `tipo` — que é
     * aplicado por fora para que os totais por canal saiam do mesmo recorte.
     *
     * @return array{0: string, 1: list<string>}
     */
    private function consulta(?string $comarca, ?string $local, ?string $no): array
    {
        $condicoes = [];
        $parametros = [];

        if ($comarca !== null && $comarca !== '') {
            $condicoes[] = 'cm.chave = ?';
            $parametros[] = $comarca;
        }

        if ($local !== null && $local !== '') {
            $condicoes[] = 'l.chave = ?';
            $parametros[] = $local;
        }

        if ($no !== null && $no !== '') {
            $condicoes[] = '(ct.no_id = (SELECT id FROM nos WHERE chave = ?)'
                .' OR ct.no_id IN (SELECT id FROM nos WHERE pai_id = (SELECT id FROM nos WHERE chave = ?)))';
            $parametros[] = $no;
            $parametros[] = $no;
        }

        $sql = <<<'SQL'
            SELECT ct.chave, ct.tipo, ct.valor, ct.descricao, ct.fonte, ct.vigente_desde,
                   ct.ordem_num AS ordem_contato, ct.ordem_idx AS ordem_contato_idx,
                   l.chave AS local_chave, l.nome AS local_nome, l.tipo AS local_tipo,
                   l.ordem_num AS ordem_local, l.ordem_idx AS ordem_local_idx,
                   cm.chave AS comarca_chave, cm.nome AS comarca_nome,
                   coalesce(pai.nome, n.nome, l.nome) AS unidade,
                   coalesce(pai.chave, n.chave, l.chave) AS unidade_chave,
                   CASE WHEN pai.id IS NOT NULL THEN n.nome END AS setor,
                   norm_texto(
                       coalesce(ct.descricao, '') || ' ' || coalesce(ct.valor, '') || ' ' ||
                       coalesce(n.nome, '') || ' ' || coalesce(pai.nome, '') || ' ' ||
                       l.nome || ' ' || coalesce(cm.nome, '')
                   ) AS alvo,
                   ct.busca_digitos AS digitos
              FROM contatos ct
              JOIN locais l ON l.id = ct.local_id
              LEFT JOIN comarcas cm ON cm.id = l.comarca_id
              LEFT JOIN nos n ON n.id = ct.no_id
              LEFT JOIN nos pai ON pai.id = n.pai_id
            SQL;

        if ($condicoes !== []) {
            $sql .= ' WHERE '.implode(' AND ', $condicoes);
        }

        return [$sql, $parametros];
    }

    /**
     * Cada palavra da consulta tem de casar — por trecho no texto normalizado
     * ou nos dígitos — para a linha entrar; é filtro, não pontuação, então a
     * ordem não muda com `q`. Não usa trigrama de propósito: a normalização já
     * resolve acento e caixa, e o trigrama trazia vizinhos que o recorte não
     * pediu (`vitoria` casava 194 em vez dos 166 da comarca). Semelhança e
     * ranking continuam sendo o trabalho de `GET /api/v1/busca`.
     *
     * @return array{0: string, 1: list<string>}
     */
    private function condicaoTexto(string $q): array
    {
        $normalizado = trim(preg_replace('/[^a-z0-9]+/', ' ', Str::lower(Str::ascii($q))) ?? '');

        if ($normalizado === '') {
            return ['', []];
        }

        $condicoes = [];
        $parametros = [];

        /** @var list<string> $termos */
        $termos = array_values(array_unique(array_filter(explode(' ', $normalizado), fn (string $t): bool => $t !== '')));

        foreach ($termos as $termo) {
            $digitos = preg_replace('/\D+/', '', $termo) ?? '';

            $condicao = 'b.alvo LIKE ?';
            $parametros[] = '%'.$termo.'%';

            if ($digitos !== '') {
                $condicao .= ' OR b.digitos LIKE ?';
                $parametros[] = '%'.$digitos.'%';
            }

            $condicoes[] = '('.$condicao.')';
        }

        return [implode(' AND ', $condicoes), $parametros];
    }

    /**
     * Contagem por canal no recorte atual (sem o filtro `tipo`, que é a aba).
     *
     * @param  list<string>  $parametros
     * @param  list<string>  $parametrosTexto
     * @return array<string, int>
     */
    private function totais(string $core, array $parametros, string $condicaoTexto, array $parametrosTexto): array
    {
        $onde = $condicaoTexto === '' ? '' : ' WHERE '.$condicaoTexto;

        /** @var list<object> $linhas */
        $linhas = DB::select(
            'SELECT b.tipo, count(*) AS n FROM ('.$core.') b'.$onde.' GROUP BY b.tipo',
            array_merge($parametros, $parametrosTexto),
        );

        $totais = ['Telefone' => 0, 'WhatsApp' => 0, 'Ramal' => 0, 'E-mail' => 0, 'Fax' => 0];

        foreach ($linhas as $linha) {
            /** @var array<string, mixed> $campos */
            $campos = (array) $linha;
            $totais[(string) $campos['tipo']] = (int) $campos['n'];
        }

        return $totais;
    }
}
