# `db/` — o banco lógico `db_diretorio`

Materializa as seções 4.4, 4.5, 5.2, 5.3, 5.6 e 6.3 de
`docs/fase-3-arquitetura-e-adrs.md` (Apêndice D), origem **histórica**: aquele documento descreve
o desenho de dois serviços, que o ADR-010 aposentou. Na v3 o banco é **único** e este DDL é o
baseline das migrations do Laravel (`docs/adr-010-backend-unico-em-laravel.md`). Identidade, permissão,
pedidos e auditoria administrativa **não estão aqui**: são tabelas do app, criadas por migrations do
Laravel no mesmo banco. `referencias_externas` e `idempotencia`, que existiam para o motor separado,
saíram do baseline em 25/11/2026.

## Arquivos

| Arquivo | Papel |
| :--- | :--- |
| `schema.sql` | DDL completo e idempotente: extensões, `norm_texto`/`so_digitos`, 7 tabelas (6 de dado + `historico_mensal`, o agregado do expurgo), índices, `CHECK`s, triggers. |
| `verificar-schema.sql` | 41 invariantes estruturais (o que cada `CHECK` deve recusar e aceitar). |
| `carregar-seed.py` | Emite o SQL de carga do `seed_mascarado.json` do v2. Andaime de teste, não o ingestão oficial. |
| `verificar-carga.sql` | 20 invariantes sobre a base carregada (volumes, vínculos, profundidade, procedência, busca). |
| `verificar-plano.sql` | 5 invariantes de **plano**: prova que o índice GIN serve a `busca_texto ILIKE norm_texto(...)` e que `lower(unaccent(valor))` não usa índice nenhum. |
| `medir-limiar.sql` | Mede `similarity`/`word_similarity` no vocabulário real e mostra de onde sai o `similarity_threshold` do motor. Não é invariante: é medição, com tabela na tela. |
| `testar-schema.sh` | Cria `<base>_teste`, aplica o DDL duas vezes, roda tudo e apaga o banco. |
| `expurgar-historico.sql` | Retenção de 180 dias: agrega em `historico_mensal` e apaga as linhas cruas, na mesma transação. `-v dias=N` muda o prazo; rodar de novo não faz nada (medido: 150 → 0). |

## Rodar

```bash
db/testar-schema.sh                                                  # só estrutura
SEED_JSON=../diretorio-contatos-v2/seed_mascarado.json db/testar-schema.sh   # + 1.807 contatos
DATABASE_URL='postgresql://…' db/testar-schema.sh                    # contra o Neon
```

Conexão pelas variáveis padrão do `psql` (`PGHOST`, `PGPORT`, `PGUSER`, `PGPASSWORD`) ou
`DATABASE_URL`. O script **só** trabalha em banco com nome terminado em `_teste` — o alvo é sempre
`<DB_BASE>_teste` (padrão `diretorio_teste`), então produção nunca é atingida.

## Aplicar em produção (Neon)

```bash
psql "$DATABASE_URL" -v ON_ERROR_STOP=1 -f db/schema.sql
```

O papel precisa ser dono do banco (para `CREATE EXTENSION`). Papéis e `GRANT`s estão comentados na
seção 11 do `schema.sql` e não são executados por ele.

## Regras que não são `CHECK`

O `CHECK` não enxerga outra linha; estas ficam na aplicação (e o `verificar-carga.sql` as mede):

- `nos.local_id` = `local_id` do pai; `contatos.local_id` = `local_id` do nó;
- profundidade ≤ 2 abaixo do local;
- deduplicação de contato — **não** existe `UNIQUE (local, nó, tipo, valor)` porque o mascaramento
  da base pública colapsa 1.510 valores em repetições falsas;
- vigência ("reconfirmar este contato") a partir de `fonte` + `vigente_desde`;
- `historico.entidade` só aceita entidade do **dado** (`contato`, `no`, `local`, `comarca`, `util`):
  `usuario`, `acesso` e `pedido` são tabelas do app e a auditoria delas não expira junto;
- expurgo do `historico` aos 180 dias (job do motor).

## Vocabulário: local, não órgão

A tabela âncora chama-se **`locais`** porque **fórum não é órgão**: o fórum é o prédio onde as varas
estão instaladas, o órgão é a Sede. Os dois ocupam o mesmo nível, então `locais.tipo` distingue
`FORUM` (sempre com comarca) de `SEDE` (nunca com comarca). Se aparecer `orgao` em algum lugar do
projeto, é texto antigo.

## O que a medição de limiar mostrou

`medir-limiar.sql` roda contra a base carregada. Resultado no seed (1.807 contatos, 467 palavras no
vocabulário de nomes):

| consulta | vizinho mais próximo entre **nomes inteiros** | entre **palavras** | `similarity` |
| :--- | :--- | :--- | ---: |
| `vitora` | `diretora` (errado) | `vitoria` | 0,500 |
| `cartoro` | `cartorio` | `cartorio` | 0,545 |
| `ciminal` | — | `criminal` | 0,545 |
| `unificad` | `2ª secretaria unificada` | `unificada` | 0,727 |

Conclusão que virou regra de projeto: **a sugestão de erro de digitação compara palavra, não nome
completo** — nome longo dilui o trigrama e `vitora` cai em `diretora`. Com o vocabulário reduzido a
palavras, o limiar **0,45** (o padrão do PostgreSQL) resolve os cinco casos de teste com 1 candidato
cada; não é preciso inventar número. A busca principal não usa limiar nenhum: é `ILIKE` sobre
`busca_texto`, filtrando por substring.

## Expurgo do histórico

`historico` guarda o retrato do contato; não pode ser arquivo permanente de dado pessoal. Passados
180 dias, o script agrega em `historico_mensal` (mês, ação, entidade, quantidade — sem nome, telefone,
e-mail, chave ou autor) e apaga a linha crua, tudo na mesma transação:

```bash
psql "$DATABASE_URL" -v ON_ERROR_STOP=1 -v dias=180 -f db/expurgar-historico.sql
```

Medido: 150 linhas velhas → 2 agregados, 5 linhas recentes intactas; segunda execução apaga 0. Sem
produção ainda, rode à mão depois de carga grande; com o motor no ar, cron diário às 03:00.

## Pegadinhas já pagas

- `lower(public.unaccent(x))` **não** entra em coluna gerada: `unaccent` é `STABLE`. Use
  `norm_texto(x)` — é `IMMUTABLE` e é a mesma função no índice e na consulta.
- A base publicada é mascarada (`(27) 3134-XXXX`, `XXXX` no ramal): o `CHECK` de telefone aceita as
  duas formas, senão o repositório público não carrega no próprio schema.
- `now()` é o início da transação: `atualizado_em` não muda dentro da mesma transação. Teste de
  trigger precisa desligar/ligar o trigger, não `pg_sleep`.
- `GENERATED ALWAYS AS IDENTITY` recusa `id` explícito: para testar ciclo, insira e faça `UPDATE`.
