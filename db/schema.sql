-- =============================================================================
--  Diretório de Contatos — v3 · DDL do diretório
--  PostgreSQL 16 (Neon). Na v3 é o baseline das migrations do Laravel (ADR-010:
--  backend único em Laravel, sem serviço FastAPI).
--  Documento de origem (histórico): docs/fase-3-arquitetura-e-adrs.md (seções 4.4, 4.5, 5.2, 5.6, 6.3)
--  Aplicar:  psql "$DATABASE_URL" -1 -v ON_ERROR_STOP=1 -f db/schema.sql
--  Testar:   db/testar-schema.sh   (cria banco temporário e roda as invariantes)
-- -----------------------------------------------------------------------------
--  O arquivo NÃO abre BEGIN nem COMMIT: ele é o corpo da migration baseline e a
--  transação pertence a quem aplica (`psql -1` ou o migrator do Laravel). Um
--  COMMIT aqui encerraria a transação do migrator e o rollback seguinte falharia
--  com "There is no active transaction", deixando o DDL aplicado.
--  O que este arquivo NÃO faz:
--   - não importa o seed (é a carga do item 2 do v3.0, com normalização e `fonte`);
--   - não cria papéis nem GRANTs (secção 10 traz o modelo comentado);
--   - não contém identidade, permissão, pedidos nem auditoria administrativa:
--     são tabelas do app, criadas por migrations do Laravel no MESMO banco
--     (ADR-010). `db_admin` é desenho de dois serviços, retirado em 25/11/2026;
--   - não tem `referencias_externas` nem `idempotencia`: as duas existiam para o
--     motor separado (espelho de referência e Idempotency-Key de escrita). Com
--     um backend só a API é de leitura e o expurgo consulta o app direto.
--  O que o banco garante sozinho: integridade referencial, unicidade de chave
--  lógica, `tipo FORUM ⇔ comarca`, unicidade de nome entre irmãos, formato de
--  contato e as colunas derivadas de busca. O que fica na aplicação (porque
--  `CHECK` não enxerga outra linha): `nos.local_id = pai.local_id`,
--  `contatos.local_id = no.local_id`, profundidade ≤ 2 e unicidade de
--  `(local, nó, valor)` de contato.
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1. Extensões e normalização
-- -----------------------------------------------------------------------------
-- `unaccent` sozinho NÃO serve em coluna gerada: `public.unaccent(text)` é
-- STABLE, e o PostgreSQL recusa (`generation expression is not immutable`).
-- A função abaixo é o invólucro IMMUTABLE que torna 4.5 executável; é o mesmo
-- texto que entra no índice e o mesmo que a aplicação aplica na consulta.
CREATE EXTENSION IF NOT EXISTS unaccent;
CREATE EXTENSION IF NOT EXISTS pg_trgm;

CREATE OR REPLACE FUNCTION norm_texto(txt TEXT)
RETURNS TEXT
LANGUAGE sql
IMMUTABLE
STRICT
PARALLEL SAFE
AS $$
  SELECT lower(public.unaccent('public.unaccent'::regdictionary, txt))
$$;

COMMENT ON FUNCTION norm_texto(TEXT) IS
  'Normalização de busca: sem acento, minúscula. IMMUTABLE para uso em coluna gerada e índice GIN.';

CREATE OR REPLACE FUNCTION so_digitos(txt TEXT)
RETURNS TEXT
LANGUAGE sql
IMMUTABLE
STRICT
PARALLEL SAFE
AS $$
  SELECT regexp_replace(coalesce(txt, ''), '\D', '', 'g')
$$;

COMMENT ON FUNCTION so_digitos(TEXT) IS
  'Só os dígitos do valor. Usada na coluna busca_digitos e na consulta por telefone.';

CREATE OR REPLACE FUNCTION marca_atualizacao()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
BEGIN
  NEW.atualizado_em := now();
  RETURN NEW;
END;
$$;

-- -----------------------------------------------------------------------------
-- 2. comarcas — dimensão geográfica (NÃO é nível da árvore)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS comarcas (
  id             BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  chave          TEXT NOT NULL UNIQUE,          -- MUN0001 | MUN-<ULID>
  nome           TEXT NOT NULL UNIQUE,
  codigo_ibge    TEXT NOT NULL DEFAULT '',
  ordem_num      REAL,
  ordem_idx      INTEGER NOT NULL DEFAULT 0,
  arquivado_em     TIMESTAMPTZ,
  arquivado_por    TEXT NOT NULL DEFAULT '',
  arquivado_motivo TEXT NOT NULL DEFAULT '',
  criado_em      TIMESTAMPTZ NOT NULL DEFAULT now(),
  atualizado_em  TIMESTAMPTZ NOT NULL DEFAULT now(),
  CONSTRAINT comarca_chave_formato  CHECK (chave ~ '^MUN[0-9]{4}$|^MUN-[0-9A-HJKMNP-TV-Z]{26}$'),
  CONSTRAINT comarca_nome_preenchido CHECK (btrim(nome) <> ''),
  CONSTRAINT comarca_ibge_formato  CHECK (codigo_ibge = '' OR codigo_ibge ~ '^[0-9]{7}$')
);

COMMENT ON TABLE comarcas IS
  '69 comarcas do seed (MUN0000 sai: a Sede é órgão, não comarca). Nome único no estado.';
COMMENT ON COLUMN comarcas.chave IS
  'Chave lógica imutável. Nunca expor `id`: o motor e o Laravel falam por chave.';

-- -----------------------------------------------------------------------------
-- 3. locais — nível de ancoragem da árvore: fórum (tem comarca) ou Sede (não tem)
-- -----------------------------------------------------------------------------
-- Vocabulário: **fórum não é órgão**. O fórum é o prédio onde as varas estão
-- instaladas; o órgão é a Sede (e, na linguagem do ofício de março/2024, as unidades
-- administrativas — diretoria, contadoria). Os dois ocupam o mesmo nível da
-- hierarquia, então a tabela se chama `locais`, com `tipo` dizendo qual é qual.
-- Reservar "órgão" só para a Sede evita o erro de ler `locais` como "lista de
-- órgãos" e de chamar a vara de "órgão do fórum".
CREATE TABLE IF NOT EXISTS locais (
  id             BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  chave          TEXT NOT NULL UNIQUE,          -- FOR0001 | MUN0000 (Sede, legado) | LOC-<ULID>
  tipo           TEXT NOT NULL CHECK (tipo IN ('FORUM', 'SEDE')),
  comarca_id     BIGINT REFERENCES comarcas(id),   -- NULL só na Sede
  nome           TEXT NOT NULL,
  endereco       TEXT NOT NULL DEFAULT '',
  cep            TEXT NOT NULL DEFAULT '',
  email          TEXT NOT NULL DEFAULT '',         -- e-mail institucional do próprio local
  observacao     TEXT NOT NULL DEFAULT '',
  ordem_num      REAL,
  ordem_idx      INTEGER NOT NULL DEFAULT 0,
  arquivado_em     TIMESTAMPTZ,
  arquivado_por    TEXT NOT NULL DEFAULT '',
  arquivado_motivo TEXT NOT NULL DEFAULT '',
  criado_em      TIMESTAMPTZ NOT NULL DEFAULT now(),
  atualizado_em  TIMESTAMPTZ NOT NULL DEFAULT now(),
  CONSTRAINT local_forum_tem_comarca CHECK ((tipo = 'FORUM') = (comarca_id IS NOT NULL)),
  CONSTRAINT local_nome_preenchido   CHECK (btrim(nome) <> ''),
  CONSTRAINT local_chave_formato     CHECK (chave ~ '^FOR[0-9]{4}$|^MUN[0-9]{4}$|^LOC-[0-9A-HJKMNP-TV-Z]{26}$'),
  CONSTRAINT local_email_formato     CHECK (email = '' OR email LIKE '%_@_%._%')
);

COMMENT ON TABLE locais IS
  '72 fóruns + 1 Sede. Fórum sempre tem comarca; Sede nunca tem (CHECK local_forum_tem_comarca).';
COMMENT ON CONSTRAINT local_forum_tem_comarca ON locais IS
  'Elimina o COALESCE(f.comarca_id, u.comarca_id) que o v2 precisa repetir em toda consulta.';

-- nome único dentro do local pai: dois fóruns da mesma comarca não podem ter o
-- mesmo nome (Vitória, Vila Velha e Viana têm dois fóruns cada).
CREATE UNIQUE INDEX IF NOT EXISTS ux_locais_nome
  ON locais (coalesce(comarca_id, 0), nome)
  WHERE arquivado_em IS NULL;

CREATE INDEX IF NOT EXISTS ix_locais_comarca
  ON locais (comarca_id, ordem_num, ordem_idx)
  WHERE arquivado_em IS NULL;

-- -----------------------------------------------------------------------------
-- 4. nos — tudo abaixo do local, uma árvore autorreferente
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS nos (
  id             BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  chave          TEXT NOT NULL UNIQUE,          -- UNI0001 | NO-<ULID>
  origem         TEXT NOT NULL DEFAULT 'seed' CHECK (origem IN ('seed', 'app')),
  local_id       BIGINT NOT NULL REFERENCES locais(id),
  pai_id         BIGINT REFERENCES nos(id),     -- NULL = primeiro nível sob o local
  tipo           TEXT NOT NULL DEFAULT 'UNIDADE',  -- UNIDADE|SETOR|VARA|JUIZADO|SECRETARIA|OUVIDORIA…
  nome           TEXT NOT NULL,
  endereco       TEXT NOT NULL DEFAULT '',
  cep            TEXT NOT NULL DEFAULT '',
  observacao     TEXT NOT NULL DEFAULT '',
  selecionavel   BOOLEAN NOT NULL DEFAULT false,
  virtual        BOOLEAN NOT NULL DEFAULT false,
  ordem_num      REAL,
  ordem_idx      INTEGER NOT NULL DEFAULT 0,
  arquivado_em     TIMESTAMPTZ,
  arquivado_por    TEXT NOT NULL DEFAULT '',
  arquivado_motivo TEXT NOT NULL DEFAULT '',
  criado_em      TIMESTAMPTZ NOT NULL DEFAULT now(),
  atualizado_em  TIMESTAMPTZ NOT NULL DEFAULT now(),
  CONSTRAINT no_nome_preenchido CHECK (btrim(nome) <> ''),
  CONSTRAINT no_chave_formato   CHECK (chave ~ '^UNI[0-9]{4}$|^NO-[0-9A-HJKMNP-TV-Z]{26}$'),
  CONSTRAINT no_nao_e_raiz      CHECK (pai_id IS NULL OR pai_id <> id)
);

COMMENT ON TABLE nos IS
  '856 nós do seed em lista única (612 com pai, profundidade ≤ 2 abaixo do local). Unidades e setores deixam de ser tabelas distintas: o nível é a posição.';
COMMENT ON COLUMN nos.local_id IS
  'NOT NULL e copiado do pai na criação: todo nó sabe seu local, o que apaga a cadeia nó→…→fórum→comarca das consultas de escopo. Consistência com pai_id é validada na aplicação.';
COMMENT ON COLUMN nos.arquivado_em IS
  'Arquivar pai não toca nos filhos: a invisibilidade é computada por subárvore (WITH RECURSIVE). Exclusão em cascata é proibida.';

CREATE INDEX IF NOT EXISTS ix_nos_local
  ON nos (local_id, ordem_num, ordem_idx)
  WHERE arquivado_em IS NULL;

CREATE INDEX IF NOT EXISTS ix_nos_pai
  ON nos (pai_id, ordem_num, ordem_idx)
  WHERE arquivado_em IS NULL;

-- sem irmãos homônimos no seed (medido: 0 colisões em 856 nós)
CREATE UNIQUE INDEX IF NOT EXISTS ux_nos_nome_irmao
  ON nos (local_id, coalesce(pai_id, 0), nome)
  WHERE arquivado_em IS NULL;

-- -----------------------------------------------------------------------------
-- 5. contatos — n contatos por local ou por nó; o vínculo de local é obrigatório
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS contatos (
  id             BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  chave          TEXT NOT NULL UNIQUE,          -- C000001 | CONT-<ULID>
  local_id       BIGINT NOT NULL REFERENCES locais(id),
  no_id          BIGINT REFERENCES nos(id),     -- NULL = contato do local (Geral, Recepção, Telefonista…)
  tipo           TEXT NOT NULL CHECK (tipo IN ('Telefone', 'WhatsApp', 'Ramal', 'E-mail', 'Fax')),
  descricao      TEXT NOT NULL DEFAULT '',      -- Institucional | Gabinete | Cartório | Geral | Atendimento…
  valor          TEXT NOT NULL,
  ordem_num      REAL,
  ordem_idx      INTEGER NOT NULL DEFAULT 0,
  fonte          TEXT NOT NULL DEFAULT '',      -- arquivo de origem: qual documento trouxe este valor
  vigente_desde  DATE,                          -- data do documento de origem
  criado_em      TIMESTAMPTZ NOT NULL DEFAULT now(),
  atualizado_em  TIMESTAMPTZ NOT NULL DEFAULT now(),
  -- derivadas: o que a busca usa; nunca escrever nelas
  busca_texto    TEXT GENERATED ALWAYS AS (
                   norm_texto(coalesce(descricao, '') || ' ' || coalesce(valor, ''))
                 ) STORED,
  busca_digitos  TEXT GENERATED ALWAYS AS (so_digitos(valor)) STORED,
  CONSTRAINT contato_valor_preenchido CHECK (btrim(valor) <> ''),
  CONSTRAINT contato_email_tem_arroba CHECK (tipo <> 'E-mail' OR valor LIKE '%_@_%._%'),
  -- Número: 8 a 13 dígitos (fixo com DDD = 10, móvel = 11, 0800 = 10/11).
  -- A base publicada do demo é mascarada — `(27) 3134-XXXX` no telefone, `XXXX`
  -- no ramal —, então o valor mascarado (só X, dígitos e separadores, com pelo
  -- menos dois X) também é aceito: sem isso o repositório público não carrega
  -- no próprio schema.
  CONSTRAINT contato_numero_tem_digito
    CHECK (tipo NOT IN ('Telefone', 'WhatsApp', 'Ramal', 'Fax')
           OR so_digitos(valor) ~ '^[0-9]{8,13}$'
           OR (valor ~* '^[0-9x()\s.\-+]+$' AND valor ~* '[x]{2,}')),
  CONSTRAINT contato_chave_formato CHECK (chave ~ '^C[0-9]{6}$|^CONT-[0-9A-HJKMNP-TV-Z]{26}$')
);

COMMENT ON TABLE contatos IS
  '1.807 contatos do seed. O local é obrigatório; o nó é opcional (contato do local = bloco de abertura do catálogo). Não existe setor virtual: "contato do local" é no_id NULL.';
COMMENT ON COLUMN contatos.descricao IS
  'Sempre preenchida em e-mail: Institucional | Gabinete | Cartório. É o que permite dois e-mails no mesmo nó (2º e 3º Juizado Especial Criminal de Vitória).';
COMMENT ON COLUMN contatos.fonte IS
  'Documento de origem (nome do arquivo). O v2 guardava "Contato provisório — validar vigência." em observação livre: texto não filtra, não expira e não se audita.';
COMMENT ON COLUMN contatos.vigente_desde IS
  'Data do documento que trouxe o valor. Ofício da fonte = março de 2024; catálogo = junho e agosto de 2026. A tela marca "confirmar vigência" a partir daqui.';
COMMENT ON COLUMN contatos.busca_digitos IS
  'Só dígitos. Tolera as quatro grafias do catálogo: (27) 9XXXX-XXXX, (27) 9XXXXXXXX, (27) 3XXXXXXX, (27) 9XXXX.XXXX.';

CREATE INDEX IF NOT EXISTS ix_contatos_local
  ON contatos (local_id, no_id, ordem_num, ordem_idx);

CREATE INDEX IF NOT EXISTS ix_contatos_no
  ON contatos (no_id)
  WHERE no_id IS NOT NULL;

CREATE INDEX IF NOT EXISTS ix_contatos_tipo
  ON contatos (tipo, local_id);

-- busca textual (4.5): trigrama para texto, prefixo para telefone
CREATE INDEX IF NOT EXISTS ix_contatos_busca_trgm
  ON contatos USING gin (busca_texto gin_trgm_ops);

CREATE INDEX IF NOT EXISTS ix_contatos_busca_dig
  ON contatos (busca_digitos text_pattern_ops);

-- procedência: "o que veio deste documento" e "o que precisa reconfirmar"
CREATE INDEX IF NOT EXISTS ix_contatos_fonte
  ON contatos (fonte, vigente_desde)
  WHERE fonte <> '';

-- -----------------------------------------------------------------------------
-- 6. telefones_uteis — os 11 UTL0001 do seed (Central de ajuda, SAC, etc.)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS telefones_uteis (
  id             BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  chave          TEXT NOT NULL UNIQUE,          -- UTL0001 | UTL-<ULID>
  nome           TEXT NOT NULL,
  tipo           TEXT NOT NULL DEFAULT 'Telefone',
  valor          TEXT NOT NULL,
  ordem_idx      INTEGER NOT NULL DEFAULT 0,
  fonte          TEXT NOT NULL DEFAULT '',
  vigente_desde  DATE,
  criado_em      TIMESTAMPTZ NOT NULL DEFAULT now(),
  atualizado_em  TIMESTAMPTZ NOT NULL DEFAULT now(),
  busca_texto    TEXT GENERATED ALWAYS AS (norm_texto(coalesce(nome, '') || ' ' || coalesce(valor, ''))) STORED,
  busca_digitos  TEXT GENERATED ALWAYS AS (so_digitos(valor)) STORED,
  CONSTRAINT util_chave_formato CHECK (chave ~ '^UTL[0-9]{4}$|^UTL-[0-9A-HJKMNP-TV-Z]{26}$'),
  CONSTRAINT util_nome_preenchido CHECK (btrim(nome) <> '')
);

CREATE INDEX IF NOT EXISTS ix_uteis_busca_trgm ON telefones_uteis USING gin (busca_texto gin_trgm_ops);
CREATE INDEX IF NOT EXISTS ix_uteis_busca_dig  ON telefones_uteis (busca_digitos text_pattern_ops);

-- -----------------------------------------------------------------------------
-- 7. sinonimos — vocabulário de busca (ideia 04 do backlog)
-- -----------------------------------------------------------------------------
-- Trigrama resolve erro de digitação, não vocabulário: "reconhecimento de firma"
-- e "cartorio" não têm trigrama em comum, e é a mesma pergunta. O vocabulário da
-- base é pequeno (619 palavras alfabéticas, medido em 26/11/2026), então o léxico é
-- curado à mão, sem modelo, sem custo e revisável em revisão de código.
--
-- `termo` é a forma digitada, já normalizada por `norm_texto` (o CHECK garante —
-- evita duas linhas para "Cartório" e "cartorio"); `expansao` são frases
-- canônicas que substituem o termo na consulta. Dentro da mesma frase as palavras
-- valem juntas (E); frases diferentes valem como alternativas (OU).
CREATE TABLE IF NOT EXISTS sinonimos (
  id        BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  termo     TEXT NOT NULL,
  expansao  TEXT[] NOT NULL,
  CONSTRAINT sinonimo_termo_unico UNIQUE (termo),
  CONSTRAINT sinonimo_termo_normalizado
    CHECK (termo = norm_texto(termo) AND btrim(termo) <> ''),
  CONSTRAINT sinonimo_expansao_preenchida
    CHECK (cardinality(expansao) > 0 AND array_position(expansao, '') IS NULL)
);

COMMENT ON TABLE sinonimos IS
  'Léxico de busca (ideia 04): termo digitado -> frases canônicas. Curado à mão; o léxico versionado está em SinonimoSeeder.';
COMMENT ON COLUMN sinonimos.expansao IS
  'Frases canônicas que substituem o termo na consulta: E dentro da frase, OU entre frases.';

-- -----------------------------------------------------------------------------
-- 8. historico — histórico do dado, com expurgo de 180 dias
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS historico (
  id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  acao            TEXT NOT NULL,                  -- criar | atualizar | remover | arquivar | restaurar | importar | expurgar
  entidade        TEXT NOT NULL DEFAULT 'contato' CHECK (entidade IN ('contato', 'no', 'local', 'comarca', 'util')),
  entidade_chave  TEXT NOT NULL DEFAULT '',       -- chave lógica; vazio quando a ação não aponta uma linha (expurgar)
  antes           JSONB,                          -- estado antes da ação; NULL em criar/importar
  depois          JSONB,                          -- estado depois da ação; NULL em remover
  autor           TEXT NOT NULL DEFAULT '',       -- opaco: usr_00042:admin (id do usuário + papel)
  request_id      TEXT NOT NULL DEFAULT '',
  criado_em       TIMESTAMPTZ NOT NULL DEFAULT now(),
  CONSTRAINT historico_autor_formato
    CHECK (autor = '' OR autor ~ '^[A-Za-z0-9][A-Za-z0-9._:-]{0,63}$'),
  CONSTRAINT historico_estado_presente
    CHECK (antes IS NOT NULL OR depois IS NOT NULL),
  CONSTRAINT historico_estado_objeto
    CHECK ((antes IS NULL OR jsonb_typeof(antes) = 'object')
       AND (depois IS NULL OR jsonb_typeof(depois) = 'object'))
);

COMMENT ON TABLE historico IS
  'Histórico do dado, não auditoria de permissão. Guarda a chave lógica e o retrato antes/depois em JSONB; exclusão física do contato não apaga a linha.';
COMMENT ON COLUMN historico.entidade IS
  'Entidades do dado: contato, no, local, comarca, util. `usuario`, `acesso` e `pedido` NÃO entram aqui: são tabelas do app (migrations do Laravel, mesmo banco) e a auditoria delas não está sujeita ao expurgo de 180 dias.';
COMMENT ON COLUMN historico.entidade_chave IS
  'Chave lógica da linha alterada (C000001, UNI0027, MUN0001, FOR0001, UTL0001). Vazia quando a ação não aponta uma linha, como o registro do próprio expurgo.';
COMMENT ON COLUMN historico.antes IS
  'Estado do dado antes da ação, em JSONB. NULL quando a linha nasceu (criar/importar).';
COMMENT ON COLUMN historico.depois IS
  'Estado do dado depois da ação, em JSONB. NULL quando a linha deixou de existir (remover).';
COMMENT ON COLUMN historico.criado_em IS
  'Base do expurgo de 180 dias: depois dele sobra só agregado (quantidade por mês, sem nome, telefone, e-mail ou chave).';

CREATE INDEX IF NOT EXISTS ix_historico_expurgo ON historico (criado_em);
CREATE INDEX IF NOT EXISTS ix_historico_entidade ON historico (entidade, entidade_chave)
  WHERE entidade_chave <> '';

-- 7b. historico_mensal — o que sobrevive ao expurgo de 180 dias
-- O expurgo apaga a linha crua; sobra a contagem por mês, ação e entidade. Sem
-- nome, telefone, e-mail, chave lógica ou autor: não dá para reidentificar
-- ninguém a partir desta tabela, e ela responde "quantas mudanças houve em
-- julho/2026?" — que é o que uma auditoria de dados precisa saber depois.
CREATE TABLE IF NOT EXISTS historico_mensal (
  mes        DATE   NOT NULL,
  acao       TEXT   NOT NULL,
  entidade   TEXT   NOT NULL,
  quantidade BIGINT NOT NULL CHECK (quantidade > 0),
  PRIMARY KEY (mes, acao, entidade),
  CONSTRAINT historico_mensal_mes_dia_um CHECK (EXTRACT(DAY FROM mes) = 1)
);
CREATE INDEX IF NOT EXISTS ix_historico_request ON historico (request_id) WHERE request_id <> '';

-- -----------------------------------------------------------------------------
-- 9. atualizado_em automático
-- -----------------------------------------------------------------------------
DROP TRIGGER IF EXISTS tg_comarcas_atualiza ON comarcas;
CREATE TRIGGER tg_comarcas_atualiza BEFORE UPDATE ON comarcas
  FOR EACH ROW EXECUTE FUNCTION marca_atualizacao();

DROP TRIGGER IF EXISTS tg_locais_atualiza ON locais;
CREATE TRIGGER tg_locais_atualiza BEFORE UPDATE ON locais
  FOR EACH ROW EXECUTE FUNCTION marca_atualizacao();

DROP TRIGGER IF EXISTS tg_nos_atualiza ON nos;
CREATE TRIGGER tg_nos_atualiza BEFORE UPDATE ON nos
  FOR EACH ROW EXECUTE FUNCTION marca_atualizacao();

DROP TRIGGER IF EXISTS tg_contatos_atualiza ON contatos;
CREATE TRIGGER tg_contatos_atualiza BEFORE UPDATE ON contatos
  FOR EACH ROW EXECUTE FUNCTION marca_atualizacao();

DROP TRIGGER IF EXISTS tg_uteis_atualiza ON telefones_uteis;
CREATE TRIGGER tg_uteis_atualiza BEFORE UPDATE ON telefones_uteis
  FOR EACH ROW EXECUTE FUNCTION marca_atualizacao();

-- =============================================================================
-- 10. Papéis (NÃO executado por este arquivo — aplicar no Neon, uma vez)
-- -----------------------------------------------------------------------------
-- Um banco, um dono: o papel da aplicação (ADR-010). O desenho anterior, com
-- `diretorio_motor` e `diretorio_espelho`, existia para separar o motor do
-- Laravel e caiu junto com o segundo serviço — não recriar dois papéis.
--
--   CREATE ROLE diretorio_app LOGIN PASSWORD '***';
--   GRANT USAGE ON SCHEMA public TO diretorio_app;
--   GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO diretorio_app;
--   GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO diretorio_app;
--   ALTER DEFAULT PRIVILEGES IN SCHEMA public
--     GRANT SELECT, INSERT, UPDATE, DELETE ON TABLES TO diretorio_app;
--
-- No Neon, `pg_trgm` e `unaccent` estão disponíveis e o schema `public` já existe;
-- `CREATE EXTENSION` exige o papel dono do banco (o mesmo que roda este arquivo).
-- =============================================================================
