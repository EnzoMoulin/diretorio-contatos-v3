<?php

namespace App\Actions;

use App\Models\Sinonimo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Busca do diretório (ideia 04 do backlog): trigrama sobre o texto normalizado,
 * com vocabulário expandido pelo léxico de sinônimos.
 *
 * Por que a expansão existe: `pg_trgm` acha erro de digitação ("cartroio"), não
 * vocabulário — "reconhecimento de firma" e "cartorio" não compartilham trigrama
 * nenhum, e é a mesma pergunta. O léxico é curado à mão (SinonimoSeeder), então
 * não há modelo, custo nem treino.
 *
 * Semântica, decidida aqui e não na consulta:
 *   - a consulta vira palavras de conteúdo (sem "de", "da", "para"...), porque
 *     "de" casa com qualquer linha e afogaria a ordenação;
 *   - se o termo tem sinônimo, as frases da expansão **substituem** a consulta:
 *     a busca por "reconhecimento de firma" devolve exatamente o que a busca por
 *     "cartorio" devolve — é o critério de aceite da issue;
 *   - dentro de uma frase as palavras valem juntas (E); frases diferentes valem
 *     como alternativas (OU);
 *   - cada resultado carrega `pontuacao` (0..1) para ordenar, mas o conjunto é o
 *     mesmo da consulta canônica — pontuação ordena, não filtra.
 */
final class Buscar
{
    /** Limiar de trigrama usado em todo o projeto (medido no v2). */
    public const LIMIAR = 0.45;

    public const LIMITE_PADRAO = 8;

    public const LIMITE_MAXIMO = 20;

    /** Palavras que não discriminam nada sozinhas. */
    private const PARADAS = [
        'de', 'da', 'do', 'das', 'dos', 'e', 'em', 'no', 'na', 'nos', 'nas',
        'a', 'o', 'as', 'os', 'ao', 'aos', 'para', 'por', 'com', 'que',
    ];

    /**
     * @return array{termo: string, expansao: list<string>, total: int, resultados: list<array{tipo: string, chave: string, rotulo: string, contexto: string|null, pontuacao: float}>}
     */
    public function executar(string $consulta, int $limite = self::LIMITE_PADRAO, bool $expandir = true): array
    {
        $consulta = trim($consulta);
        $termo = $this->normalizar($consulta);

        if ($termo === '' || $this->termos($termo) === []) {
            return ['termo' => $consulta, 'expansao' => [], 'total' => 0, 'resultados' => []];
        }

        $expansao = $expandir ? $this->expansao($termo) : [];
        $unidades = $expansao === [] ? [$termo] : $expansao;

        /** @var array<string, array{tipo: string, chave: string, rotulo: string, contexto: string|null, pontuacao: float}> $achados */
        $achados = [];

        foreach ($unidades as $unidade) {
            $termos = $this->termos($unidade);

            if ($termos === []) {
                continue;
            }

            foreach ($this->consultar($termos) as $linha) {
                $chave = $linha['tipo'].'|'.$linha['chave'];

                if (! isset($achados[$chave]) || $linha['pontuacao'] > $achados[$chave]['pontuacao']) {
                    $achados[$chave] = $linha;
                }
            }
        }

        $resultados = array_values($achados);
        usort($resultados, fn (array $a, array $b): int => [$b['pontuacao'], $a['rotulo']] <=> [$a['pontuacao'], $b['rotulo']]);

        return [
            'termo' => $consulta,
            'expansao' => $expansao,
            'total' => count($resultados),
            'resultados' => $limite > 0 ? array_slice($resultados, 0, $limite) : $resultados,
        ];
    }

    /**
     * Formas canônicas cadastradas para o termo. A expansão substitui a consulta,
     * então o dicionário é consultado pela forma inteira — não por palavra.
     *
     * @return list<string>
     */
    private function expansao(string $termo): array
    {
        $linha = Sinonimo::query()->where('termo', $termo)->first();

        if ($linha === null) {
            return [];
        }

        /** @var list<string> $expansao */
        $expansao = $linha->expansao;

        return $expansao;
    }

    /** @return list<string> */
    private function termos(string $frase): array
    {
        $achados = [];

        foreach (preg_split('/\s+/', $this->normalizar($frase)) ?: [] as $palavra) {
            if (strlen($palavra) >= 3 && ! in_array($palavra, self::PARADAS, true)) {
                $achados[] = $palavra;
            }
        }

        return $achados;
    }

    /** Espelha `norm_texto` do banco: sem acento, minúscula. */
    private function normalizar(string $texto): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', ' ', Str::lower(Str::ascii($texto))) ?? '');
    }

    /**
     * Uma passada por frase: a linha entra se **todas** as palavras casarem por
     * trigrama (ou por trecho literal), e a pontuação é a melhor palavra.
     *
     * @param  list<string>  $termos
     * @return list<array{tipo: string, chave: string, rotulo: string, contexto: string|null, pontuacao: float}>
     */
    private function consultar(array $termos): array
    {
        $lista = implode(' ', $termos);

        $linhas = DB::select(<<<'SQL'
            WITH trechos AS (
                SELECT 'comarca' AS tipo, c.chave, c.nome AS rotulo, ''::text AS contexto,
                       norm_texto(c.nome) AS alvo
                  FROM comarcas c
                 WHERE c.arquivado_em IS NULL
                UNION ALL
                SELECT 'local', l.chave, l.nome, coalesce(cm.nome, ''),
                       norm_texto(l.nome)
                  FROM locais l
                  LEFT JOIN comarcas cm ON cm.id = l.comarca_id
                 WHERE l.arquivado_em IS NULL
                UNION ALL
                SELECT 'no', n.chave, n.nome, l.nome || coalesce(' · ' || cm.nome, ''),
                       norm_texto(n.nome)
                  FROM nos n
                  JOIN locais l ON l.id = n.local_id
                  LEFT JOIN comarcas cm ON cm.id = l.comarca_id
                 WHERE n.arquivado_em IS NULL
                UNION ALL
                SELECT 'contato', ct.chave, ct.tipo || ' ' || ct.valor,
                       coalesce(nn.nome, l.nome) || coalesce(' · ' || cm.nome, ''),
                       ct.busca_texto
                  FROM contatos ct
                  JOIN locais l ON l.id = ct.local_id
                  LEFT JOIN nos nn ON nn.id = ct.no_id
                  LEFT JOIN comarcas cm ON cm.id = l.comarca_id
                UNION ALL
                SELECT 'util', u.chave, u.nome, u.tipo, u.busca_texto
                  FROM telefones_uteis u
            )
            SELECT tipo, chave, rotulo, NULLIF(contexto, '') AS contexto,
                   max(word_similarity(t.termo, alvo))::float8 AS pontuacao
              FROM trechos
             CROSS JOIN unnest(string_to_array(?::text, ' ')) AS t(termo)
             WHERE NOT EXISTS (
                   SELECT 1
                     FROM unnest(string_to_array(?::text, ' ')) AS z(termo)
                    WHERE NOT (word_similarity(z.termo, alvo) >= ?
                               OR alvo LIKE '%' || z.termo || '%')
                   )
             GROUP BY tipo, chave, rotulo, contexto
             ORDER BY pontuacao DESC, rotulo
            SQL, [$lista, $lista, self::LIMIAR]);

        $resultados = [];

        foreach ($linhas as $linha) {
            /** @var array<string, mixed> $campos */
            $campos = (array) $linha;
            $resultados[] = [
                'tipo' => (string) $campos['tipo'],
                'chave' => (string) $campos['chave'],
                'rotulo' => (string) $campos['rotulo'],
                'contexto' => $campos['contexto'] === null ? null : (string) $campos['contexto'],
                'pontuacao' => round((float) $campos['pontuacao'], 3),
            ];
        }

        return $resultados;
    }
}
