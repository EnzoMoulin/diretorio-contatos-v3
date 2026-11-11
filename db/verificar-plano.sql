-- -----------------------------------------------------------------------------
-- verificar-plano.sql — o índice de busca é usado pelo MOTOR, não só existe
-- -----------------------------------------------------------------------------
-- Ter `ix_contatos_busca_trgm` criado não prova nada: se a consulta escrever
-- `lower(unaccent(...))` em vez de `norm_texto(...)`, ou procurar em `valor` em
-- vez de `busca_texto`, o planejador não acha o índice e a busca vira varredura
-- sequencial — funciona, e fica lenta no dia em que a base crescer.
--
-- Aqui o plano é lido e conferido. Roda depois de carregar o seed:
--   psql "$DATABASE_URL" -v ON_ERROR_STOP=1 -f db/verificar-plano.sql
--
-- Nota honesta, medida no seed (1.807 contatos): com as configurações de
-- fábrica o planejador alterna — `cartorio` sai por Bitmap Index Scan (68
-- linhas), `vara` (65) e `de` (291) saem por Seq Scan. Nos dois casos o
-- resultado está certo e o tempo é o mesmo nesta escala. O que os testes abaixo
-- provam é que o índice SERVE à consulta (desligando `enable_seqscan`), que é o
-- que decide o comportamento quando a base crescer; não que ele vença hoje.
DO $$
DECLARE
    plano text;
    r     record;
    falhas int := 0;
    n      int;
BEGIN
    -- 1. o índice existe, é GIN com gin_trgm_ops e está válido -----------------
    SELECT count(*) INTO n
      FROM pg_index i
      JOIN pg_class c  ON c.oid = i.indexrelid
      JOIN pg_class t  ON t.oid = i.indrelid
      JOIN pg_am   am ON am.oid = c.relam
     WHERE t.relname = 'contatos'
       AND c.relname = 'ix_contatos_busca_trgm'
       AND am.amname = 'gin'
       AND i.indisvalid;
    IF n = 1 THEN
        RAISE NOTICE 'PASS  índice GIN trgm em contatos existe e é válido';
    ELSE
        RAISE NOTICE 'FALHOU  índice GIN trgm em contatos ausente ou inválido';
        falhas := falhas + 1;
    END IF;

    -- 2. busca textual com norm_texto USA o índice -----------------------------
    -- é exatamente a forma que o motor deve escrever no WHERE
    SET LOCAL enable_seqscan = off;
    plano := '';
    FOR r IN EXECUTE
        $q$EXPLAIN (COSTS OFF)
           SELECT id FROM contatos
            WHERE busca_texto ILIKE '%' || norm_texto('vitoria') || '%'$q$
    LOOP
        plano := plano || r."QUERY PLAN" || E'\n';
    END LOOP;
    IF plano LIKE '%ix_contatos_busca_trgm%' THEN
        RAISE NOTICE 'PASS  busca_texto ILIKE norm_texto(...) usa o índice trgm';
    ELSE
        RAISE NOTICE 'FALHOU  busca textual não usa o índice. Plano:%', E'\n' || plano;
        falhas := falhas + 1;
    END IF;

    -- 3. a forma "ingênua" NÃO usa: é o erro que este teste existe para pegar -
    -- lower(unaccent(valor)) não é a expressão indexada (busca_texto é), então
    -- o planejador não tem como usar o GIN.
    plano := '';
    FOR r IN EXECUTE
        $q$EXPLAIN (COSTS OFF)
           SELECT id FROM contatos
            WHERE lower(public.unaccent(valor)) ILIKE '%' || norm_texto('vitoria') || '%'$q$
    LOOP
        plano := plano || r."QUERY PLAN" || E'\n';
    END LOOP;
    IF plano NOT LIKE '%ix_contatos_busca_trgm%' THEN
        RAISE NOTICE 'PASS  lower(unaccent(valor)) NÃO usa o índice (por isso existe norm_texto + coluna gerada)';
    ELSE
        RAISE NOTICE 'FALHOU  a forma ingênua usou o índice — o teste perdeu o sentido';
        falhas := falhas + 1;
    END IF;

    -- 4. telefone por dígitos usa o índice de prefixo --------------------------
    plano := '';
    FOR r IN EXECUTE
        $q$EXPLAIN (COSTS OFF)
           SELECT id FROM contatos WHERE busca_digitos LIKE '273134%'$q$
    LOOP
        plano := plano || r."QUERY PLAN" || E'\n';
    END LOOP;
    IF plano LIKE '%ix_contatos_busca_dig%' THEN
        RAISE NOTICE 'PASS  busca_digitos LIKE ''273134%%'' usa o índice text_pattern_ops';
    ELSE
        RAISE NOTICE 'FALHOU  busca por dígitos não usa o índice. Plano:%', E'\n' || plano;
        falhas := falhas + 1;
    END IF;

    -- 5. sanidade: a busca devolve o que deve, e não devolve o que não deve ----
    SELECT count(*) INTO n FROM contatos
     WHERE busca_texto ILIKE '%' || norm_texto('cartorio') || '%';
    IF n > 0 THEN
        RAISE NOTICE 'PASS  norm_texto(''cartorio'') acha % contatos sem acento', n;
    ELSE
        RAISE NOTICE 'FALHOU  busca sem acento não achou nada';
        falhas := falhas + 1;
    END IF;

    -- 6. e o plano real, com as configurações de fábrica (informativo) ---------
    plano := '';
    FOR r IN EXECUTE
        $q$EXPLAIN (ANALYZE, COSTS OFF, TIMING OFF, SUMMARY OFF)
           SELECT id FROM contatos
            WHERE busca_texto ILIKE '%' || norm_texto('cartorio') || '%'$q$
    LOOP
        plano := plano || r."QUERY PLAN" || E'\n';
    END LOOP;
    RAISE NOTICE 'INFO  plano com as configurações de fábrica (1.807 linhas):%', E'\n' || plano;

    IF falhas > 0 THEN
        RAISE EXCEPTION '% invariante(s) de plano falharam', falhas;
    END IF;
    RAISE NOTICE '== PLANO OK ==';
END $$;
