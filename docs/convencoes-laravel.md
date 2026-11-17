# Convenções de código — Laravel (v3)

Registro de 17/11/2026. Vale para `apps/web/` no dia em que o esqueleto existir: a régua vem
antes do código, porque convenção decidida depois é convenção negociada commit a commit.

Cada regra abaixo responde três perguntas: **o que**, **por que** e **quem faz cumprir**. O que
não tem mecanismo de enforcement está marcado como revisão humana — e revisão humana é a
última linha, não a única.

## Onde cada tipo de código mora

O controller é o lugar mais fácil de esconder regra de negócio. A divisão é fixa:

| Peça | O que entra | O que **não** entra | Quem faz cumprir |
| :--- | :--- | :--- | :--- |
| `app/Http/Controllers` | orquestração: recebe `FormRequest` validado, chama uma `Action`, devolve `Resource` ou view | `validate()`, `if` de regra de negócio, `DB::` | revisão (alvo: ≤ 15 linhas por método) |
| `app/Http/Requests` | validação de entrada, `authorize()`, mensagens | consulta ao banco que não seja checagem de autorização | Pest (feature test cobre a regra) |
| `app/Actions` / `app/Services` | caso de uso inteiro: transação, evento, efeito colateral | `Request`, `Response`, `session()` | Pest (teste da action, sem HTTP) |
| `app/Http/Resources` | formato de saída (JSON), nomes de campo do contrato | regra de negócio, consulta | Pest + paridade de contrato |
| `app/Models` | relação, escopo, cast, acessor | consulta pesada, chamada HTTP, e-mail | Pest + Larastan |

Regra de bolso: se um teste de unidade da peça precisa de `actingAs()` ou de `getJson()`, ela
está na camada errada.

## Pint — estilo (PSR-12)

- Preset `laravel` (que é PSR-12 com as escolhas do framework), zero config própria no começo.
  Config divergente é dívida: cada opção muda o diff de todo mundo.
- Local: `./vendor/bin/pint` antes do commit (reescreve). No CI: `--test` (só reporta).
- **Por que não no hook de pre-commit:** o hook local já barra commit em `main` e mensagem fora
  do padrão; somar reescrita de arquivo ali esconde o que foi alterado entre o `git add` e o
  commit. Formatação é passo explícito.
- Falhou no CI = o autor roda `pint` e commita o resultado. Nunca `pint --test` com `continue-on-error`.

## Larastan — análise estática

- `larastan/larastan` no nível **5** no primeiro PR, subindo um nível por marco fechado.
  Começar em 9 num projeto de banco grande produz uma lista que ninguém lê e que vira
  `@phpstan-ignore` em cascata — pior que nível baixo cumprido.
- `phpstan.neon` versionado, com `paths: [app, routes, database]`. `tests/` entra depois que a
  suíte existir (os helpers do Pest confundem o analisador no começo).
- Supressão exige comentário de uma linha dizendo **por que** o tipo é desconhecido. Suppress
  sem motivo é removido na revisão.
- Falhou no CI = corrigir o tipo ou estreitar o nível do arquivo; não baixar o nível global.

## Pest — testes

- **Feature test cobre o fluxo principal**, do HTTP ao banco. Unit test só onde a regra é
  aritmética/transformação pura e o teste de feature ficaria caro.
- Nome do teste descreve o comportamento em português, não o método:
  `it('devolve 404 quando a chave lógica não existe')` — é o que aparece no relatório e no
  portfólio.
- `RefreshDatabase` + seed mínimo por teste. Teste que depende de dado deixado por outro teste
  é falha intermitente esperando a hora.
- Banco de teste é **Postgres 16**, nunca SQLite: `pg_trgm`, `unaccent` e o DDL de `db/` são
  Postgres. Suíte verde em SQLite não prova a consulta de produção.
- Cada invariante de `db/verificar-schema.sql` que a aplicação pode violar ganha um teste de
  feature correspondente. O `db/testar-schema.sh` continua rodando como está — ele prova o
  schema, o Pest prova a aplicação sobre ele.

## Sail — ambiente de quem clona

- `compose.yaml` de **desenvolvimento** (PHP, Postgres, Mailpit; Redis só se houver fila),
  versionado, com `./vendor/bin/sail up -d` funcionando do zero.
- O `Dockerfile` da raiz continua sendo o de **produção** (nginx + php-fpm, migrations no
  entrypoint). Sail não é usado em produção e não substitui o build de deploy.
- `.env.example` completo: quem clona não deve precisar adivinhar variável. Segredo real nunca
  no `.env.example`.

## Banco: migrations, factories, seeders

- `php artisan migrate --seed` num banco limpo tem de deixar a aplicação utilizável: um
  administrador e dados realistas. É a promessa do README e o CI a executa em todo PR
  (`laravel.yml`, job `Pest`).
- Migration descreve o schema; `db/schema.sql` é a fonte das invariantes. Divergência entre os
  dois é bug de migration, não de DDL — o CI roda os dois.
- Factory nunca gera telefone, e-mail ou nome de pessoa fora do padrão sintético: a varredura
  de privacidade (`scripts/varrer_privacidade.py`) roda no mesmo PR e falha se escapar.
- Seeder de demonstração lê o seed da v2 (`../diretorio-contatos-v2/seed_mascarado.json`),
  nunca copia dado para dentro do repositório.

## Pipeline (`laravel.yml`)

| Job | Comando | Vermelho significa |
| :--- | :--- | :--- |
| `Pint (PSR-12)` | `vendor/bin/pint --test` | estilo fora do padrão; rodar `pint` e commitar |
| `Larastan (análise estática)` | `vendor/bin/phpstan analyse` | tipo errado ou contrato quebrado antes de rodar |
| `Pest (Feature)` | `php artisan test --compact` | comportamento errado — ou `migrate --seed` quebrado |
| `Blade e rotas (sem servidor)` | `route:cache`, `view:cache` | view com erro de compilação ou rota com nome repetido |

O workflow é disparado por `paths` de `apps/web/**`: documentação e `db/` não pagam o custo do
Composer. Os jobs de privacidade e DDL ficam em `testes.yml` e rodam em **todo** PR.

## O que fica de fora, de propósito

- **Dusk/Cypress no v3 agora:** a fumaça em navegador real já existe no v2 (Playwright) e o
  custo no v3 é alto antes de haver tela. Quando a busca pública fechar, entra device mobile
  (`Pixel 7`) — é a lacuna 5 registrada no `PROJETOS-FUTUROS.md` do workspace.
- **Cobertura como gate:** medir primeiro (lacuna 1), exigir depois. Gate em número não medido
  produz teste de fachada.
- **PHP_CodeSniffer além do Pint:** dois formatadores na mesma árvore brigam.
- **`composer audit` bloqueante agora:** advisory de dependência de desenvolvimento bloqueia PR
  que não tem relação com ele. Vira job informativo (`continue-on-error`) e depois gate.

## Checklist de PR

O formulário está em `.github/pull_request_template.md`; o que importa nele:

1. **Issue vinculada** (`Closes #N`) — problema e código no mesmo fio.
2. **Trade-offs escritos** — o que foi descartado e por quê.
3. **Validação medida** — comando rodado e resultado, não "testei aqui".
