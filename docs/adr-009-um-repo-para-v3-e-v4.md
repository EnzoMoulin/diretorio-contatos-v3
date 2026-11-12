# ADR-009 — Um repositório para a v3 e a v4 (dois deployáveis)

- **Status:** aceito em 12/11/2026, por decisão do Enzo.
- **Complementa:** o ADR-008 (v3 com um serviço só, FastAPI na v4). Não o substitui.
- **Vale para:** topologia de repositório. Não altera contrato, modelo de dados nem hospedagem.

## Contexto

O produto tem três repositórios e nenhum deploy vivo:

| Repositório | Commits | Tamanho | Estado |
| :--- | ---: | ---: | :--- |
| `diretorio-contatos` (v1, Apps Script) | 13 | 2,4 M | congelado, é evidência do arco |
| `diretorio-contatos-v2` (FastAPI + SQLite) | 24 | 25,6 M | fechado, falta a parte manual do deploy |
| `diretorio-contatos-v3` (Laravel + TALL) | 13 | 340 K | só DDL, docs e esqueleto de deploy |

A v4 não existe como código. Pelo ADR-008 ela é a fase do FastAPI **mantendo o Laravel da v3**, com
o mesmo Postgres e o mesmo modelo de dados do diretório. Ou seja: v3 e v4 não são dois produtos —
são dois processos do mesmo produto.

Criar um quarto repositório para a v4 teria três custos concretos:

1. **DDL duplicado ou dependente.** `db/schema.sql` é a fonte do modelo nas duas fases. Em dois
   repos, ou o DDL é copiado (e divergem) ou a v4 lê o DDL da v3 por caminho de irmão — o mesmo
   acoplamento invisível que hoje existe com `../diretorio-contatos-v2/seed_mascarado.json`, só que
   agora no arquivo mais importante do projeto.
2. **Deriva de documentação multiplicada.** O projeto já pagou esse preço uma vez: três arquivos
   escreviam `v3.1` e um documento descrevia dois bancos depois de a decisão ter mudado. Quatro
   repositórios com README próprio multiplicam o número de lugares que precisam saber a mesma coisa.
3. **Sinal errado no portfólio.** Quatro repositórios pequenos do mesmo produto leem como quatro
   tentativas; um repositório com dois deployáveis lê como arquitetura.

## Decisão

1. **v3 e v4 vivem no mesmo repositório**, como **dois deployáveis independentes**. O repositório é
   `diretorio-contatos-v3` (nome mantido; renomear é decisão separada, porque quebra link).
2. **Estrutura alvo**, aplicada quando a primeira linha de Laravel for escrita — não antes:

   ```
   apps/web/            Laravel 13 + TALL + Filament   → deployável 1 (Render, Dockerfile próprio)
   services/busca/      FastAPI                        → deployável 2 (v4, Dockerfile próprio)
   db/                  schema.sql, invariantes, carga do seed (fonte única, compartilhada)
   infra/               render.yaml, docker/ do web
   docs/                ADRs, planos, backlog
   ```

   Hoje o repositório continua na raiz atual (`db/`, `docs/`, `Dockerfile`, `render.yaml`). O
   deslocamento para `apps/web/` acontece junto com o `composer create-project`: mover um esqueleto
   sem aplicação não prova nada e só gera commit de mudança de caminho.

3. **Hospedagem não muda.** Continuam dois serviços free no Render, cada um com seu Dockerfile —
   monorepo não é serviço único. No `render.yaml`, o serviço do web aponta `dockerContext` para
   `apps/web/`. O banco continua único (ADR-008) até a v4 ter motivo medido para separar.
4. **Fronteira entre os dois é HTTP, não import.** Nenhum código Python conhece o banco do Laravel
   por ORM nem compartilha model; a v4 fala com o diretório pelo contrato `/api/v1` descrito em
   `fase-3-arquitetura-e-adrs.md` e pela mesma `DB_URL` só onde o ADR da v4 autorizar.
5. **`db/` é fonte única e mora na raiz**, fora dos deployáveis. É o que `db/testar-schema.sh` aplica
   e o que as migrations do Laravel usam como baseline.
6. **O seed continua no repositório do v2**, lido por caminho relativo de irmão
   (`SEED_JSON=../diretorio-contatos-v2/seed_mascarado.json`), e nunca versionado aqui. Dado de
   contato não entra em git — a regra do workspace continua valendo no monorepo.
7. **CI dividido por caminho.** Um workflow com filtro de `paths` por deployável (`apps/web/**`,
   `services/busca/**`, `db/**`, `docs/**`), para que mexer em documentação não rode a matriz de
   testes das duas stacks. A varredura de privacidade continua rodando sempre.

## Consequências

- **Melhora:** um DDL, um conjunto de invariantes, um lugar para decidir a fase. A v4 nasce como
  pasta, não como projeto novo — e o trabalho de separação que o ADR-008 adiou fica no mesmo
  histórico, onde a comparação v3→v4 é legível.
- **Custo:** dois Dockerfiles e dois contextos de build no mesmo repo. Sem `paths` no CI, todo push
  roda as duas suítes. Aceito enquanto a suíte for pequena.
- **Risco de leitura:** quem abrir o repo procurando "a aplicação" acha `apps/` e `services/` antes
  de achar o Laravel. O README passa a abrir pela estrutura, não pelo status.
- **Não muda:** os repositórios v1 e v2 ficam como estão, congelados, cada um com seu histórico
  publicado. Juntar histórico de stacks diferentes não vale o preço (reescrita de SHA, dossiê da AE3
  citando hashes, CI de cada um).

## O que fica em aberto

- Renomear o repositório para `diretorio-contatos` (sem sufixo) quando a v4 existir. Quebra link
  citado em documento; só com pedido explícito.
- Se a v4 chega a ter deploy próprio no ar, ou se o free tier passa a hospedar só o v3 e a v4 fica
  como código + suíte (decisão de hospedagem, não desta ADR).
- Ordem das ideias de produto: `docs/backlog-ideias.md`.

## Referências

- `adr-008-v3-um-servico-e-fastapi-na-v4.md` — v3 com um serviço, FastAPI na v4.
- `v3-arquitetura-preparacao.md` — plano vigente das duas fases.
- `backlog-ideias.md` — ideias de produto, com fase, gatilho e veredito.
- `../../README.md` (workspace) — convenção de topologia de repositório.
