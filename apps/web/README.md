# Aplicação — Diretório de Contatos v3

Laravel 13 + PHP 8.4 + Postgres 16. A camada de banco (migrations) chegou no
PR #34; este é o esqueleto da aplicação, com Pint, Larastan e Pest ligados.

## O que já funciona

- `GET /` — capa com os totais do diretório (comarcas, fóruns, nós, telefones úteis).
- `GET /api/v1/hierarquia` — mesma leitura em JSON, no formato do contrato do v2
  (`docs/paridade/esperado.json`, rota `hierarquia`).
- `GET /api/v1/busca?q=&limite=` — busca por trigrama sobre comarcas, locais, nós,
  contatos e telefones úteis, com léxico de sinônimos (`SinonimoSeeder`). O
  contrato está no README da raiz, seção *Busca por sinônimos*.
- `GET /up` — health check do Laravel (usado pelo Render).
- `php artisan migrate --seed` — cria o administrador e carrega o seed mascarado
  do v2 quando ele existe; sem o arquivo, gera um conjunto sintético.

## Subir com Sail

```bash
cp .env.example .env
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
```

A aplicação fica em `http://localhost` e o Mailpit em `http://localhost:8025`.

O administrador é criado com `ADMIN_EMAIL`. Se `ADMIN_SENHA` estiver vazio, o
seeder gera uma senha aleatória e a imprime **uma única vez** no terminal; copie
antes de fechar a saída do comando.

## Seed real x sintético

O seed mascarado do v2 não é copiado para este repositório. O seeder procura,
nesta ordem:

1. `SEED_JSON` (caminho absoluto ou relativo);
2. o irmão `../../diretorio-contatos-v2/seed_mascarado.json`;
3. se nenhum existir, gera 3 comarcas, 4 fóruns, 4 nós e 12 contatos sintéticos.

Para carregar o seed real a partir do clone do v2:

```bash
SEED_JSON=../../diretorio-contatos-v2/seed_mascarado.json \
    ./vendor/bin/sail artisan db:seed --class=DiretorioSeeder
```

O seeder é idempotente por `chave` (roda de novo sem duplicar).

O `SinonimoSeeder` não depende do seed do v2: o léxico de busca é dado de
referência e entra em todo `migrate --seed`, sintético ou real.

## Testes, estilo e tipos

Dentro do Sail:

```bash
./vendor/bin/sail artisan test --compact
./vendor/bin/sail exec laravel.test vendor/bin/pint --test
./vendor/bin/sail exec laravel.test vendor/bin/phpstan analyse
```

Sem Docker, com PHP 8.4 e um Postgres 16 à mão, use um banco de teste separado:

```bash
DB_DATABASE=diretorio_teste php artisan test --compact
```

Os testes usam `RefreshDatabase` e rodam em Postgres — não em SQLite — porque a
busca do v3 usa `pg_trgm` e `unaccent`, que o SQLite não tem.

## Produção

O `Dockerfile` embute nginx + php-fpm e é o runtime do Render (`render.yaml`
aponta para `apps/web/`). O entrypoint roda `config:cache` e `migrate --force`
antes de abrir a porta; é o que substitui o pre-deploy command, que não existe
no free tier. As variáveis obrigatórias são `APP_KEY`, `APP_URL`, `DB_URL` e
`ADMIN_EMAIL` (o seeder de produção não roda sozinho no boot).
