# Diretório de Contatos — v3 (Laravel + TALL)

[![Testes](https://github.com/EnzoMoulin/diretorio-contatos-v3/actions/workflows/testes.yml/badge.svg)](https://github.com/EnzoMoulin/diretorio-contatos-v3/actions/workflows/testes.yml)

> Terceira fase do mesmo produto das versões [v1](https://github.com/EnzoMoulin/diretorio-contatos)
> (Apps Script) e [v2](https://github.com/EnzoMoulin/diretorio-contatos-v2) (Python stdlib): consulta
> pública de contatos de fóruns e unidades, com backoffice. Projeto de portfólio;
> **todos os dados são fictícios** — ver `LICENSE`.

## Status

**Fase de fundação, não de código.** O que existe aqui é o que se prova antes da primeira
linha de Laravel: o modelo físico do banco, o desenho de deploy, a direção visual e as
decisões registradas. O esqueleto da aplicação (migrations, models, Livewire, Filament)
ainda não começou — quando começar, entra por este README.

| Já feito | Onde |
| :--- | :--- |
| Decisão de stack: **Laravel 13 + TALL + Filament + Postgres (Neon), um serviço Docker no Render free** | `docs/arquitetura-v3.md` |
| DDL completo e idempotente do banco `db_diretorio`, com invariantes e expurgo LGPD | `db/schema.sql`, `db/README.md` |
| Direção visual escolhida: **A — mapa protagonista**, paleta slate + turquesa | `docs/identidade-visual.md` |
| Blueprint de deploy e imagem Docker (nginx + php-fpm, migrations no entrypoint) | `render.yaml`, `Dockerfile`, `docker/` |
| CI: varredura de privacidade + DDL em Postgres 16 real | `.github/workflows/testes.yml` |
| **Fluxo de PR com rastreabilidade**: formulário de PR (`Closes #N`), formulários de issue e checks obrigatórios para entrar na `main` | `.github/`, seção *Fluxo de contribuição* abaixo |
| **Régua Laravel escrita e adormecida**: Pint, Larastan, Pest e cache de rotas/views prontos, disparando quando `apps/web/` existir | `.github/workflows/laravel.yml`, `docs/convencoes-laravel.md` |
| Plano vigente da v3 e da v4: o que migra, o que não migra, critério do FastAPI | `docs/v3-arquitetura-preparacao.md`, `docs/adr-008-v3-um-servico-e-fastapi-na-v4.md` |
| **Decisão de 12/11/2026: a v4 mora neste repositório** — dois deployáveis (`apps/web`, `services/busca`), um `db/` | `docs/adr-009-um-repo-para-v3-e-v4.md` |
| Briefing da direção A com mapa real e layout de celular (a executar) | `docs/v3-interface.md` |

> **Números verificados.** As contagens que este repositório cita — 1.807 contatos, 838 nós,
> 70 entradas em `comarcas` (69 com IBGE + a Sede), 11 úteis, 94 casos de teste — foram medidas no
> `diretorio-contatos-v2` @ `a93aeb1`, não copiadas de texto. A perna
> `SIMULACAO_AUTO_APROVAR=1` com `ADMIN_EMAILS` foi reconferida em 12/11/2026: `Ran 95`,
> 1 pulado, OK. A perna 0 e o resto das contagens estão em
> `diretorio-contatos-v2/scripts/contagens.json`, que o CI confere contra o README.

## Stack

- **Laravel 13 / PHP 8.4** serve HTML e JSON do mesmo processo (Blade + Livewire).
- **Tailwind + Alpine** no front; **Filament** no backoffice — é o que fecha a lacuna de
  Laravel no portfólio sem construir dois backends.
- **Postgres no Neon free**, fora do Render: o Postgres free do Render expira em 30 dias e o
  demo quebraria sozinho.
- **FastAPI na v4**, em serviço separado e com o Laravel da v3 mantido, quando houver medição
  que justifique — importação com `ftfy`, deduplicação e busca fonética em português.

## Estrutura (v3 e v4 no mesmo repositório)

Decidido em 12/11/2026 (`docs/adr-009-um-repo-para-v3-e-v4.md`). A v4 não é um quarto repositório:
é um **segundo deployável** aqui dentro. Layout alvo, aplicado quando a primeira linha de Laravel
for escrita — hoje a raiz continua como está:

```
apps/web/          Laravel 13 + TALL + Filament   → deployável 1 (Render, Dockerfile próprio)
services/busca/    FastAPI                        → deployável 2 (v4, Dockerfile próprio)
db/                schema.sql, invariantes, carga do seed — fonte única das duas fases
infra/             render.yaml e docker/ do web
docs/              ADRs, planos, backlog
```

A fronteira entre os dois é HTTP (`/api/v1`), não import de código. O seed continua no repositório
do v2, lido por caminho relativo de irmão e nunca versionado aqui.

## Rodar o que existe

```bash
# estrutura do banco (cria <base>_teste, aplica o DDL duas vezes, roda as invariantes)
db/testar-schema.sh

# com o seed da v2 ao lado (1.807 contatos, 838 nós) — os repos são irmãos por desenho
SEED_JSON=../diretorio-contatos-v2/seed_mascarado.json db/testar-schema.sh

# imagem
docker build -t diretorio-contatos-v3 .
```

O script só age em banco terminado em `_teste`, então produção nunca é atingida por acidente.
Detalhe de cada arquivo de `db/`, pegadinhas já pagas e a medição que fixou o limiar de
similaridade em 0,45: `db/README.md`.

## Fluxo de contribuição

Nada entra na `main` por push direto. O caminho é sempre o mesmo:

```
issue (problema) → branch → commits semânticos → PR ("Closes #N") → CI verde → squash merge
```

- **Issue primeiro.** O formulário de bug e o de ideia/melhoria pedem o problema, o gatilho e
  a definição de pronto. Ideia registrada não é compromisso: vira trabalho quando o gatilho
  acontecer (`docs/backlog-ideias.md` é a fila de produto).
- **PR com `Closes #N`.** O merge fecha a issue sozinho, e o histórico da `main` mostra o
  problema e o código que o resolveu no mesmo fio. `Refs #N` quando a mudança só encosta na
  issue.
- **Descrição do PR com trade-offs**, não só a lista de arquivos: o que foi descartado, o que
  fica pior, o que ficou fora de escopo.
- **Checks obrigatórios.** O ruleset `protege-default` exige os jobs de `.github/workflows/testes.yml`
  verdes e recusa force-push e exclusão da `main`. PR vermelho não tem botão de merge.

| Workflow | Dispara em | Prova |
| :--- | :--- | :--- |
| `testes.yml` → `Varredura de privacidade` | todo push e PR | nenhum telefone/e-mail real e nenhum nome de pessoa fora do padrão sintético |
| `testes.yml` → `DDL e invariantes (Postgres 16)` | todo push e PR | `db/schema.sql` aplica duas vezes (idempotência) e as 41 invariantes estruturais passam |
| `testes.yml` → `Varredura do seed (a pedido)` | `workflow_dispatch` | o seed da v2, clonado na hora, também está limpo |
| `laravel.yml` → Pint, Larastan, Pest, Blade/rotas | PR que toca `apps/web/**` | estilo PSR-12, tipos, fluxos de feature e compilação de view — **adormecido até o esqueleto existir** |

Commits seguem Conventional Commits (`feat:`, `fix:`, `docs:`, `ci:`, `chore:`…), um assunto por
commit; o squash preserva um commit por funcionalidade na `main`.

## Roadmap

O que ainda não existe está em [issues](https://github.com/EnzoMoulin/diretorio-contatos-v3/issues),
não em promessa de README. Duas listas se complementam:

- **issues abertas** — trabalho reconhecido: esqueleto Laravel, busca, mapa, paridade de
  contrato, acessibilidade no CI, plano de testes;
- `docs/backlog-ideias.md` — ideias com fase, gatilho e veredito, inclusive as descartadas e o
  motivo. Issue sem gatilho não entra em execução.

Prioridade vigente: fechar o **esqueleto Laravel** (é o que destrava Pint/Larastan/Pest no CI e
as rotas de leitura) e, em cima dele, a busca pública.

## Deploy

Blueprint do Render em `render.yaml`; segredos (`APP_BASE_URL`, `GOOGLE_CLIENT_JSON`,
`ADMIN_EMAILS`) só no painel, nunca no repositório. Runbook completo em
`docs/fase-3-arquitetura-e-adrs.md`. Ainda não deployado.

## Documentos

| Documento | Conteúdo |
| :--- | :--- |
| `docs/arquitetura-v3.md` | Decisão de 10/11/2026: por que Laravel é a casca, por que o FastAPI fica para depois, o que acontece com o v2. Revisado em 12/11/2026: a fase do FastAPI é a **v4**. |
| `docs/v3-arquitetura-preparacao.md` | **Plano vigente**: o que a v3 entrega, o que o v2 deixa como especificação, escopo mínimo publicável e os candidatos da v4. |
| `docs/v3-interface.md` | Briefing da direção A para o mockup: mapa real do ES, layout de celular, sete estados, restrições de marca. Ainda não executado. |
| `docs/adr-008-v3-um-servico-e-fastapi-na-v4.md` | ADR da decisão de 12/11/2026: v3 com um serviço só, FastAPI na v4, banco único na v3. |
| `docs/adr-009-um-repo-para-v3-e-v4.md` | ADR da decisão de 12/11/2026: v3 e v4 num repositório, dois deployáveis, `db/` compartilhado. |
| `docs/backlog-ideias.md` | Ideias de produto com fase, gatilho e veredito — inclusive as descartadas, com motivo. |
| `docs/paridade-contrato-v2.md` | Contrato de leitura congelado: o que a v3 tem de devolver igual ao v2, e onde a diferença é decisão. |
| `docs/reconciliacao-2026-11-12.md` | Laudo de aderência da base aos três catálogos oficiais, com as divergências e o que o método não prova. É a **base final de demonstração** da v3 e da v4. |
| `docs/fase-3-arquitetura-e-adrs.md` | **Documento histórico**: o desenho de dois serviços (Laravel + motor FastAPI), preservado como especificação da v4. Tem aviso no topo; não é o plano da v3. |
| `docs/identidade-visual.md` | Direção A, mapa protagonista. Inclui o que **não** fazer: `direcoes-visuais.html` é instrumento de decisão, não markup. |
| `db/README.md` | O banco lógico: tabelas, invariantes, medições e as regras que ficam na aplicação. |

## Base de demonstração (congelada)

A base da v3 e da v4 é a que foi conferida em 12/11/2026 contra os três catálogos
oficiais: **69 comarcas + Sede · 72 fóruns · 838 nós · 1.807 contatos · 11 telefones
úteis**, com 296 dos 297 e-mails confirmados por documento. Não há segunda conferência
a fazer enquanto a fonte não mudar.

Duas garantias baratas acompanham isso:

- `../dados/catalogo-2026/base-verificada.json` — manifesto com as contagens e o SHA-256
  de cada CSV **e do `seed_mascarado.json` da v2** (o arquivo que o app serve de fato).
  Regerado por `scripts/reconciliar-catalogo.py --manifesto … --seed …`; o `estado.sh` do
  workspace recomputa os hashes e avisa se a base ou o seed mudaram depois da conferência.
- `scripts/varrer_privacidade.py --seed ../diretorio-contatos-v2/seed_mascarado.json`
  — fecha o ponto cego do seed (ele mora no repo da v2 e não é versionado aqui): falha se
  aparecer telefone/e-mail não mascarado ou **nome de pessoa** nos nós, contatos e
  telefones úteis. `--autoteste` prova o detector com 33 casos (nomes reais × rótulos de
  unidade).
- `.privacidade-sinteticos` — allowlist de fixture da varredura: uma linha por valor
  literal ou `re:<regex>`. Existe para as quatro grafias de telefone que
  `db/verificar-schema.sql` usa ao testar o normalizador (todas terminam em `000N`, mas o
  formato varia de propósito) e vale **só** para fixture de teste, nunca para calar dado
  real. Por isso ela não age em silêncio: a varredura imprime quantos valores declarados
  abafaram achado, quais, e qual declaração nunca casou com nada — linha nova aparece no
  log do CI, e declaração morta aparece como candidata a sair.

## Relação com as versões anteriores

O v2 não é tocado: continua público e congelado como evidência do arco v1→v2. O único
acréscimo permitido é teste de privacidade, que não muda a aplicação (o detector de nome de
pessoa mora lá também, ver `tests/test_sem_telefone_real.py`). O que se escreve
do zero é esta versão — e um dos critérios é não herdar código: a similaridade com o v1 fica
em ≤10%. A comparação lado a lado dos três está no README do v1 e do v2.

## Licença

Direitos reservados. Ver `LICENSE`.
