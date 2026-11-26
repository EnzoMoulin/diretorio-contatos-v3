<?php

namespace App\Actions;

use Illuminate\Support\Facades\DB;

/**
 * Índice reverso de ramais (issue #7, fila de 15/11/2026).
 *
 * A pergunta do balcão é "quem é o ramal 1234?" e o produto só respondia por
 * lugar. Esta ação lê os contatos de tipo `Ramal` e devolve, para cada um, o
 * contexto em que ele aparece na árvore: comarca (ou Sede), unidade e setor.
 *
 * Duas decisões que valem registro:
 *   - **A ordem é numérica quando há número.** `so_digitos(valor)` ordena o ramal
 *     pelo número, não pela string mascarada; quando o seed de demonstração traz
 *     tudo mascarado (`XXXX`), o critério cai para a ordem do catálogo — a mesma
 *     de `locais.ordem_num`/`nos.ordem_num` — e a tela avisa que a base é mascarada.
 *   - **Prefixo curto casa por substring, não por trigrama.** Medido em 25/11:
 *     `word_similarity` de três dígitos contra o número completo fica em 0,25,
 *     abaixo do limiar de 0,45 —
 *     o trigrama não enxerga três dígitos dentro de um número maior. O `LIKE
 *     '%123%'` é o que faz "123" achar "1234"; o trigrama fica como tolerância a
 *     erro de digitação, não como mecanismo principal.
 */
final class ListarRamais
{
    /** Mesmo limiar de trigrama do resto do projeto (medido no v2). */
    public const LIMIAR = 0.45;

    /**
     * @return array{
     *     itens: list<array{chave: string, valor: string, comarca: string, local: string, local_tipo: string, unidade: string, setor: string|null, digitos: string}>,
     *     total: int,
     *     total_geral: int,
     *     mascarados: int,
     *     busca: string,
     *     digitos: string
     * }
     */
    public function executar(string $busca = ''): array
    {
        $busca = trim($busca);
        $digitos = preg_replace('/\D+/', '', $busca) ?? '';

        // Busca só por número: texto sem dígito não vira consulta vazia silenciosa
        // (o filtro é aplicado com `digitos = ''`, que não casa com ninguém).
        $filtrar = $busca !== '' && $digitos !== '';

        $sql = <<<'SQL'
            SELECT ct.chave,
                   ct.valor,
                   COALESCE(cm.nome, 'Sede') AS comarca,
                   l.nome AS local,
                   l.tipo AS local_tipo,
                   COALESCE(pai.nome, n.nome, l.nome) AS unidade,
                   CASE WHEN pai.id IS NOT NULL THEN n.nome END AS setor,
                   so_digitos(ct.valor) AS digitos
              FROM contatos ct
              JOIN locais l ON l.id = ct.local_id
              LEFT JOIN comarcas cm ON cm.id = l.comarca_id
              LEFT JOIN nos n ON n.id = ct.no_id
              LEFT JOIN nos pai ON pai.id = n.pai_id
             WHERE ct.tipo = 'Ramal'
            SQL;

        $parametros = [];

        if ($filtrar) {
            $sql .= <<<'SQL'

               AND (
                     so_digitos(ct.valor) LIKE ?
                     OR word_similarity(?, so_digitos(ct.valor)) >= ?
                   )
            SQL;
            array_push($parametros, '%'.$digitos.'%', $digitos, self::LIMIAR);
        } elseif ($busca !== '') {
            // Texto sem dígito: nenhum ramal casa, e a tela explica o formato.
            $sql .= ' AND false';
        }

        $sql .= <<<'SQL'

             ORDER BY CASE
                          WHEN so_digitos(ct.valor) = ? THEN 0
                          WHEN right(so_digitos(ct.valor), 4) LIKE ? THEN 1
                          WHEN so_digitos(ct.valor) LIKE ? THEN 2
                          ELSE 3
                      END,
                      NULLIF(so_digitos(ct.valor), '') IS NULL,
                      so_digitos(ct.valor),
                      l.ordem_num NULLS LAST,
                      l.ordem_idx,
                      n.ordem_num NULLS LAST,
                      n.ordem_idx,
                      ct.ordem_num NULLS LAST,
                      ct.ordem_idx,
                      ct.chave
            SQL;

        array_push($parametros, $digitos, $digitos.'%', $digitos.'%');

        /** @var list<object> $linhas */
        $linhas = DB::select($sql, $parametros);

        $itens = [];

        foreach ($linhas as $linha) {
            /** @var array<string, mixed> $campos */
            $campos = (array) $linha;

            $itens[] = [
                'chave' => (string) $campos['chave'],
                'valor' => (string) $campos['valor'],
                'comarca' => (string) $campos['comarca'],
                'local' => (string) $campos['local'],
                'local_tipo' => (string) $campos['local_tipo'],
                'unidade' => (string) $campos['unidade'],
                'setor' => $campos['setor'] === null ? null : (string) $campos['setor'],
                'digitos' => (string) $campos['digitos'],
            ];
        }

        $totalGeral = (int) DB::scalar("SELECT count(*) FROM contatos WHERE tipo = 'Ramal'");
        $mascarados = (int) DB::scalar("SELECT count(*) FROM contatos WHERE tipo = 'Ramal' AND so_digitos(valor) = ''");

        return [
            'itens' => $itens,
            'total' => count($itens),
            'total_geral' => $totalGeral,
            'mascarados' => $mascarados,
            'busca' => $busca,
            'digitos' => $digitos,
        ];
    }
}
