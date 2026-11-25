# `apps/web/database/` — migrations do Laravel

A camada de banco e o esqueleto da aplicação estão versionados. As migrations são a **única** fonte
de DDL do app: o baseline não copia o schema para PHP, ele executa `db/schema.sql` inteiro.

## Ordem

| Migration | O que cria | Depende de |
| :--- | :--- | :--- |
| `0001_01_01_000000_criar_baseline_do_diretorio.php` | as 7 tabelas de `db/schema.sql` (comarcas, locais, nós, contatos, telefones úteis, histórico e o agregado do expurgo), funções, índices e triggers | das extensões `pg_trgm`/`unaccent`, criadas pelo próprio baseline |
| `0001_01_01_000001_criar_identidade_e_escopo.php` | `usuarios`, `password_reset_tokens`, `acessos`, `pedidos` | do `marca_atualizacao()` do baseline |
| `0001_01_01_000002_criar_sessoes_e_cache.php` | `sessions`, `cache`, `cache_locks` | de `usuarios` (FK de `sessions.user_id`) |

A ordem importa: `sessions` tem FK para `usuarios`, e as duas migrations de app usam a função de
trigger criada no baseline. Não renomeie os arquivos para mudar a ordem — o timestamp do nome é o que
o Laravel ordena.

## Rodar

Pré-requisitos: o app em `apps/web/` com `vendor/` e PHP 8.4 com `pdo_pgsql`. Com Sail:

```bash
cd apps/web
cp .env.example .env
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
```

Sem Docker, aponte o `.env` para um Postgres 16 local e rode `php artisan migrate`. O protótipo fora
do git (`IDEIAS PARA O V3/wireframe`) continua sendo referência visual, não app de apoio.

Se precisar rodar estas migrations a partir de outro app Laravel, `--path` + `--realpath` são
obrigatórios — também no **rollback**: sem eles, o Laravel procura as migrations em
`database/migrations` do app de apoio, não encontra os arquivos e responde `Migration not found`.

O baseline usa `dirname(__DIR__, 4).'/db/schema.sql'`; a partir de `apps/web/database/migrations`
isso resolve para a raiz do repositório, tanto no host quanto no container (`/app`).

## Provar

O teste mínimo, num banco vazio e descartável:

1. `migrate` → 16 tabelas em `public`: `migrations` + 8 do baseline + 7 do app.
2. `historico` tem `entidade_chave`, `antes` e `depois`; **não** tem `contato_chave`, `contato_rotulo`
   nem `detalhe`.
3. `sessions.user_id` tem FK para `usuarios`; `usuarios` e `pedidos` têm o trigger
   `marca_atualizacao()`.
4. `migrate:rollback --path=... --realpath` deixa só `migrations` (as funções e extensões ficam, de
   propósito: o `down()` não as derruba).
5. `migrate` de novo recria tudo — é o que prova que o rollback não deixou sujeira.

O seed não é migration: `db/carregar-seed.py` emite o SQL e `psql -1 -f` carrega num banco já migrado.
Ele é andaime de teste, não o ingestão oficial.

## Três armadilhas

- **`composer create-project` exige diretório vazio.** Foi o que aconteceu aqui: o esqueleto nasceu
  em `/tmp/esq`, foi copiado para `apps/web/` com `rsync` e as migrations desta pasta ficaram no
  lugar. Não rode `composer create-project` direto em `apps/web/` com esta pasta no lugar.
- **Apague as migrations padrão que o esqueleto traz.** O Laravel 13 gera
  `0001_01_01_000000_create_users_table`, `..._000001_create_cache_table` e `..._000002_create_jobs_table`;
  as duas primeiras criam `password_reset_tokens`, `sessions`, `cache` e `cache_locks`, que já nascem
  aqui. Sem apagá-las, o primeiro `migrate` falha por tabela duplicada — e `jobs` não serve, porque a
  fila é `sync`; a tabela de usuários é `usuarios`, não `users`.
- **`pdo_pgsql` não vem no PHP desta máquina.** O scaffold rodou com o static-php 8.4.23, que já traz
  `pdo_pgsql`, `pgsql`, `intl`, `zip` e `mbstring`; o PHP 8.3 do sistema segue sem o driver. No CI,
  `laravel.yml` instala `pdo_pgsql`/`pgsql` e roda contra Postgres 16.
