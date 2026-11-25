-- -----------------------------------------------------------------------------
-- expurgar-historico.sql — retenção de 180 dias do histórico do dado
-- -----------------------------------------------------------------------------
-- O histórico guarda o retrato antes/depois (JSONB) para quem precisar saber
-- "como estava em tal dia". Isso não pode virar arquivo permanente de dado
-- pessoal: passados 180 dias, sobra só a contagem.
--
-- O que este script faz, numa transação:
--   1. agrega em `historico_mensal` (mês, ação, entidade) tudo que passou do prazo;
--   2. apaga as linhas cruas;
--   3. registra a própria execução em `historico` (ação `expurgar`).
--
-- Rodar:
--   psql "$DATABASE_URL" -v dias=180 -v ON_ERROR_STOP=1 -f db/expurgar-historico.sql
-- Agendar (quando houver produção): cron diário às 03:00, ou `pg_cron`, ou o
-- scheduler do Laravel chamando este arquivo. Enquanto não há produção, rodar à
-- mão depois de qualquer carga grande.
--
-- `dias` é opcional (padrão 180). Para ensaio, use um número alto (ex.: 36500) e
-- confira o relatório antes de rodar com o prazo real.

\if :{?dias}
\else
  \set dias 180
\endif

BEGIN;

-- 1+2. agrega e apaga, na mesma transação: ou as duas coisas acontecem, ou nenhuma
WITH prazo AS (
    SELECT (now() - (:dias || ' days')::interval) AS corte
), velho AS (
    SELECT (date_trunc('month', h.criado_em AT TIME ZONE 'America/Sao_Paulo'))::date AS mes,
           h.acao, h.entidade, count(*) AS quantidade
      FROM historico h, prazo p
     WHERE h.criado_em < p.corte
       AND h.acao <> 'expurgar'          -- o registro do próprio expurgo não se agrega
     GROUP BY 1, 2, 3
), gravado AS (
    INSERT INTO historico_mensal AS hm (mes, acao, entidade, quantidade)
    SELECT mes, acao, entidade, quantidade FROM velho
    ON CONFLICT (mes, acao, entidade)
    DO UPDATE SET quantidade = hm.quantidade + EXCLUDED.quantidade
    RETURNING 1
), apagado AS (
    DELETE FROM historico h
     USING prazo p
     WHERE h.criado_em < p.corte
       AND h.acao <> 'expurgar'
    RETURNING 1
)
SELECT (SELECT count(*) FROM gravado) AS meses_gravados,
       (SELECT count(*) FROM apagado) AS linhas_apagadas
\gset

INSERT INTO historico (acao, entidade, depois)
VALUES ('expurgar', 'contato',
        jsonb_build_object(
          'dias', :dias,
          'linhas_apagadas', :'linhas_apagadas'::bigint,
          'agregados_atualizados', :'meses_gravados'::bigint));

COMMIT;

\echo
\echo 'Expurgo do histórico —'
SELECT :dias AS dias_de_retencao,
       :'linhas_apagadas'::bigint AS linhas_apagadas,
       :'meses_gravados'::bigint AS agregados_atualizados;

\echo 'O que sobrou, por mês:'
SELECT mes, acao, entidade, quantidade
  FROM historico_mensal
 ORDER BY mes DESC, acao, entidade
 LIMIT 20;
