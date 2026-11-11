-- =============================================================================
--  db/verificar-carga.sql — invariantes sobre o seed carregado
--  Rodar DEPOIS de db/carregar-seed.py, no mesmo banco.
--  Uso: db/testar-schema.sh (com SEED_JSON=... ) ou
--       psql "$URL" -v ON_ERROR_STOP=1 -f db/verificar-carga.sql
--  Os números abaixo são os medidos no seed do v2 (HEAD d9a011a) e conferidos
--  contra os dois volumes do catálogo e o ofício de março/2024.
-- =============================================================================
BEGIN;

CREATE OR REPLACE FUNCTION _confere(rotulo TEXT, obtido BIGINT, esperado BIGINT)
RETURNS void LANGUAGE plpgsql AS $$
BEGIN
  IF obtido <> esperado THEN
    RAISE EXCEPTION 'FALHOU %: obtido %, esperado %', rotulo, obtido, esperado;
  END IF;
  RAISE NOTICE 'PASS  % = %', rotulo, obtido;
END $$;

DO $$
DECLARE n BIGINT;
BEGIN
  -- 1. volume
  PERFORM _confere('comarcas (MUN0000 não é comarca)', (SELECT count(*) FROM comarcas), 69);
  PERFORM _confere('locais (72 fóruns + 1 Sede)',        (SELECT count(*) FROM locais), 73);
  PERFORM _confere('locais do tipo FORUM',               (SELECT count(*) FROM locais WHERE tipo = 'FORUM'), 72);
  PERFORM _confere('nós em lista única',                 (SELECT count(*) FROM nos), 838);
  PERFORM _confere('nós de primeiro nível',              (SELECT count(*) FROM nos WHERE pai_id IS NULL), 226);
  PERFORM _confere('contatos',                           (SELECT count(*) FROM contatos), 1807);
  PERFORM _confere('telefones úteis',                    (SELECT count(*) FROM telefones_uteis), 11);

  -- 2. vínculo: 626 blocos de local dos fóruns + 5 da Sede = 631 sem nó
  PERFORM _confere('contatos do local (no_id IS NULL)',  (SELECT count(*) FROM contatos WHERE no_id IS NULL), 631);
  PERFORM _confere('contatos ligados a nó',              (SELECT count(*) FROM contatos WHERE no_id IS NOT NULL), 1176);
  PERFORM _confere('contatos da Sede',                   (SELECT count(*) FROM contatos c
                                                            JOIN locais o ON o.id = c.local_id
                                                           WHERE o.tipo = 'SEDE'), 286);

  -- 3. coerência que o CHECK não enxerga (validação de aplicação)
  PERFORM _confere('contato com local diferente do local do nó',
                   (SELECT count(*) FROM contatos c
                      JOIN nos n ON n.id = c.no_id
                     WHERE n.local_id <> c.local_id), 0);
  PERFORM _confere('nós com avô (profundidade > 2 abaixo do local)',
                   (SELECT count(*) FROM nos n JOIN nos p ON p.id = n.pai_id
                     WHERE p.pai_id IS NOT NULL), 0);
  PERFORM _confere('nós órfãos de local',                (SELECT count(*) FROM nos WHERE local_id IS NULL), 0);

  -- 4. procedência: as três origens documentadas, sem sobra nem falta
  PERFORM _confere('contatos do ofício de março/2024',        (SELECT count(*) FROM contatos WHERE fonte LIKE 'oficio-2024-03%'), 43);
  PERFORM _confere('contatos do volume da Sede',         (SELECT count(*) FROM contatos WHERE fonte LIKE 'catalogo-sede-%'), 286);
  PERFORM _confere('contatos da base sem documento declarado',
                   (SELECT count(*) FROM contatos WHERE fonte LIKE 'seed%'), 1478);
  PERFORM _confere('contatos sem procedência',           (SELECT count(*) FROM contatos WHERE fonte = ''), 0);
  PERFORM _confere('ofício da fonte com data de março/2024',
                   (SELECT count(*) FROM contatos WHERE fonte LIKE 'oficio-2024-03%' AND vigente_desde = DATE '2024-03-01'), 43);

  -- 5. busca sobre a base mascarada (é o que o demo público vai servir)
  PERFORM _confere('busca sem acento acha "Cartório"',
                   (SELECT count(*) FROM contatos WHERE busca_texto LIKE '%' || norm_texto('cartorio') || '%'), 68);
  PERFORM _confere('busca por prefixo de dígito acha telefone mascarado em 3134',
                   (SELECT count(*) FROM contatos WHERE busca_digitos LIKE '273134%'), 17);
END $$;

DROP FUNCTION IF EXISTS _confere(TEXT, BIGINT, BIGINT);

COMMIT;
\echo '== CARGA OK =='
