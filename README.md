# Diretório de Contatos — v3 (Laravel + TALL)

[![Testes](https://github.com/EnzoMoulin/diretorio-contatos-v3/actions/workflows/testes.yml/badge.svg)](https://github.com/EnzoMoulin/diretorio-contatos-v3/actions/workflows/testes.yml)

> Terceira fase do mesmo produto das versões [v1](https://github.com/EnzoMoulin/diretorio-contatos)
> (Apps Script) e [v2](https://github.com/EnzoMoulin/diretorio-contatos-v2) (Python stdlib): consulta
> pública de contatos de fóruns e unidades, com backoffice. Projeto de portfólio;
> **todos os dados são fictícios** — ver `LICENSE`.

## Status

**Fase de fundação concluída: a camada de banco e o esqueleto da aplicação estão versionados.** O
`apps/web/` tem Laravel 13 + PHP 8.4 com Pint, Larastan, Pest e Sail; a primeira leitura ligada no
banco é a capa (`GET /`) e o `GET /api/v1/hierarquia`, no contrato do v2. A interface foi validada
antes num **protótipo navegável em Laravel + TALL** que vive fora do repositório (`IDEIAS PARA O V3/
wireframe`, no `.gitignore`): nove rodadas de revisão externa, com capturas e respostas em
`IDEIAS PARA O V3/`. A fumaça desse protótipo está versionada em `tests/e2e/` e vira o e2e da
aplicação. A busca já responde na API (`GET /api/v1/busca`, com léxico de sinônimos); a interface de
busca e o mapa seguem como issues abertas, não promessa de README.

| Já feito | Onde |
| :--- | :--- |
| Decisão de stack: **Laravel 13 + TALL + Filament + Postgres (Neon), um serviço Docker no Render free** | `docs/arquitetura-v3.md` |
| DDL completo e idempotente do banco `db_diretorio`, com invariantes e expurgo LGPD | `db/schema.sql`, `db/README.md` |
| **Migrations da camada de banco**: baseline que executa o `schema.sql`, identidade/escopo (`usuarios`, `acessos`, `pedidos`) e sessions/cache do framework | `apps/web/database/migrations`, `apps/web/database/README.md` |
| **Esqueleto Laravel 13 em `apps/web/`**: models, factories sintéticas, seeders (seed mascarado da v2 por caminho), action + resource + controller da primeira leitura, capa em Blade | `apps/web/`, `apps/web/README.md` |
| Direção visual escolhida: **A — mapa protagonista**, paleta slate + turquesa | `docs/identidade-visual.md` |
| Blueprint de deploy e imagem Docker (nginx + php-fpm, migrations no entrypoint) | `render.yaml`, `apps/web/Dockerfile`, `apps/web/docker/producao/` |
| CI: varredura de privacidade + DDL em Postgres 16 real | `.github/workflows/testes.yml` |
| **Fluxo de PR com rastreabilidade**: formulário de PR (`Closes #N`), formulários de issue e checks obrigatórios para entrar na `main` | `.github/`, seção *Fluxo de contribuição* abaixo |
| **Régua Laravel ativa**: Pint (PSR-12), Larastan (nível 5), Pest e cache de rotas/views, com os quatro jobs rodando em todo PR | `.github/workflows/laravel.yml`, `docs/convencoes-laravel.md` |
| **Protótipo navegável da direção A em Laravel + TALL**, com mapa real do ES e o seed da v2 — nove rodadas de revisão, mantido fora do git | `IDEIAS PARA O V3/wireframe`, `docs/plano-v3.md` |
| **Fumaça e2e do protótipo**: sete verificações em Chromium, saída em JSON | `tests/e2e/` |
| **Plano do v3 encerrado em 20/11/2026**: arquitetura, interface, telas e critérios de aceite | `docs/plano-v3.md` |
| Plano do v3 — e o que ficou fora dele, inclusive a v4 adiada | `docs/plano-v3.md` (vence onde houver conflito), `docs/adr-010-backend-unico-em-laravel.md` |
| **Decisão de 12/11/2026, revisada em 25/11: a v4 moraria neste repositório** — hoje um só deployável (`apps/web`), porque a v4 está adiada | `docs/adr-009-um-repo-para-v3-e-v4.md`, `docs/adr-010-backend-unico-em-laravel.md` |
| Briefing original da direção A (11/11/2026) — executado como protótipo fora do repositório; registro da decisão, não briefing vigente | `docs/v3-interface.md`, `tests/e2e/` |

> **Números verificados.** As contagens que este repositório cita — 1.807 contatos, 856 nós,
> 70 entradas em `comarcas` (69 com IBGE + a Sede), 11 úteis, 94 casos de teste — foram medidas no
> `diretorio-contatos-v2` @ `7320a46`, não copiadas de texto. A perna
> `SIMULACAO_AUTO_APROVAR=1` com `ADMIN_EMAILS` foi reconferida em 24/11/2026: `Ran 95`,
> 1 pulado, OK. A perna 0 e o resto das contagens estão em
> `diretorio-contatos-v2/scripts/contagens.json`, que o CI confere contra o README.

## Stack

- **Laravel 13 / PHP 8.4** serve HTML e JSON do mesmo processo (Blade + Livewire).
- **Tailwind + Alpine** no front; **Filament** no backoffice — é o que fecha a lacuna de
  Laravel no portfólio sem construir dois backends.
- **Postgres no Neon free**, fora do Render: o Postgres free do Render expira em 30 dias e o
  demo quebraria sozinho.
- **FastAPI na v4, adiada** (ADR-010): só entra com medição que justifique — importação com
  `ftfy`, deduplicação e busca fonética em português. O `v3.0` é Laravel sozinho.

## Estrutura (v3 agora; v4 adiada)

Decidido em 12/11/2026 (`docs/adr-009-um-repo-para-v3-e-v4.md`) e revisado em 25/11/2026
(`docs/adr-010-backend-unico-em-laravel.md`). A v4 não é um quarto repositório — mas também não é
fase em curso: o `v3.0` tem **um** deployável. O app está em `apps/web/`: migrations, models,
factories, seeders, a primeira leitura (capa e `/api/v1/hierarquia`) e a régua de estilo/tipo/teste.

```
apps/web/          Laravel 13 + TALL + Filament   → deployável 1 (Dockerfile próprio)
  app/             models, Action, Resource, controllers
  database/        migrations + factories + seeders
  docker/producao/ nginx, php.ini, entrypoint
services/busca/    FastAPI (v4, adiada)           → sem deployável enquanto o ADR-010 valer
db/                schema.sql, invariantes, carga do seed — fonte única do v3
docs/              ADRs, planos, backlog
render.yaml        Blueprint do Render, aponta para apps/web/
```

Se a v4 voltar, a fronteira entre os dois é HTTP (`/api/v1`), não import de código. O seed continua
no repositório do v2, lido por caminho relativo de irmão e nunca versionado aqui.

## Rodar o que existe

```bash
# estrutura do banco (cria <base>_teste, aplica o DDL duas vezes, roda as invariantes)
db/testar-schema.sh

# com o seed da v2 ao lado (1.807 contatos, 856 nós) — os repos são irmãos por desenho
SEED_JSON=../diretorio-contatos-v2/seed_mascarado.json db/testar-schema.sh

# app
(cd apps/web && { test -f .env || cp .env.example .env; } && ./vendor/bin/sail up -d \
  && ./vendor/bin/sail artisan key:generate && ./vendor/bin/sail artisan migrate --seed)
# sem Docker: DB_DATABASE=... php artisan migrate --seed (ver apps/web/README.md)

# imagem de produção (contexto apps/web)
docker build -t diretorio-contatos-v3 apps/web

# fumaça do protótipo — o alvo vive fora do repositório (ver tests/e2e/README.md)
cd "IDEIAS PARA O V3/wireframe" && php artisan serve --port=8123   # noutro terminal
BASE_URL=http://127.0.0.1:8123 node tests/e2e/fumaca.js
```

O script só age em banco terminado em `_teste`, então produção nunca é atingida por acidente.
Detalhe de cada arquivo de `db/`, pegadinhas já pagas e a medição que fixou o limiar de
similaridade em 0,45: `db/README.md`. `scripts/` guarda as ferramentas de base — reconciliação com
os catálogos, ordem do catálogo, coordenadas dos fóruns, captura e comparação do contrato do v2 e a
varredura de privacidade; cada uma abre com o porquê no docstring.

## Busca por sinônimos (ideia 04)

`GET /api/v1/busca?q=<termo>&limite=<1..20>` responde `termo`, `expansao`, `total` e `resultados` —
cada resultado com `tipo` (`comarca`, `local`, `no`, `contato`, `util`), `chave`, `rotulo`,
`contexto` e `pontuacao`. A varredura é trigrama (`pg_trgm`) sobre o texto normalizado
(`norm_texto`: sem acento, sem caixa) de 2.816 trechos, ordenada por semelhança; `total` é o tamanho
antes do corte e `limite` para em 20, sem devolver erro para quem pede mais.

Trigramas resolvem erro de digitação, não vocabulário: `reconhecimento de firma` e `cartorio` não
têm trigrama em comum e são a mesma pergunta. Por isso existe um léxico curado à mão
(`apps/web/database/seeders/SinonimoSeeder.php`, 39 termos) que **substitui** o termo digitado por
frases canônicas. O critério de aceite é literal e está em teste: a consulta com sinônimo devolve
**exatamente** o conjunto da consulta canônica — mesma ordem, mesmo `total`, não uma lista parecida.

### O que muda na prática

| Digite | Antes, só trigrama | Depois, com o léxico |
| :--- | :--- | :--- |
| `reconhecimento de firma` | 0 resultados | 154 linhas de cartório |
| `inss` | 98 linhas por letra parecida — e-mails com "ins" no meio | 8 linhas de previdência (perícia) |
| `testamento` | 56 linhas: CPD, assessoria de serventias, processamento de recursos | 11 linhas de órfãos e sucessões |
| `pequenas causas` | 0 resultados | 148 linhas de juizado especial |
| `medida protetiva` | 0 resultados | 8 linhas de violência doméstica e familiar |

### As 39 consultas medidas

Contagem de linhas por consulta, com `limite` desligado, no seed da v2 (1.807 contatos, 856 nós,
73 locais, 69 comarcas, 11 úteis) em 25/11/2026. "Antes" é a mesma busca com a expansão desligada — é o número
que o usuário veria hoje; "Depois" é com o léxico. Quando "antes" é maior que "depois", o trigrama
estava devolvendo letra parecida, não o serviço.

| Termo digitado | Expansão | Antes | Depois |
| :--- | :--- | ---: | ---: |
| `reconhecimento de firma` | `cartorio` | 0 | **154** |
| `firma reconhecida` | `cartorio` | 0 | **154** |
| `tabelionato` | `cartorio` | 0 | **154** |
| `escritura publica` | `cartorio` | 0 | **154** |
| `registro de imoveis` | `registro` | 0 | **18** |
| `certidao de nascimento` | `registro` | 0 | **18** |
| `pensao alimenticia` | `familia` | 0 | **42** |
| `divorcio` | `familia` | 0 | **42** |
| `guarda de filho` | `familia` | 0 | **42** |
| `heranca` | `sucessoes` | 13 | **11** |
| `inventario` | `sucessoes` | 0 | **11** |
| `testamento` | `sucessoes` | 56 | **11** |
| `tutela de menor` | `orfaos` | 0 | **15** |
| `conselho tutelar` | `infancia juventude` | 0 | **28** |
| `crianca e adolescente` | `infancia juventude` | 0 | **28** |
| `menor infrator` | `socioeducativo` | 0 | **1** |
| `justica do trabalho` | `trabalho` | 0 | **9** |
| `processo do trabalho` | `trabalho` | 0 | **9** |
| `inss` | `previdencia` | 98 | **8** |
| `aposentadoria` | `previdencia` | 73 | **8** |
| `pericia medica` | `previdencia` | 0 | **8** |
| `auxilio doenca` | `previdencia` | 0 | **8** |
| `intimacao` | `mandados` | 0 | **28** |
| `oficial de justica` | `oficiais de justica` | 37 | **37** |
| `marcar audiencia` | `audiencias` | 0 | **35** |
| `pequenas causas` | `juizado` | 0 | **148** |
| `juizado de pequenas causas` | `juizado` | 0 | **148** |
| `medida protetiva` | `violencia mulher` | 0 | **8** |
| `crime` | `criminal` | 96 | **131** |
| `juri popular` | `juri` | 0 | **23** |
| `tribunal do juri` | `juri` | 2 | **23** |
| `divida ativa` | `execucao fiscal` | 0 | **1** |
| `divida` | `execucao` | 1 | **15** |
| `iptu` | `fazenda` | 0 | **23** |
| `tributo` | `fazenda` | 4 | **23** |
| `balcao` | `atendimento` | 0 | **119** |
| `atendimento ao publico` | `atendimento` | 13 | **119** |
| `plantao judiciario` | `plantao` | 10 | **27** |
| `urgencia` | `plantao` | 0 | **27** |

A medição é reproduzível: `SinonimoSeeder::LEXICO` percorrido com `Buscar::executar($termo, 0, false)`
e `Buscar::executar($termo, 0, true)`. O critério de aceite é verificado no léxico inteiro: um teste de
feature percorre os 39 pares comparando o conjunto com o da consulta canônica, e três deles (cartório,
família, sucessões) têm caso explícito com fixture de diretório em
`apps/web/tests/Feature/BuscaTest.php`. Um teste de unidade recusa termo com acento, caixa alta ou
expansão vazia antes de o `CHECK` do banco derrubar o `migrate --seed`.

### Limites declarados

- O léxico expande a **consulta inteira**: `reconhecimento de firma em vitória` ainda não compõe com
  comarca. Entra quando houver caso real, com a consulta que motivou.
- O vocabulário é o do cidadão, não o do catálogo: nome próprio e erro de digitação continuam por
  conta do trigrama. Termo novo é decisão de revisão, não aprendizado automático.
- `pontuacao` ordena, não filtra: o conjunto é o da consulta canônica, e a nota serve para o
  desempate na tela.
- A tabela fala de linhas do banco, não de telas; agrupar contatos por nó na interface é trabalho da
  issue de busca na UI.

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
| `testes.yml` → `DDL e invariantes (Postgres 16)` | todo push e PR | `db/schema.sql` aplica duas vezes (idempotência) e as 45 invariantes estruturais passam |
| `testes.yml` → `Varredura do seed (a pedido)` | `workflow_dispatch` | o seed da v2, clonado na hora, também está limpo |
| `laravel.yml` → Pint, Larastan, Pest, Blade/rotas | todo push e PR | estilo PSR-12, tipos nível 5, fluxos de feature em Postgres 16 e compilação de view/rotas |

Commits seguem Conventional Commits (`feat:`, `fix:`, `docs:`, `ci:`, `chore:`…), um assunto por
commit; o squash preserva um commit por funcionalidade na `main`.

## Roadmap

O que ainda não existe está em [issues](https://github.com/EnzoMoulin/diretorio-contatos-v3/issues),
não em promessa de README. Duas listas se complementam:

- **issues abertas** — trabalho reconhecido: busca, mapa, paridade de
  contrato, acessibilidade no CI, plano de testes;
- `docs/backlog-ideias.md` — ideias com fase, gatilho e veredito, inclusive as descartadas e o
  motivo. Issue sem gatilho não entra em execução.

Com o esqueleto fechado, a prioridade seguinte é a **busca pública** e o **mapa geocodificado**,
nessa ordem — as duas issues abertas que herdam o contrato do v2 e o protótipo já revisado.

## Deploy

Blueprint do Render em `render.yaml`; o que não vai para o repositório são `APP_URL`, `APP_KEY` e
`DB_URL`, preenchidos no painel. Banco no Neon free (o Postgres do Render expira em 30 dias), fila
`sync` e health check em `/up`, a rota de health do próprio Laravel. Runbook e decisão em
`docs/plano-v3.md` §3.7 — `docs/fase-3-arquitetura-e-adrs.md` é documento histórico da v4, não runbook.
Ainda não deployado.

## Documentos

| Documento | Conteúdo |
| :--- | :--- |
| `docs/plano-v3.md` | **Plano vigente** (14/11/2026, encerrado em 20/11/2026): arquitetura, interface, telas e critérios de aceite. Onde houver conflito, vence sobre `fase-3`, `arquitetura-v3`, `v3-interface` e `v3-arquitetura-preparacao`. |
| `docs/arquitetura-v3.md` | Decisão de 10/11/2026: por que Laravel é a casca, por que o FastAPI fica para depois, o que acontece com o v2. Revisado em 12/11/2026 (a fase do FastAPI é a **v4**) e em 25/11/2026: a v4 está **adiada** (ADR-010). |
| `docs/v3-arquitetura-preparacao.md` | Plano anterior à consolidação do `plano-v3.md`; preservado como registro do que a v3 entrega e do que o v2 deixa como especificação. |
| `docs/v3-interface.md` | Briefing original da direção A (11/11/2026): mapa real do ES, layout de celular, sete estados, restrições de marca. Executado como protótipo fora do repositório; hoje é registro da decisão. |
| `docs/revisao-claude-wireframe-*-2026-11-1*.md` | Triagem das três rodadas de revisão externa do wireframe: o que procedia, o que foi corrigido no protótipo e o que virou decisão de produto. |
| `docs/adr-008-v3-um-servico-e-fastapi-na-v4.md` | ADR de 12/11/2026: v3 com um serviço só, banco único. **Substituído pelo ADR-010 na parte do FastAPI.** |
| `docs/adr-009-um-repo-para-v3-e-v4.md` | ADR de 12/11/2026: v3 e v4 num repositório, `db/` compartilhado. **Revisado em 25/11: um deployável, v4 adiada.** |
| `docs/adr-010-backend-unico-em-laravel.md` | ADR de 14/11/2026: backend único em Laravel, sem serviço FastAPI; aposenta o marco "v4". |
| `docs/backlog-ideias.md` | Ideias de produto com fase, gatilho e veredito — inclusive as descartadas, com motivo. |
| `docs/paridade-contrato-v2.md` | Contrato de leitura congelado: o que a v3 tem de devolver igual ao v2, e onde a diferença é decisão. |
| `docs/reconciliacao-2026-11-12.md` | Laudo de aderência da base aos três catálogos oficiais, com as divergências e o que o método não prova. É a **base final de demonstração** da v3. |
| `docs/fase-3-arquitetura-e-adrs.md` | **Documento histórico**: o desenho de dois serviços (Laravel + motor FastAPI), preservado como especificação da v4, que está adiada. Tem aviso no topo; não é o plano da v3. |
| `docs/identidade-visual.md` | Direção A, mapa protagonista. Inclui o que **não** fazer: `direcoes-visuais.html` é instrumento de decisão, não markup. |
| `db/README.md` | O banco lógico: tabelas, invariantes, medições e as regras que ficam na aplicação. |

## Base de demonstração (congelada)

A base da v3 é a que foi conferida em 12/11/2026 contra os três catálogos
oficiais: **69 comarcas + Sede · 72 fóruns · 856 nós · 1.807 contatos · 11 telefones
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

O v2 não ganha funcionalidade nova: continua público, como evidência do arco v1→v2, e só
muda por correção pontual ou por evidência visual (screencast e capturas). O único
acréscimo de escopo permitido é teste de privacidade, que não muda a aplicação (o detector de
nome de pessoa mora lá também, ver `tests/test_sem_telefone_real.py`). O que se escreve
do zero é esta versão — e um dos critérios é não herdar código: a similaridade com o v1 fica
em ≤10%. A comparação lado a lado dos três está no README do v1 e do v2.

## Licença

Direitos reservados. Ver `LICENSE`.
