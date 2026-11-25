# `apps/web/database/` — migrations do Laravel

A camada de banco da v3 já está versionada; o resto do esqueleto (`composer.json`, models, Livewire,
Filament) entra com a issue #4. As migrations são a **única** fonte de DDL do app: o baseline não
copia o schema para PHP, ele executa `db/schema.sql` inteiro.

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

Pré-requisitos: um app Laravel com `vendor/` e PHP com `pdo_pgsql`. Enquanto a issue #4 não fecha, o
protótipo fora do git serve de app de apoio:

```bash
cd "$HOME/Emprego/Projetos/diretorio-contatos-v3/IDEIAS PARA O V3/wireframe"
export DB_CONNECTION=pgsql DB_HOST=127.0.0.1 DB_PORT=5432 \
       DB_DATABASE=diretorio_migracoes_teste DB_USERNAME=enzo DB_PASSWORD=''
php artisan migrate \
  --path="$HOME/Emprego/Projetos/diretorio-contatos-v3/apps/web/database/migrations" \
  --realpath --force
```

`--path` + `--realpath` também são obrigatórios no **rollback**: sem eles, o Laravel procura as
migrations em `database/migrations` do app de apoio, não encontra os arquivos e responde
`Migration not found`.

O baseline usa `dirname(__DIR__, 4).'/db/schema.sql'`; a partir de `apps/web/database/migrations`
isso resolve para a raiz do repositório, tanto no host quanto no container (`/app`).

## Provar

O teste mínimo, num banco vazio e descartável:

1. `migrate` → 15 tabelas em `public`: `migrations` + 7 do baseline + 7 do app.
2. `historico` tem `entidade_chave`, `antes` e `depois`; **não** tem `contato_chave`, `contato_rotulo`
   nem `detalhe`.
3. `sessions.user_id` tem FK para `usuarios`; `usuarios` e `pedidos` têm o trigger
   `marca_atualizacao()`.
4. `migrate:rollback --path=... --realpath` deixa só `migrations` (as funções e extensões ficam, de
   propósito: o `down()` não as derruba).
5. `migrate` de novo recria tudo — é o que prova que o rollback não deixou sujeira.

O seed não é migration: `db/carregar-seed.py` emite o SQL e `psql -1 -f` carrega num banco já migrado.
Ele é andaime de teste, não o ingestão oficial.

## Duas armadilhas

- **`composer create-project` exige diretório vazio.** Quando a issue #4 rodar, crie o esqueleto num
  diretório temporário e mova `database/migrations/` para dentro dele; ou mova estas migrations para
  um lugar temporário, crie o projeto em `apps/web/` e devolva os arquivos. Não rode
  `composer create-project` direto em `apps/web/` com esta pasta no lugar.
- **`pdo_pgsql` não vem no PHP desta máquina.** Para o teste local, o driver foi carregado de um
  `.deb` extraído (`php8.3-pgsql`) com `php -d extension=.../pdo_pgsql.so`; a instalação definitiva
  (PHP 8.4 + composer) é o item adiado do início do v3. No CI, `laravel.yml` já instala
  `pdo_pgsql`/`pgsql` e roda contra Postgres 16.
