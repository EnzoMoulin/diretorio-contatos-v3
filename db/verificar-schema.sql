-- =============================================================================
--  Verificação executável do DDL — db/verificar-schema.sql
--  Roda contra um banco VAZIO, já com db/schema.sql aplicado.
--  Uso: db/testar-schema.sh            (cria banco temporário e chama os dois)
--       psql "$DATABASE_URL" -v ON_ERROR_STOP=1 -f db/verificar-schema.sql
--  Falha com exceção na primeira invariante quebrada; imprime PASS em cada
--  invariante cumprida. Ao fim, apaga os dados de teste (o schema fica).
-- =============================================================================

BEGIN;

-- ---------------------------------------------------------------------------
-- helpers
-- ---------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION _passa(rotulo TEXT) RETURNS void
LANGUAGE plpgsql AS $$
BEGIN
  RAISE NOTICE 'PASS  %', rotulo;
END $$;

CREATE OR REPLACE FUNCTION _espera_erro(rotulo TEXT, cmd TEXT, sqlstate_alvo TEXT)
RETURNS void
LANGUAGE plpgsql AS $$
BEGIN
  BEGIN
    EXECUTE cmd;
  EXCEPTION WHEN OTHERS THEN
    IF SQLSTATE <> sqlstate_alvo THEN
      RAISE EXCEPTION 'FALHOU %: erro %, esperado % (%)', rotulo, SQLSTATE, sqlstate_alvo, SQLERRM;
    END IF;
    PERFORM _passa(rotulo || '  [' || SQLSTATE || ']');
    RETURN;
  END;
  RAISE EXCEPTION 'FALHOU %: comando passou e devia falhar com %', rotulo, sqlstate_alvo;
END $$;

CREATE OR REPLACE FUNCTION _espera_ok(rotulo TEXT, cmd TEXT) RETURNS void
LANGUAGE plpgsql AS $$
BEGIN
  EXECUTE cmd;
  PERFORM _passa(rotulo);
EXCEPTION WHEN OTHERS THEN
  RAISE EXCEPTION 'FALHOU %: %', rotulo, SQLERRM;
END $$;

-- ---------------------------------------------------------------------------
-- 0. schema aplicado duas vezes continua válido (idempotência do arquivo)
--    — o teste real está em db/testar-schema.sh; aqui só conferimos as tabelas
-- ---------------------------------------------------------------------------
DO $$
DECLARE faltando TEXT;
BEGIN
  SELECT string_agg(t, ', ') INTO faltando
  FROM unnest(ARRAY['comarcas', 'locais', 'nos', 'contatos', 'telefones_uteis',
                    'historico', 'historico_mensal']) AS t
  WHERE to_regclass('public.' || t) IS NULL;
  IF faltando IS NOT NULL THEN
    RAISE EXCEPTION 'FALHOU: tabelas ausentes: %', faltando;
  END IF;
  PERFORM _passa('as 7 tabelas do baseline existem');
END $$;

-- ---------------------------------------------------------------------------
-- 1. dados de apoio
-- ---------------------------------------------------------------------------
INSERT INTO comarcas (chave, nome, codigo_ibge, ordem_num, ordem_idx)
VALUES ('MUN0001', 'Vitória', '3205309', 1, 0),
       ('MUN0002', 'Vila Velha', '3205200', 2, 1);

INSERT INTO locais (chave, tipo, comarca_id, nome, email)
VALUES ('FOR0001', 'FORUM', (SELECT id FROM comarcas WHERE chave = 'MUN0001'), 'Fórum Cível', 'vitoria@exemplo.jus.br'),
       ('FOR0002', 'FORUM', (SELECT id FROM comarcas WHERE chave = 'MUN0001'), 'Fórum Criminal', ''),
       ('MUN0000', 'SEDE',  NULL, 'Sede Administrativa', '');

INSERT INTO nos (chave, local_id, pai_id, tipo, nome)
VALUES ('UNI0001', (SELECT id FROM locais WHERE chave = 'FOR0002'), NULL, 'UNIDADE', '7ª Secretaria Unificada'),
       ('UNI0066', (SELECT id FROM locais WHERE chave = 'FOR0002'), NULL, 'SETOR', '1ª Contadoria');

INSERT INTO nos (chave, local_id, pai_id, tipo, nome)
VALUES ('UNI0064', (SELECT id FROM locais WHERE chave = 'FOR0002'),
        (SELECT id FROM nos WHERE chave = 'UNI0001'), 'VARA', '1ª Vara Criminal');

-- contatos: as quatro grafias de telefone do ofício de março/2024
INSERT INTO contatos (chave, local_id, no_id, tipo, descricao, valor, fonte, vigente_desde)
VALUES ('C000001', (SELECT id FROM locais WHERE chave = 'FOR0002'),
        (SELECT id FROM nos WHERE chave = 'UNI0064'), 'Telefone', 'Institucional', '(27) 99111-0001',
        'oficio-2024-03.pdf', '2024-03-01'),
       ('C000002', (SELECT id FROM locais WHERE chave = 'FOR0002'),
        (SELECT id FROM nos WHERE chave = 'UNI0064'), 'Telefone', 'Institucional', '(27) 991110002',
        'oficio-2024-03.pdf', '2024-03-01'),
       ('C000003', (SELECT id FROM locais WHERE chave = 'FOR0002'),
        (SELECT id FROM nos WHERE chave = 'UNI0064'), 'Telefone', 'Institucional', '(27) 33110003',
        'oficio-2024-03.pdf', '2024-03-01'),
       ('C000004', (SELECT id FROM locais WHERE chave = 'FOR0002'),
        (SELECT id FROM nos WHERE chave = 'UNI0064'), 'Telefone', 'Institucional', '(27) 99110.0004',
        'oficio-2024-03.pdf', '2024-03-01'),
       -- dois e-mails no mesmo nó, separados por descricao (2º JECrim)
       ('C000005', (SELECT id FROM locais WHERE chave = 'FOR0002'),
        (SELECT id FROM nos WHERE chave = 'UNI0064'), 'E-mail', 'Gabinete', 'gabinete3jecrimdevitoria@exemplo.jus.br',
        'oficio-2024-03.pdf', '2024-03-01'),
       ('C000006', (SELECT id FROM locais WHERE chave = 'FOR0002'),
        (SELECT id FROM nos WHERE chave = 'UNI0064'), 'E-mail', 'Cartório', '3jecriminal-vitoria@exemplo.jus.br',
        'oficio-2024-03.pdf', '2024-03-01'),
       -- contato DO ÓRGÃO: no_id NULL é válido e não é dado incompleto
       ('C000007', (SELECT id FROM locais WHERE chave = 'FOR0002'), NULL, 'Telefone', 'Geral', '(27) 3134-0007',
        'catalogo-comarcas-2026-06-26.pdf', '2026-06-25');

-- ---------------------------------------------------------------------------
-- 2. colunas derivadas
-- ---------------------------------------------------------------------------
DO $$
DECLARE d TEXT; t TEXT;
BEGIN
  SELECT busca_digitos, busca_texto INTO d, t FROM contatos WHERE chave = 'C000001';
  IF d <> '27991110001' THEN RAISE EXCEPTION 'busca_digitos errada: %', d; END IF;
  IF t NOT LIKE '%99111%' THEN RAISE EXCEPTION 'busca_texto errada: %', t; END IF;
  PERFORM _passa('busca_digitos/busca_texto são calculadas na escrita');

  SELECT busca_digitos INTO d FROM contatos WHERE chave = 'C000004';
  IF d <> '27991100004' THEN RAISE EXCEPTION 'ponto no telefone não normalizou: %', d; END IF;
  PERFORM _passa('grafia com ponto (27) 99110.0004 vira 27991100004');

  SELECT busca_digitos INTO d FROM contatos WHERE chave = 'C000003';
  IF length(d) <> 10 THEN RAISE EXCEPTION 'fixo de 8 dígitos virou %', d; END IF;
  PERFORM _passa('fixo sem hífen (27) 33110003 vira 10 dígitos');
END $$;

-- ---------------------------------------------------------------------------
-- 3. busca (4.5): dígito parcial e trigrama sem acento
-- ---------------------------------------------------------------------------
DO $$
DECLARE n INT;
BEGIN
  SELECT count(*) INTO n FROM contatos WHERE busca_digitos LIKE '2799%';
  IF n <> 3 THEN RAISE EXCEPTION 'prefixo 2799 devia achar 3 móveis, achou %', n; END IF;
  PERFORM _passa('busca por prefixo de DDD+9 acha os três móveis');

  SELECT count(*) INTO n FROM contatos WHERE busca_digitos LIKE '%9110%';
  IF n <> 1 THEN RAISE EXCEPTION 'trecho 9110 devia achar 1, achou %', n; END IF;
  PERFORM _passa('trecho no meio do número acha a grafia com ponto');

  SELECT count(*) INTO n FROM contatos
   WHERE busca_texto ILIKE '%' || norm_texto('CARtorio') || '%';
  IF n <> 1 THEN RAISE EXCEPTION 'busca sem acento/caixa devia achar 1, achou %', n; END IF;
  PERFORM _passa('busca sem acento e sem caixa acha "Cartório"');

  SELECT count(*) INTO n FROM locais WHERE norm_texto(nome) LIKE norm_texto('%forum crim%') ;
  IF n <> 1 THEN RAISE EXCEPTION 'trigrama em locais achou %', n; END IF;
  PERFORM _passa('norm_texto serve para qualquer tabela, não só contatos');
END $$;

-- ---------------------------------------------------------------------------
-- 4. invariantes de local e nó
-- ---------------------------------------------------------------------------
DO $$
DECLARE cid BIGINT := (SELECT id FROM comarcas WHERE chave = 'MUN0001');
BEGIN
  PERFORM _espera_erro('fórum sem comarca é recusado',
    $q$INSERT INTO locais (chave, tipo, comarca_id, nome) VALUES ('FOR0100','FORUM',NULL,'X')$q$, '23514');
  PERFORM _espera_erro('Sede com comarca é recusada',
    format($q$INSERT INTO locais (chave, tipo, comarca_id, nome) VALUES ('LOC-01J0000000000000000000000','SEDE',%s,'X')$q$, cid), '23514');
  PERFORM _espera_erro('chave de local fora do formato é recusada',
    $q$INSERT INTO locais (chave, tipo, comarca_id, nome) VALUES ('XX1','SEDE',NULL,'X')$q$, '23514');
  PERFORM _espera_erro('dois fóruns homônimos na mesma comarca são recusados',
    format($q$INSERT INTO locais (chave, tipo, comarca_id, nome) VALUES ('FOR0101','FORUM',%s,'Fórum Cível')$q$, cid), '23505');
  PERFORM _espera_ok('nome igual em comarca diferente é permitido',
    format($q$INSERT INTO locais (chave, tipo, comarca_id, nome) VALUES ('FOR0101','FORUM',%s,'Fórum Cível')$q$,
           (SELECT id FROM comarcas WHERE chave = 'MUN0002')));

  PERFORM _espera_erro('nó sem local é recusado',
    $q$INSERT INTO nos (chave, local_id, nome) VALUES ('UNI0900', NULL, 'X')$q$, '23502');
  PERFORM _espera_erro('irmãos homônimos são recusados',
    $q$INSERT INTO nos (chave, local_id, nome) VALUES ('UNI0901', (SELECT id FROM locais WHERE chave='FOR0002'), '1ª Contadoria')$q$, '23505');
  PERFORM _espera_ok('nó novo entra com pai nulo',
    $q$INSERT INTO nos (chave, local_id, nome) VALUES ('UNI0902',(SELECT id FROM locais WHERE chave='FOR0002'),'Temporário')$q$);
  PERFORM _espera_erro('nó apontando para si mesmo é recusado',
    $q$UPDATE nos SET pai_id = id WHERE chave = 'UNI0902'$q$, '23514');
END $$;

-- ---------------------------------------------------------------------------
-- 5. invariantes de contato
-- ---------------------------------------------------------------------------
DO $$
DECLARE oid2 BIGINT := (SELECT id FROM locais WHERE chave='FOR0002');
BEGIN
  PERFORM _espera_erro('e-mail sem arroba é recusado',
    format($q$INSERT INTO contatos (chave, local_id, tipo, valor) VALUES ('C000900',%s,'E-mail','sem-arroba')$q$, oid2), '23514');
  PERFORM _espera_erro('telefone com letras é recusado',
    format($q$INSERT INTO contatos (chave, local_id, tipo, valor) VALUES ('C000901',%s,'Telefone','(27) 9abc-0000')$q$, oid2), '23514');
  PERFORM _espera_erro('contato sem valor é recusado',
    format($q$INSERT INTO contatos (chave, local_id, tipo, valor) VALUES ('C000902',%s,'Telefone','   ')$q$, oid2), '23514');
  PERFORM _espera_erro('contato sem local é recusado',
    $q$INSERT INTO contatos (chave, tipo, valor) VALUES ('C000903','Telefone','(27) 3333-0000')$q$, '23502');
  PERFORM _espera_erro('tipo de contato fora da lista é recusado',
    format($q$INSERT INTO contatos (chave, local_id, tipo, valor) VALUES ('C000904',%s,'Pombo','x')$q$, oid2), '23514');
  PERFORM _espera_erro('chave de contato duplicada é recusada',
    format($q$INSERT INTO contatos (chave, local_id, tipo, valor) VALUES ('C000001',%s,'Telefone','(27) 3333-0001')$q$, oid2), '23505');
  PERFORM _espera_ok('dois e-mails no mesmo nó com descrições diferentes convivem',
    format($q$INSERT INTO contatos (chave, local_id, no_id, tipo, descricao, valor)
              VALUES ('C000905',%s,(SELECT id FROM nos WHERE chave='UNI0064'),'E-mail','Assessoria','assessoria@exemplo.jus.br')$q$, oid2));
END $$;

-- ---------------------------------------------------------------------------
-- 6. exclusão: sem cascata (LGPD 5.3/5.4) — FK protege
-- ---------------------------------------------------------------------------
DO $$
BEGIN
  PERFORM _espera_erro('apagar local com nós é bloqueado',
    $q$DELETE FROM locais WHERE chave = 'FOR0002'$q$, '23503');
  PERFORM _espera_erro('apagar nó com contato é bloqueado',
    $q$DELETE FROM nos WHERE chave = 'UNI0064'$q$, '23503');
  PERFORM _espera_erro('apagar comarca com fórum é bloqueado',
    $q$DELETE FROM comarcas WHERE chave = 'MUN0001'$q$, '23503');
END $$;

-- ---------------------------------------------------------------------------
-- 7. arquivamento: pai arquivado não toca nos filhos (5.3)
-- ---------------------------------------------------------------------------
DO $$
DECLARE antes INT;
BEGIN
  SELECT count(*) INTO antes FROM nos WHERE arquivado_em IS NOT NULL;
  UPDATE nos SET arquivado_em = now(), arquivado_por = 'usr_00042:admin',
                 arquivado_motivo = 'teste'
   WHERE chave = 'UNI0001';
  IF (SELECT count(*) FROM nos WHERE arquivado_em IS NOT NULL) <> antes + 1 THEN
    RAISE EXCEPTION 'arquivar pai marcou os filhos (devia marcar 1 linha)';
  END IF;
  PERFORM _passa('arquivar pai marca 1 linha; filhos intactos');
  UPDATE nos SET arquivado_em = NULL, arquivado_por = '', arquivado_motivo = ''
   WHERE chave = 'UNI0001';
  PERFORM _passa('restaurar pai devolve a subárvore ao estado anterior');
END $$;

-- ---------------------------------------------------------------------------
-- 8. atualizado_em automático
-- ---------------------------------------------------------------------------
DO $$
DECLARE dentro_ref TIMESTAMPTZ;
BEGIN
  -- dentro de uma transação `now()` não anda (é o início da transação), então o
  -- teste força um valor antigo com o trigger desligado e confere que o UPDATE
  -- seguinte devolve a coluna para o "agora".
  ALTER TABLE contatos DISABLE TRIGGER tg_contatos_atualiza;
  UPDATE contatos SET atualizado_em = '2000-01-01 00:00:00+00' WHERE chave = 'C000007';
  ALTER TABLE contatos ENABLE TRIGGER tg_contatos_atualiza;
  SELECT atualizado_em INTO dentro_ref FROM contatos WHERE chave = 'C000007';
  IF dentro_ref > '2000-01-02' THEN
    RAISE EXCEPTION 'preparo do teste falhou: atualizado_em = %', dentro_ref;
  END IF;
  UPDATE contatos SET descricao = 'Geral 2' WHERE chave = 'C000007';
  IF (SELECT atualizado_em FROM contatos WHERE chave = 'C000007') <= '2000-01-02' THEN
    RAISE EXCEPTION 'trigger de atualizado_em não disparou';
  END IF;
  PERFORM _passa('trigger mantém atualizado_em');
END $$;

-- ---------------------------------------------------------------------------
-- 9. histórico, procedência e idempotência
-- ---------------------------------------------------------------------------
DO $$
BEGIN
  INSERT INTO historico (acao, entidade, entidade_chave, antes, depois, autor, request_id)
  VALUES ('remover', 'contato', 'C000001',
          '{"descricao": "Institucional", "valor": "(27) 99111-0001"}'::jsonb, NULL,
          'usr_00042:admin', '0193f0c2-0000-7000-8000-000000000001');
  PERFORM _passa('histórico aceita autor opaco + request_id');

  PERFORM _espera_erro('autor com CRLF é recusado no histórico',
    $q$INSERT INTO historico (acao, autor) VALUES ('criar', 'usr_1:admin' || chr(13) || chr(10) || 'X: y')$q$, '23514');

  PERFORM _espera_erro('histórico sem antes nem depois é recusado',
    $q$INSERT INTO historico (acao, entidade, entidade_chave) VALUES ('atualizar', 'contato', 'C000001')$q$, '23514');

  PERFORM _espera_ok('histórico aceita entidade util',
    $q$INSERT INTO historico (acao, entidade, entidade_chave, depois)
       VALUES ('atualizar', 'util', 'UTL0001', '{"valor": "(27) 3232-0000"}'::jsonb)$q$);
  PERFORM _espera_erro('histórico recusa entidade de auditoria administrativa',
    $q$INSERT INTO historico (acao, entidade, depois) VALUES ('criar', 'escopo_acesso', '{}'::jsonb)$q$, '23514');

  PERFORM _espera_ok('procedência consultável por fonte',
    $q$INSERT INTO telefones_uteis (chave, nome, valor, fonte, vigente_desde)
       VALUES ('UTL0001','Central de ajuda','(27) 3232-0000','catalogo-comarcas-2026-06-26.pdf','2026-06-25')$q$);

END $$;

-- ---------------------------------------------------------------------------
-- 10. consultas do contrato (4.4) usando só chaves
-- ---------------------------------------------------------------------------
DO $$
DECLARE n INT;
BEGIN
  SELECT count(*) INTO n FROM contatos c JOIN locais o ON o.id = c.local_id
   WHERE o.chave = 'FOR0002' AND c.no_id IS NULL;
  IF n <> 1 THEN RAISE EXCEPTION 'contatos do local deviam ser 1, foram %', n; END IF;
  PERFORM _passa('/contatos?local= só com no_id NULL devolve o bloco do local');

  -- a consulta de escopo não precisa mais do COALESCE do v2
  SELECT count(*) INTO n FROM contatos c
    JOIN locais o ON o.id = c.local_id
    JOIN comarcas m ON m.id = o.comarca_id
   WHERE m.chave = 'MUN0001';
  IF n <> 7 + 1 THEN RAISE EXCEPTION 'escopo Vitória devia somar 8, somou %', n; END IF;
  PERFORM _passa('escopo por comarca resolve por local.comarca_id, sem COALESCE');

  SELECT count(*) INTO n FROM (
    WITH RECURSIVE sub AS (
      SELECT id, arquivado_em FROM nos WHERE chave = 'UNI0001'
      UNION ALL
      SELECT f.id, f.arquivado_em FROM nos f JOIN sub s ON f.pai_id = s.id
    ) SELECT bool_or(arquivado_em IS NOT NULL) FROM sub) x;
  IF n <> 1 THEN RAISE EXCEPTION 'subárvore visível devia ser 1 linha'; END IF;
  PERFORM _passa('invisibilidade por subárvore roda como em 5.3');
END $$;

-- ---------------------------------------------------------------------------
-- limpeza: o schema fica, os dados de teste saem
-- ---------------------------------------------------------------------------
TRUNCATE historico, telefones_uteis, contatos, nos, locais, comarcas
  RESTART IDENTITY CASCADE;

DROP FUNCTION IF EXISTS _passa(TEXT);
DROP FUNCTION IF EXISTS _espera_erro(TEXT, TEXT, TEXT);
DROP FUNCTION IF EXISTS _espera_ok(TEXT, TEXT);

COMMIT;

\echo ''
\echo '== TODAS AS INVARIANTES OK =='
