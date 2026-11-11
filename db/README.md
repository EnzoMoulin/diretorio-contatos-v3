# `db/` — o banco lógico `db_diretorio`

Materializa as seções 4.4, 4.5, 5.2, 5.3, 5.6 e 6.3 de
`docs/fase-3-arquitetura-e-adrs.md` (Apêndice D). O banco é do **motor FastAPI**; o Laravel
(`db_admin`) não escreve aqui, exceto em `referencias_externas`.

## Arquivos

| Arquivo | Papel |
| :--- | :--- |
| `schema.sql` | DDL completo e idempotente: extensões, `norm_texto`/`so_digitos`, 8 tabelas, índices, `CHECK`s, triggers. |
| `verificar-schema.sql` | 39 invariantes estruturais (o que cada `CHECK` deve recusar e aceitar). |
| `carregar-seed.py` | Emite o SQL de carga do `seed_mascarado.json` do v2. Andaime de teste, não o ingestão oficial. |
| `verificar-carga.sql` | 20 invariantes sobre a base carregada (volumes, vínculos, profundidade, procedência, busca). |
| `testar-schema.sh` | Cria `<base>_teste`, aplica o DDL duas vezes, roda tudo e apaga o banco. |

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
- expurgo do `historico` aos 180 dias (job do motor).

## Pegadinhas já pagas

- `lower(public.unaccent(x))` **não** entra em coluna gerada: `unaccent` é `STABLE`. Use
  `norm_texto(x)` — é `IMMUTABLE` e é a mesma função no índice e na consulta.
- A base publicada é mascarada (`(27) 3134-XXXX`, `XXXX` no ramal): o `CHECK` de telefone aceita as
  duas formas, senão o repositório público não carrega no próprio schema.
- `now()` é o início da transação: `atualizado_em` não muda dentro da mesma transação. Teste de
  trigger precisa desligar/ligar o trigger, não `pg_sleep`.
- `GENERATED ALWAYS AS IDENTITY` recusa `id` explícito: para testar ciclo, insira e faça `UPDATE`.
