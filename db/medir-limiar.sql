-- -----------------------------------------------------------------------------
-- medir-limiar.sql — de onde sai o `pg_trgm.similarity_threshold` do motor
-- -----------------------------------------------------------------------------
-- 0,45 é o padrão do PostgreSQL e é chute para este domínio. O limiar tem de
-- separar duas coisas medidas na própria base:
--
--   * `sim_intencao` — parecença entre a consulta COM ERRO e o termo que a
--     pessoa queria (ex.: 'vitora' → 'vitória'). É o piso: abaixo disso a busca
--     não corrige o erro de digitação.
--   * `sim_ruido` — maior parecença entre a mesma consulta e QUALQUER outro
--     termo do vocabulário (fóruns + nós). É o teto: acima disso ela começa a
--     sugerir coisa errada.
--
-- O limiar recomendado fica no vão entre os dois. Se o vão não existir para
-- alguma consulta, o dado está dizendo que trigrama sozinho não resolve — e a
-- resposta é ordenar por similaridade em vez de cortar por limiar.
--
--   psql "$DATABASE_URL" -v ON_ERROR_STOP=1 -f db/medir-limiar.sql

\pset border 2
\pset footer off

WITH vocab AS (
    SELECT DISTINCT norm_texto(nome) AS t FROM nos   WHERE nome <> ''
    UNION
    SELECT DISTINCT norm_texto(nome) AS t FROM locais WHERE nome <> ''
), q(consulta, alvo) AS (VALUES
    ('vitora',                    'vitoria'),
    ('cartoro',                   'cartorio'),
    ('vara ciminal',              'vara criminal'),
    ('secretaria unificad',       'secretaria unificada'),
    ('contadoria',                'contadoria'),
    ('fazenda publica municipal', 'fazenda publica municipal'),
    ('distribuicao',              'distribuicao'),
    ('audiencia',                 'audiencia'),
    ('jecrim',                    'jecrim'),
    ('comissario',                'comissario')
)
SELECT q.consulta,
       q.alvo,
       round(similarity(norm_texto(q.consulta), norm_texto(q.alvo))::numeric, 3) AS sim_intencao,
       round((SELECT max(similarity(norm_texto(q.consulta), v.t)) FROM vocab v
               WHERE v.t <> norm_texto(q.alvo))::numeric, 3)                     AS sim_ruido,
       (SELECT v.t FROM vocab v
         ORDER BY similarity(norm_texto(q.consulta), v.t) DESC, v.t LIMIT 1)     AS top1_do_vocabulario
  FROM q
 ORDER BY sim_intencao DESC;

-- Quantos termos do vocabulário cada consulta arrasta em cada limiar: é a
-- medida do custo de baixar o limiar (mais sugestão certa, mais ruído junto).
WITH vocab AS (
    SELECT DISTINCT norm_texto(nome) AS t FROM nos   WHERE nome <> ''
    UNION
    SELECT DISTINCT norm_texto(nome) AS t FROM locais WHERE nome <> ''
), q(consulta) AS (VALUES
    ('vitora'), ('cartoro'), ('vara ciminal'), ('secretaria unificad'),
    ('contadoria'), ('fazenda publica municipal'), ('jecrim')
), limiares(limiar) AS (VALUES (0.20), (0.30), (0.40), (0.45), (0.50), (0.60))
SELECT q.consulta,
       l.limiar,
       count(*) FILTER (WHERE similarity(norm_texto(q.consulta), v.t) >= l.limiar) AS termos_no_limiar
  FROM q CROSS JOIN limiares l CROSS JOIN vocab v
 GROUP BY q.consulta, l.limiar
 ORDER BY q.consulta, l.limiar;

-- -----------------------------------------------------------------------------
-- O vocabulário que importa: PALAVRA, não nome inteiro
-- -----------------------------------------------------------------------------
-- Medido: `similarity('vitora', 'vitoria')` = 0,500, mas o vizinho mais próximo
-- entre os 1.4xx nomes completos é `diretora` — nome longo dilui o trigrama.
-- Com o vocabulário reduzido a palavras (4+ letras, 467 tokens no seed), a
-- mesma consulta acha `vitoria` com 0,500 e um único candidato no limiar 0,45.
-- É por isso que a sugestão de erro de digitação compara PALAVRA, e a busca
-- principal continua em ILIKE sobre o texto inteiro.
WITH nomes AS (
    SELECT norm_texto(nome) AS n FROM nos   WHERE nome <> ''
    UNION ALL
    SELECT norm_texto(nome) FROM locais WHERE nome <> ''
), tokens AS (
    SELECT DISTINCT t FROM (
        SELECT unnest(regexp_split_to_array(n, '[^a-z0-9]+')) AS t FROM nomes
    ) s WHERE length(t) >= 4
), q(consulta) AS (VALUES
    ('vitora'), ('cartoro'), ('ciminal'), ('unificad'), ('jecrim'), ('fazenda')
)
SELECT q.consulta,
       (SELECT t FROM tokens WHERE similarity(norm_texto(q.consulta), t) >= 0.45
         ORDER BY similarity(norm_texto(q.consulta), t) DESC LIMIT 1)      AS top1_no_limiar_045,
       (SELECT round(max(similarity(norm_texto(q.consulta), t))::numeric, 3)
          FROM tokens)                                                     AS sim_max,
       (SELECT count(*) FROM tokens
         WHERE similarity(norm_texto(q.consulta), t) >= 0.45)              AS candidatos_045
  FROM q;

SELECT count(*) AS tokens_no_vocabulario FROM (
    SELECT DISTINCT t FROM (
        SELECT unnest(regexp_split_to_array(norm_texto(nome), '[^a-z0-9]+')) AS t
          FROM (SELECT nome FROM nos UNION ALL SELECT nome FROM locais) x
    ) s WHERE length(t) >= 4
) y;
