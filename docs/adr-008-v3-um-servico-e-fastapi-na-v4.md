# ADR-008 — v3 com um serviço só (Laravel) e o motor FastAPI como v4

- **Status:** aceito em 12/11/2026.
- **Substitui, para a v3:** o plano de dois serviços de `fase-3-arquitetura-e-adrs.md` (seções 2.3, 3, 6 e ADR-001 a ADR-007).
- **Plano vigente:** `v3-arquitetura-preparacao.md`.
- **Renomeia:** a fase do FastAPI, que o README, o `arquitetura-v3.md` e o `PROJETOS-FUTUROS.md` chamavam de **v3.1**, passa a se chamar **v4**.

## Contexto

O repositório carregava dois desenhos vivos ao mesmo tempo. O `fase-3-arquitetura-e-adrs.md`
(87 KB, revisões de 10 e 11/11/2026) define a Fase 3 como orquestrador Laravel + **motor FastAPI**,
com dois bancos lógicos (`db_admin` e `db_diretorio`), chaves de API, `X-Acting-User`,
`Idempotency-Key` e `/api/v1`. O `docs/arquitetura-v3.md`, o README e o `PROJETOS-FUTUROS.md`
já descreviam outro desenho: um serviço Laravel e o FastAPI como satélite "v3.1", sem data.

O `render.yaml` e o `docker/entrypoint.sh` seguem o segundo desenho: **uma** `DB_URL`, um
`php-fpm`, um `migrate --force`. Não existe par de credenciais nem segundo processo.

Decisão do Enzo em 12/11/2026: a v3 é Laravel (com o diretório como módulo) e a **v4** é a versão
em que o FastAPI é implementado, mantendo o Laravel da v3.

## Decisão

1. **v3 = um serviço.** Laravel 13 + TALL + Filament + Postgres (Neon), uma imagem Docker no
   Render free. O diretório é módulo do próprio Laravel (`Directory` e `Access` são módulos de
   código, não serviços). HTML e JSON saem do mesmo processo.
2. **v4 = o FastAPI.** Serviço separado, com o Laravel da v3 mantido. Escopo: importação com
   dry-run e `ftfy`, deduplicação, busca fonética em português, relatórios. Autenticação,
   pedidos e notificações ficam no Laravel; exportação assíncrona também (filas do Laravel).
   O critério do `ADR-0001` da v4 continua: extrair por motivo medido, não por simetria.
3. **O `fase-3-arquitetura-e-adrs.md` fica preservado como especificação da v4**, com aviso no
   topo. O que nele é conceito continua valendo nas duas fases e não se reescreve: chave lógica
   imutável (`MUN0001`, `FOR0001`, `UNI0001`, `C000001`, `UTL0001`), `locais` como âncora
   (`FORUM`/`SEDE`, a Sede com `tipo SEDE` e `chave MUN0000`), árvore única abaixo do local,
   profundidade ≤ 2, busca com `norm_texto` + `pg_trgm`, ciclo de vida do dado (LGPD), as
   invariantes de `db/` e o mascaramento de telefone. O que é de dois serviços — `db_admin`
   separado, ADR-001 (database-per-service), chaves de API, `X-Acting-User`,
   `Idempotency-Key`, `/api/v1`, `referencias_externas`, leitura em lote, sagas — vale para a v4.
   O "Fase 4" da seção 2.4 daquele documento (motor de relatórios com tabela de jobs, `202` e
   `ftfy` na ingestão) também passa a ser escopo da v4.
4. **Banco na v3: um único Postgres.** `db/schema.sql` (modelo do diretório) é o **baseline** das
   migrations do Laravel; identidade, permissão, pedidos e auditoria administrativa entram como
   migrations no mesmo banco, sem segunda credencial. `referencias_externas` permanece no DDL,
   sem uso na v3, reservada à separação da v4 — não removê-la agora evita uma migration de ida e
   volta quando o motor existir.
5. **Números e contrato não mudam.** Os 1.807 contatos, os 838 nós, as 69 comarcas com IBGE, o
   contrato de rotas e a suíte de 94 casos do v2 continuam sendo a especificação. A v3 reimplementa;
   nenhum código Python migra.

## Consequências

- **Melhora:** um deploy, uma credencial, um cold start, um log. O caminho até o primeiro demo
  publicado não depende de dois serviços se entenderem. O esqueleto já escrito (`render.yaml`,
  `Dockerfile`, `docker/`) fica correto como está.
- **Custo:** a v4 passa a exigir o trabalho de separação que o plano de dois serviços já tinha
  desenhado — e esse trabalho está documentado, não perdido. O ADR-001 a ADR-007 viram a
  especificação dessa separação.
- **Risco de leitura:** quem abrir o `fase-3-arquitetura-e-adrs.md` sem o aviso do topo vai achar
  que a v3 tem motor FastAPI. O banner e este ADR existem para isso; não removê-los.
- **Fila de trabalho da v3** (a mesma do plano, sem alteração de conteúdo): migrations + seed →
  Policies com Pest → busca pública em Livewire com o mapa da direção A → painel Filament → CI e
  deploy. Quando as migrations começarem, `db/schema.sql` é a fonte; a validação por
  `db/testar-schema.sh` continua sendo a prova de que o modelo carrega o seed.

## Em aberto (decisão do Enzo, não desta ADR)

- Se procedência (`fonte`, `vigente_desde`) e conferência ("✓ Conferi" e fila de reconfirmação)
  entram no mínimo da v3 ou depois do deploy. O v2 tem os dois, com 14 casos de teste que servem
  de especificação.
- Se a v4 nasce com o critério de "motivo medido" ou como entrega comprometida da fila. O plano
  registra o critério; a decisão de comprometer a fase é do Enzo.

## Referências

- `v3-arquitetura-preparacao.md` — plano vigente (v3 e v4).
- `v3-interface.md` — briefing da direção A (mapa protagonista).
- `fase-3-arquitetura-e-adrs.md` — documento preservado, especificação da v4.
- `arquitetura-v3.md` — decisão de stack de 10/11/2026, com a nota de revisão de 12/11/2026.
- `../db/README.md` — o banco lógico do diretório, invariantes e medições.
