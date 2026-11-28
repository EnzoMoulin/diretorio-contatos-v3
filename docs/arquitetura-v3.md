# Arquitetura do v3 — decisão de 10/11/2026

Este documento registra a decisão tomada hoje. Ele **substitui** a formulação anterior
(v3 = FastAPI/Postgres com front estático separado no Cloudflare Pages), registrada em
07/11/2026 no `PROJETOS-FUTUROS.md`. A fila de projetos e as regras de operação continuam
lá; aqui fica só o desenho técnico e os números que o sustentam.

**Revisão de 12/11/2026:** a fase do FastAPI passou a se chamar **v4** (antes "v3.1", nome que
aparecia aqui, no README e no `PROJETOS-FUTUROS.md`), e o plano de dois serviços do
`fase-3-arquitetura-e-adrs.md` fica preservado como especificação da v4. A v3 é um serviço só —
o ETL e a hierarquia que a tabela abaixo mandava portar para FastAPI são reescritos em seeders e
models do Laravel. Decisão: `adr-008-v3-um-servico-e-fastapi-na-v4.md`; o FastAPI e o marco "v4"
foram aposentados pelo `adr-010-backend-unico-em-laravel.md`, e o plano vigente é `plano-v3.md`.

## Decisão

**v3 — Laravel 13 (PHP 8.3+) + TALL (Tailwind, Alpine, Livewire) + Filament + Postgres (Neon),
em um único serviço Docker no Render free.** HTML e JSON saem do mesmo processo.

**v4 — serviço FastAPI separado (fora de escopo pelo ADR-010)**, apontado para o mesmo Postgres do Neon, com trabalho
que o Laravel faria mal e com degradação graciosa: se o serviço estiver dormindo ou fora, o
Laravel responde a consulta básica.

**O v2 não é tocado.** Ele permanece público, congelado como evidência do arco v1→v2 e com o
demo suspenso (nunca deletado) quando o v3 entrar. O que se escreve do zero é o v3.

## Por que o Laravel é a casca

- É a única lacuna grande do portfólio: hoje o GitHub prova Apps Script (v1), Python stdlib
  (v2) e JavaScript — e nenhuma linha de Laravel/TALL, embora o título do LinkedIn e o CV
  comecem por "PHP & Laravel".
- Entrega, prontos, os itens que estavam pendentes no escopo do v3: migrations, RBAC
  (policies/Gates), paginação, validação e backoffice (Filament).
- O item 3 da fila ("projeto TALL/Laravel") deixa de ser um produto separado com o mesmo
  domínio. Sem isso, o mesmo diretório seria construído duas vezes.

## Por que o FastAPI fica para depois

Dois backends no caminho crítico do primeiro deploy é risco sem retorno: no free tier não
existe private service, então os dois serviços são HTTPS públicos e a fronteira entre eles é
token, não rede. Além disso o cold start dobra. O satélite — hoje **v4** — entra quando tiver uma
medição que justifique a existência dele.

## O que a troca custa, medido no v2

`app.py` tem 2.003 linhas. O destino de cada bloco:

| Bloco do v2 | Linhas | Destino no v3 |
| :--- | ---: | :--- |
| `class Handler` (roteamento HTTP) | 992 | rotas + controllers + FormRequests |
| `SCHEMA` | 122 | migrations |
| `_carregar_seed_legado` + `_carregar_seed_json` | 230 | **seeder do Laravel** (o ETL com `ftfy` é da v4) |
| `build_hierarquia` | 114 | **seeders + models** do Laravel |
| `_unificar_emails` | 37 | **seeder** do Laravel (o motor de deduplicação é da v4) |
| `init_fts` (FTS5 do SQLite) | 26 | **reescrita** — FTS5 não existe no Postgres (`tsvector`/`pg_trgm`) |
| OAuth Google | 78 | Socialite |
| hash de senha (PBKDF2 + upgrade) | 32 | `Hash` (argon2id) |
| `_send_email` | 27 | `Mail` + API de e-mail |
| aprovar/recusar pedido | 96 | controllers + policies |

**Nenhuma dessas linhas vai para Python na v3.** O bloco de ETL e hierarquia (381 linhas, 19% do
`app.py`, 407 com o FTS) é reescrito em seeders e models do Laravel, como o resto; o FTS do SQLite
é reescrito com `pg_trgm`/`tsvector`. O que justifica o FastAPI — `ftfy`, similaridade,
fonética em português, relatórios — é escopo da **v4** e será medido lá.

O front é o custo maior: **4.780 linhas** (public.js 1.461, admin.js 2.073, mapa.js 110,
HTML 594, CSS 542). Esse custo **não é criado pela troca de stack** — o v3 já tinha, desde
04/11, o requisito de ≤10% de similaridade com `public.js` do v1 e zero classes `fc-*`, ou
seja, o front seria reescrito de qualquer forma. A troca muda a ferramenta (Blade/Livewire/
Alpine em vez de JS puro), não a existência do trabalho.

Testes: **1.181 linhas** de `unittest` a reimplementar em Pest/PHPUnit. Os casos servem de
especificação; o código, não.

## Custos e riscos aceitos

1. **Render não tem runtime PHP nativo** — Laravel sobe por Dockerfile. Resolvido neste
   esqueleto; a consequência é build de imagem (consome pipeline minutes) em cada deploy.
2. **Sem pre-deploy command no free tier** — as migrations rodam no `entrypoint.sh`, antes do
   php-fpm. No wake do serviço, `migrate --force` roda de novo e é no-op.
3. **Cold start passa a cobrir a página inteira.** Com o front estático separado, o HTML abria
   na hora e só a API acordava. Com Blade, o HTML vem do container: ~1 min de espera no
   primeiro acesso depois de 15 min ocioso. Aceito; o README declara o cold start e o link
   durável divulgado continua sendo o repositório.
4. **Sem Redis no free** — `SESSION_DRIVER`, `CACHE_STORE` e `QUEUE_CONNECTION` em `database`.
5. **SMTP bloqueado no free** (portas 25/465/587) — e-mail só por API (Resend/Brevo).
6. **`APP_KEY` e `DB_URL` nunca no repositório** — `sync: false` no `render.yaml`, preenchidos
   no painel.

## O que este esqueleto é e o que ele não é

Arquivos escritos e não executados: `Dockerfile`, `docker/nginx.conf.template`,
`docker/php.ini`, `docker/entrypoint.sh`, `.dockerignore`, `render.yaml`.

Verificado de fato: `sh -n` no entrypoint (sintaxe ok), `render.yaml` (YAML válido, 18 env
vars), existência das imagens-base `php:8.4-fpm-alpine`, `composer:2` e `node:22-alpine` no
Docker Hub.

**Não verificado:** o build da imagem. Não há Docker nesta máquina e o código Laravel ainda
não existe. O primeiro build de verdade é o primeiro deploy no Render — o caminho de validação
abaixo foi escrito para isso.

## Caminho de validação (antes de escrever código de produto)

1. `composer create-project laravel/laravel` na raiz deste diretório.
2. Copiar o esqueleto por cima (`Dockerfile`, `docker/`, `.dockerignore`, `render.yaml`).
3. `php artisan key:generate --show` e guardar o valor para o painel do Render.
4. Criar o projeto no Neon free, copiar a connection string (já vem com `sslmode=require`).
5. Push; no Render, criar o Blueprint a partir do `render.yaml`, preencher `APP_KEY`, `DB_URL`,
   `APP_URL`.
6. Esperar o build, abrir `/up` (health route padrão do Laravel). Se `/up` responde 200, o
   caminho Docker + Neon + migrations está provado — e só então começar models, migrations e
   rotas do diretório.

Ponto de atenção no passo 6: a health route só responde depois que `migrate --force` terminou,
porque ela roda no entrypoint. Se o Neon estiver suspenso (scale-to-zero em 5 min), o primeiro
boot inclui a latência de acordar o banco.
