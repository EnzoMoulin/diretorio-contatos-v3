# Diretório de Contatos — v3 (Laravel 13)

[![Testes](https://github.com/EnzoMoulin/diretorio-contatos-v3/actions/workflows/testes.yml/badge.svg)](https://github.com/EnzoMoulin/diretorio-contatos-v3/actions/workflows/testes.yml)
[![Laravel](https://github.com/EnzoMoulin/diretorio-contatos-v3/actions/workflows/laravel.yml/badge.svg)](https://github.com/EnzoMoulin/diretorio-contatos-v3/actions/workflows/laravel.yml)

**O que este repositório é, em engenharia:** consulta pública de contatos de uma estrutura em quatro
níveis (comarca → local → unidade → setor), com 1.807 contatos e 856 nós na base de demonstração, em
Laravel 13 + PHP 8.4 + PostgreSQL. HTML e JSON saem do mesmo processo (Blade e `/api/v1`), e a API
segue o formato da [v2](https://github.com/EnzoMoulin/diretorio-contatos-v2). A autorização de edição por escopo já está
modelada e testada em Policies; a área de gestão ainda não tem tela. É a terceira fase do mesmo
produto — v1 em Apps Script (repositório privado), v2 em Python stdlib. Projeto de portfólio;
**todos os dados são fictícios** — ver `LICENSE`.

> **Como este repositório foi feito.** Parte da documentação e das rodadas de revisão foi produzida
> com apoio de agentes de IA (Hermes e Claude). As decisões de produto, o código e a verificação são
> meus; os documentos registram o processo, inclusive os caminhos descartados.

## Estado

- **Na `main`:** busca na API com léxico de sinônimos (`GET /api/v1/busca`); listagens por canal em
  `/telefones` (1.019), `/emails` (297) e `/whatsapp` (47), com filtro por comarca, local, unidade ou
  setor e folha A4 do recorte; índice reverso de ramais em `/ramais` (444 ramais); autorização por
  escopo (`DiretorioPolicy`) com testes.
- **Em aberto** ([issues](https://github.com/EnzoMoulin/diretorio-contatos-v3/issues)): o mapa geocodificado por CEP (#5), a
  paridade de `contatos` e `contatos_q` com a v2 (#43) e o plano de testes com rastreabilidade (#10). A interface
  de busca ainda não tem issue.
- **Adiado:** o painel Filament. **Fora de escopo:** o marco v4 (FastAPI), aposentado pelo ADR-010; a especificação fica congelada e só reabre com requisito medido.
- **Deploy:** blueprint do Render pronto (`render.yaml`, Postgres no Neon); ainda não publicado.

## O que olhar no código

| Para ver | Comece por |
| :--- | :--- |
| Autorização por escopo e gestão de acesso | `apps/web/app/Escopo/`, `apps/web/app/Policies/DiretorioPolicy.php`, `apps/web/app/Actions/ConcederAcesso.php`, `apps/web/tests/Feature/EscopoTest.php` |
| Leitura com filtros, paginação e contagem por canal | `apps/web/app/Actions/ListarContatos.php`, `apps/web/tests/Feature/ListagemContatosTest.php` |
| Busca com sinônimos | `apps/web/app/Actions/Buscar.php`, `apps/web/tests/Feature/BuscaTest.php` |
| Modelo de dados e invariantes | `db/schema.sql`, `db/README.md` |
| CI | `.github/workflows/laravel.yml` (Pint, Larastan, Pest e acessibilidade) e `testes.yml` (privacidade, DDL em Postgres 16 e contrato v2 × v3 sob demanda) |

## Status em detalhe

**Fase de fundação concluída: banco e esqueleto versionados.** O `apps/web/` tem Laravel 13 + PHP
8.4, migrations, models, seeders e a primeira leitura no banco (`GET /` e `GET /api/v1/hierarquia`).

A camada de autorização por nó está versionada: `EscopoResolvido` + `DiretorioPolicy` decidem edição
por comarca, local ou nó (`ADMIN` edita tudo; `GESTOR_COMARCA` e `EDITOR` dentro do escopo; `DEMO` só
lê), com testes de Policy sem HTTP. No comparador, `contatos` segue **pendente** porque o fixture
recorta por nome de comarca e falta `/api/v1/locais` para traduzir nome em chave; `contatos_q`
também, porque o `q` do v2 lê `nome`/`cargo`, colunas que o modelo do v3 não tem — a paridade dessa
rota está medida à mão em `docs/paridade-contrato-v2.md`.

O protótipo navegável da direção A, em Laravel + TALL, vive fora do repositório (`IDEIAS PARA O V3/`,
no `.gitignore`) e foi validado em nove rodadas de revisão, três delas com o Claude; a fumaça dele
está em `tests/e2e/` e vira o e2e da aplicação quando a rota do mapa existir.

| Já feito | Onde |
| :--- | :--- |
| Decisão de stack: **Laravel 13 + Postgres (Neon), um serviço Docker no Render free**; TALL e Filament decididos, ainda não instalados (ver *Stack*) | `docs/arquitetura-v3.md` |
| DDL completo e idempotente do banco `db_diretorio`, com invariantes e expurgo LGPD | `db/schema.sql`, `db/README.md` |
| **Migrations da camada de banco**: baseline que executa o `schema.sql`, identidade/escopo (`usuarios`, `acessos`, `pedidos`, `auditoria`) e sessions/cache do framework | `apps/web/database/migrations`, `apps/web/database/README.md` |
| **Esqueleto Laravel 13 em `apps/web/`**: models, factories sintéticas, seeders (seed mascarado da v2 por caminho), action + resource + controller da primeira leitura, capa em Blade | `apps/web/`, `apps/web/README.md` |
| **Índice reverso de ramais em `/ramais`** (issue #7): 444 ramais com comarca · local · unidade · setor, busca por prefixo e folha A4 de 17 páginas | `apps/web/app/Actions/ListarRamais.php`, `apps/web/resources/views/ramais.blade.php` |
| **Listagens de contatos por canal** (issue #43): `GET /api/v1/contatos` com `tipo` (`Telefone`, `WhatsApp`, `Ramal`, `E-mail`, `Fax`), comarca, local, nó e `q`; nó inclui os filhos, paginação com teto de 200 e contagem por canal no recorte; as três telas (`/telefones`, `/whatsapp`, `/emails`) com filtro por comarca, local, unidade ou setor e busca, e a **folha A4 do recorte** (`canal.*.impressao`), o recorte inteiro e sem os controles da tela | `apps/web/app/Actions/ListarContatos.php`, `apps/web/resources/views/lista-contatos-impressao.blade.php` |
| **Escopo e autorização por nó** (item 3): `EscopoResolvido` calculado uma vez por requisição, `DiretorioPolicy`, `Contato::editaveisPor()`, `ConcederAcesso`/`RevogarAcesso` com chave validada e `auditoria` das tabelas do app | `apps/web/app/Escopo`, `apps/web/app/Policies`, `apps/web/app/Actions`, `apps/web/tests/Feature/EscopoTest.php` |
| Direção visual escolhida: **A — mapa protagonista**, paleta slate + turquesa | `docs/identidade-visual.md` |
| Blueprint de deploy e imagem Docker (nginx + php-fpm, migrations no entrypoint) | `render.yaml`, `apps/web/Dockerfile`, `apps/web/docker/producao/` |
| CI: varredura de privacidade + DDL em Postgres 16 real | `.github/workflows/testes.yml` |
| **Gate de acessibilidade** (issue #9): axe-core com as regras WCAG 2.1 A/AA, foco por Tab, alvo de toque ≥ 44 px e relatório como artefato | `tests/acessibilidade/`, `docs/acessibilidade.md` |
| **Paridade de contrato v2 × v3 executável** (issue #8): `--v3 <url>` compara campo a campo e marca rota pendente em vez de escondê-la | `scripts/comparar-contrato.py`, `docs/paridade-contrato-v2.md` |
| **Fluxo de PR com rastreabilidade**: formulário de PR (`Closes #N`), formulários de issue e checks obrigatórios para entrar na `main` | `.github/`, seção *Fluxo de contribuição* abaixo |
| **Régua Laravel ativa**: Pint (PSR-12), Larastan (nível 5), Pest e cache de rotas/views, com os quatro jobs rodando em todo PR | `.github/workflows/laravel.yml`, `docs/convencoes-laravel.md` |
| **Protótipo navegável da direção A em Laravel + TALL**, com mapa real do ES e o seed da v2 — nove rodadas de revisão, mantido fora do git | `IDEIAS PARA O V3/wireframe`, `docs/plano-v3.md` |
| **Fumaça e2e do protótipo**: sete verificações em Chromium, saída em JSON | `tests/e2e/` |
| **Plano do v3 encerrado em 20/11/2026**: arquitetura, interface, telas, critérios de aceite e o que ficou fora, com o marco v4 hoje aposentado (ADR-010) | `docs/plano-v3.md` (vence onde houver conflito), `docs/adr-010-backend-unico-em-laravel.md` |
| **Decisão de 12/11/2026, revisada em 25/11: a v4 moraria neste repositório** — hoje um só deployável (`apps/web`), porque o marco v4 foi aposentado (ADR-010) | `docs/adr-009-um-repo-para-v3-e-v4.md`, `docs/adr-010-backend-unico-em-laravel.md` |
| Briefing original da direção A (11/11/2026) — executado como protótipo fora do repositório; registro da decisão, não briefing vigente | `docs/v3-interface.md`, `tests/e2e/` |

> **Números verificados.** As contagens que este repositório cita — 1.807 contatos, 856 nós,
> 69 comarcas, 73 locais (72 fóruns + a Sede), 11 úteis e os 94 casos de teste do v2 — foram medidas no
> `diretorio-contatos-v2` em 24/11/2026 e conferidas no marco `bb9b62b` (mesmo seed,
> `sha256 7ccf1a9c…3a308c`). No seed, há 70 entradas em `comarcas` (69 com IBGE + a Sede);
> na v3, a Sede vira o local `MUN0000` e a tabela `comarcas` fica com 69. A perna `SIMULACAO_AUTO_APROVAR=1`
> com `ADMIN_EMAILS` foi reconferida em 24/11/2026: `Ran 95`, 1 pulado, OK. O resto das contagens está em
> `diretorio-contatos-v2/scripts/contagens.json`, que o CI confere contra o README.

## Stack

- **Laravel 13 / PHP 8.4** serve HTML e JSON do mesmo processo (Blade).
- A stack decidida inclui **Tailwind + Alpine** no front e **Filament** no backoffice, mas **nenhum
  dos três está instalado ainda**: hoje as views usam CSS próprio e o app não tem `package.json`
  nem `livewire`/`filament` no `composer.lock`. É o que fecha a lacuna de Laravel no portfólio sem
  construir dois backends.
- **Postgres no Neon free**, fora do Render: o Postgres free do Render expira em 30 dias e o
  demo quebraria sozinho.
- **FastAPI na v4, fora de escopo** (ADR-010): a especificação fica congelada; só entra com medição que justifique — importação com
  `ftfy`, deduplicação e busca fonética em português. O `v3.0` é Laravel sozinho.

## Estrutura (v3 agora; v4 fora de escopo)

Decidido em 12/11/2026 (`docs/adr-009-um-repo-para-v3-e-v4.md`) e revisado em 25/11/2026
(`docs/adr-010-backend-unico-em-laravel.md`). A v4 não é um quarto repositório — mas também não é
fase em curso: o `v3.0` tem **um** deployável. O app está em `apps/web/`: migrations, models,
factories, seeders, a primeira leitura (capa e `/api/v1/hierarquia`) e a régua de estilo/tipo/teste.

```
apps/web/          Laravel 13 + Blade            → deployável 1 (Dockerfile próprio)
  app/             models, Action, Resource, controllers
  database/        migrations + factories + seeders
  docker/producao/ nginx, php.ini, entrypoint
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

# app — num clone novo, instale as dependências antes (vendor/ não é versionado)
(cd apps/web && docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/var/www/html" -w /var/www/html \
  laravelsail/php84-composer:latest composer install --ignore-platform-reqs)
(cd apps/web && { test -f .env || cp .env.example .env; } && ./vendor/bin/sail up -d \
  && ./vendor/bin/sail artisan key:generate && ./vendor/bin/sail artisan migrate --seed)
# sem o repo irmão montado, o seeder cria 12 contatos sintéticos (ver apps/web/README.md)
# sem Docker: DB_DATABASE=... php artisan migrate --seed

# imagem de produção (contexto apps/web)
docker build -t diretorio-contatos-v3 apps/web

# a fumaça do protótipo depende de um alvo fora do repositório: ver tests/e2e/README.md
```

O script só age em banco terminado em `_teste`, então produção nunca é atingida por acidente.
Detalhe de cada arquivo de `db/`, pegadinhas já pagas e a medição que fixou o limiar de
similaridade em 0,45: `db/README.md`. `scripts/` guarda as ferramentas de base — reconciliação com
os catálogos, ordem do catálogo, coordenadas dos fóruns, captura e comparação do contrato do v2 e a
varredura de privacidade; cada uma abre com o porquê no docstring.

## Listagens por canal (issue #43)

`GET /api/v1/contatos` devolve os contatos recortados por canal, com os filtros que o v2 tinha.
`tipo` é comparado normalizado (`E-MAIL` e `email` caem em `E-mail`); comarca, local e nó usam a
chave lógica (`MUN####`, `FOR####` — `MUN0000` é a Sede —, `UNI####`), e o nó inclui os filhos
diretos: o contato do cartório é o telefone que o balcão procura pelo nome da vara. A resposta traz
`total`, `paginas` e `totais` por canal **no mesmo recorte** — é o número das abas da tela.

| Parâmetro | Efeito |
| :--- | :--- |
| `tipo` | `Telefone` (1.019), `WhatsApp` (47), `Ramal` (444), `E-mail` (297) ou `Fax` (0) |
| `comarca` | chave `MUN####` (ex.: `MUN0001` = Vitória, 166 contatos) |
| `local` | chave `FOR####`; `MUN0000` é a Sede (286 contatos) |
| `no` | chave `UNI####`, com os filhos diretos |
| `q` | trecho no texto normalizado e nos dígitos: `cartorio` acha `Cartório` |
| `pagina` / `por_pagina` | 50 por padrão, teto de 200 |

A ordem é a do seed — local, depois o `ordem_idx` do catálogo —, não a data de criação. `q` aqui é
filtro, não busca: semelhança, ranking e erro de digitação continuam em `GET /api/v1/busca`.
Medido contra o seed em 25/11/2026: 1.807 contatos, `q=cartorio` 215, `q=sede` 286, `q=vitoria` 166.

### As telas

`/telefones`, `/whatsapp` e `/emails` são a mesma listagem com o eixo trocado (`ListagemContatosController`
recebe o canal por `defaults`). A tela tem barra de canais com a contagem do recorte, filtro por comarca,
local (agrupado por comarca, com a Sede no começo) e **unidade ou setor** (`UNI####`, com os filhos
diretos — escolher a vara traz o cartório — e o rótulo mostrando o pai), busca por texto, 100 linhas por
página, copiar e o link do canal — `tel:`, `mailto:` ou `wa.me/55`. Valor mascarado (`XXXX`) aparece como
texto, sem link: não há número para discar. O filtro por nó é encadeado na tela (comarca → local →
unidade), mas o servidor aceita qualquer combinação; sem JavaScript a lista continua escolhível.

Cada tela tem a **folha A4 do recorte** em rota própria (`/telefones/impressao`, `/whatsapp/impressao`,
`/emails/impressao`): o link "Folha A4" leva comarca, local, nó e busca, a folha não repete os controles
da tela e imprime o recorte inteiro, não a página de 100 — `ListarContatos` com `$porPagina = null` tira
o `LIMIT`. O papel pagina sozinho e o `<thead>` corrente (canal · recorte · total) se repete em cada
página. O `@media print` da tela continua imprimindo só a página; a peça completa é a folha.

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
73 locais, 69 comarcas, 11 úteis) em 25/11/2026. A tabela completa, com a leitura de cada termo e a
metodologia, está em [`docs/busca.md`](docs/busca.md).

O critério de aceite é verificado no léxico inteiro: um teste de
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
  interface de busca, que ainda não tem issue.

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
- **Checks obrigatórios.** O ruleset `protege-default` exige os jobs de teste verdes
  (varredura, DDL, Pint, Larastan, Pest, Blade/rotas) e a **acessibilidade** (WCAG 2.1 AA);
  recusa force-push e exclusão da `main`. PR vermelho não tem botão de merge.

| Workflow | Dispara em | Prova |
| :--- | :--- | :--- |
| `testes.yml` → `Varredura de privacidade` | todo push e PR | nenhum telefone/e-mail real e nenhum nome de pessoa fora do padrão sintético |
| `testes.yml` → `DDL e invariantes (Postgres 16)` | todo push e PR | `db/schema.sql` aplica duas vezes (idempotência) e as 45 invariantes estruturais passam |
| `testes.yml` → `Varredura do seed (a pedido)` | `workflow_dispatch` | o seed da v2, clonado na hora, também está limpo |
| `testes.yml` → `Contrato v2 × v3 (a pedido)` | `workflow_dispatch` | compara a v3 com o fixture do v2; rota pendente não falha (ainda não bloqueante) |
| `laravel.yml` → Pint, Larastan, Pest, Blade/rotas | todo push e PR | estilo PSR-12, tipos nível 5, fluxos de feature em Postgres 16 e compilação de view/rotas |
| `laravel.yml` → `Acessibilidade (WCAG 2.1 AA)` | todo push e PR | axe-core A/AA nas telas públicas, foco por Tab, alvo ≥ 44 px e contraste nos dois temas |

Commits seguem Conventional Commits (`feat:`, `fix:`, `docs:`, `ci:`, `chore:`…), um assunto por
commit; o squash preserva um commit por funcionalidade na `main`.

## Roadmap

O que ainda não existe está em [issues](https://github.com/EnzoMoulin/diretorio-contatos-v3/issues),
não em promessa de README. Duas listas se complementam:

- **issues abertas** — trabalho reconhecido: o mapa geocodificado, o comparador de `contatos`
  (#43) e o plano de testes com rastreabilidade;
- `docs/backlog-ideias.md` — ideias com fase, gatilho e veredito, inclusive as descartadas e o
  motivo. Issue sem gatilho não entra em execução.

Com o esqueleto fechado, a prioridade seguinte é o **mapa geocodificado** (issue #5). A **#43** está
entregue nas telas e na API: `GET /api/v1/contatos`, as três telas com os filtros do v2 — inclusive
o nó, que inclui os filhos —, o copiar, o `tel:`/`mailto:`/`wa.me` e a folha A4 por recorte. Falta
ligá-la ao comparador de contrato, que depende de `/api/v1/locais`.

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
| `docs/arquitetura-v3.md` | Decisão de 10/11/2026: por que Laravel é a casca, por que o FastAPI fica para depois, o que acontece com o v2. Revisado em 12/11/2026 (a fase do FastAPI é a **v4**) e em 25/11/2026: o marco v4 está **aposentado** (ADR-010). |
| `docs/busca.md` | As 39 consultas medidas do léxico de sinônimos: a tabela, a leitura de cada termo e a metodologia. |
| `docs/v3-arquitetura-preparacao.md` | Plano anterior à consolidação do `plano-v3.md`; preservado como registro do que a v3 entrega e do que o v2 deixa como especificação. |
| `docs/v3-interface.md` | Briefing original da direção A (11/11/2026): mapa real do ES, layout de celular, sete estados, restrições de marca. Executado como protótipo fora do repositório; hoje é registro da decisão. |
| `docs/revisao-claude-wireframe-*-2026-11-1*.md` | Triagem das três rodadas de revisão com o Claude do wireframe: o que procedia, o que foi corrigido no protótipo e o que virou decisão de produto. |
| `docs/adr-008-v3-um-servico-e-fastapi-na-v4.md` | ADR de 12/11/2026: v3 com um serviço só, banco único. **Substituído pelo ADR-010 na parte do FastAPI.** |
| `docs/adr-009-um-repo-para-v3-e-v4.md` | ADR de 12/11/2026: v3 e v4 num repositório, `db/` compartilhado. **Revisado em 25/11: um deployável; marco v4 aposentado (ADR-010).** |
| `docs/adr-010-backend-unico-em-laravel.md` | ADR de 14/11/2026: backend único em Laravel, sem serviço FastAPI; aposenta o marco "v4". |
| `docs/backlog-ideias.md` | Ideias de produto com fase, gatilho e veredito — inclusive as descartadas, com motivo. |
| `docs/paridade-contrato-v2.md` | Contrato de leitura congelado: o que a v3 tem de devolver igual ao v2, e onde a diferença é decisão. Inclui o modo `--v3` e o recall medido da busca. |
| `docs/acessibilidade.md` | Gate de acessibilidade (axe-core, regras WCAG 2.1 A/AA): o que mede, o que corrigiu, como rodar e por que é required check. |
| `docs/reconciliacao-2026-11-12.md` | Laudo de aderência da base aos três catálogos oficiais, com as divergências e o que o método não prova. É a **base final de demonstração** da v3. |
| `docs/fase-3-arquitetura-e-adrs.md` | **Documento histórico**: o desenho de dois serviços (Laravel + motor FastAPI), preservado como especificação da v4, fora de escopo. Tem aviso no topo; não é o plano da v3. |
| `docs/identidade-visual.md` | Direção A, mapa protagonista. Inclui o que **não** fazer: `direcoes-visuais.html` é instrumento de decisão, não markup. |
| `db/README.md` | O banco lógico: tabelas, invariantes, medições e as regras que ficam na aplicação. |

## Base de demonstração (congelada)

A base da v3 é a que foi conferida em 12/11/2026 contra os três catálogos
oficiais: **69 comarcas + Sede · 72 fóruns · 856 nós · 1.807 contatos · 11 telefones
úteis**, com 296 dos 297 e-mails confirmados por documento. Não há segunda conferência
a fazer enquanto a fonte não mudar.

Três garantias baratas acompanham isso:

- `../dados/catalogo-2026/base-verificada.json` (no workspace do autor, fora deste repositório) — manifesto com as contagens e o SHA-256
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
do zero é esta versão — e um dos critérios é não herdar código: similaridade de linhas ≤ 10% com o
v1 e o v2 (`docs/identidade-visual.md`), a medir quando a interface pública existir. A comparação
lado a lado das fases está no README do v2.

## Licença

Direitos reservados. Ver `LICENSE`.
