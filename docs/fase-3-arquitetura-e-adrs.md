# Diretório de Contatos — Fase 3: Arquitetura e Registro de Decisões (ADRs)

_10/11/2026 · base medida: `diretorio-contatos-v2` @ HEAD `d9a011a` · destino: `diretorio-contatos-v3`_

**Revisão 2 (10/11/2026, após aval do Enzo).** Fechados: chave lógica = id do seed
(`MUN0001` / `FOR0001` / `UNI0001` / `C000001` / `UTL0001`); banco = **Neon free**; modelo de nós e
busca decididos por delegação (ADR-006 e ADR-007). Os itens A-1, A-2, A-3 e A-4 da revisão 1 deixam
de existir; resta aberto apenas o login (seção 8).

**Revisão 3 (10/11/2026, correção do Enzo).** A Sede **não é comarca**: é órgão, no mesmo nível do
fórum. Isso muda o nível de ancoragem da árvore — `orgaos` (fóruns **e** Sede), não `foruns`, e
`comarcas` deixa de ser nível da árvore para virar dimensão geográfica (69 linhas, sem a linha falsa
`MUN0000`). Corrigido em 4.2, 4.4, ADR-006, seção 8, seção 9 e Apêndice A. Também corrigido um
número que eu havia citado errado: a estrutura central são **286 contatos**, não 1.139 (ver 4.4).

**Revisão 4 (11/11/2026, catálogo oficial em mãos).** Os dois volumes do catálogo — Comarcas
(`catalogo-comarcas-2026-06-26.pdf`, 41 páginas) e Sede (`catalogo-sede-2026-08-28.pdf`, 1 página) — confirmam
que cada órgão abre com um bloco de contatos que não pertence a unidade nem setor (*Geral · Sala ADM ·
Recepção · Segurança*; na Sede, `Geral 3334-XXXX · Telefonista XXXX•XXXX•X · Recepção XXXX`). Isso fecha
a pergunta dos contatos sem nó: entra a **5.6** (contato pertence a nó **ou** a órgão, com `orgao_id`
obrigatório e `no_id` nulo), sai a necessidade de setor virtual; os 5 contatos da Sede são o telefone
geral, três ramais de telefonista e a recepção. O volume da Sede também valida a árvore: as 21 unidades
de primeiro nível do seed aparecem todas no catálogo, e 148 dos 149 nós comparáveis batem por nome (os
30 restantes são os rótulos anonimizados de gabinete/comissário). Entram ainda o **Apêndice C** (runbook
de recarga do Neon, com a regra da Sede) e o critério 13 na seção 9. E uma retificação: o v2 **já**
separa a Sede da lista de comarcas na tela (menu próprio, `renderOrgao`); o que retorna 70 entradas é só
`/api/comarcas`.

**Revisão 5 (11/11/2026, terceiro documento da fonte).** Entra o *ofício de março/2024 – Diretoria
do Foro* (`oficio-2024-03.pdf`, março de 2024): 21 unidades da Capital, 23 e-mails e 20
telefones — é a origem exata dos **43 contatos** que o seed marca `Contato provisório — validar
vigência.`. A conferência fecha um a um (inclusive a 1ª Contadoria sem telefone e os dois e-mails do 2º e
do 3º JECrim) e acrescenta três exigências ao DDL de `contatos`: `descricao` obrigatória para e-mail,
`fonte` + `vigente_desde` no lugar do aviso em texto livre e normalização de telefone coberta por
`busca_digitos`. Detalhe em **5.6**; a divergência do `WhatsApp` da 2ª Contadoria fica registrada como
ponto a confirmar.

**Revisão 6 (11/11/2026, DDL executável).** O modelo desta especificação virou código: `db/schema.sql`
cria as 9 tabelas (8 de dado + `historico_mensal`, o agregado que sobrevive ao expurgo), as duas funções de normalização, os índices de busca e os `CHECK` que dão para
garantir no banco; `db/testar-schema.sh` aplica o DDL duas vezes num banco descartável e roda 39
invariantes; com `SEED_JSON=…` ele também carrega os 1.807 contatos e confere mais 20 números.
Tudo verde em PostgreSQL 16.15. Três achados que o papel não tinha: (i) `lower(unaccent(…))` **não**
compila em coluna gerada (`unaccent` é `STABLE`) — entra o invólucro `norm_texto`; (ii) o `CHECK` de
telefone precisa aceitar valor mascarado (`(27) 3134-XXXX`, `XXXX` no ramal), senão a base pública do
demo não carrega no próprio schema; (iii) `UNIQUE (local, nó, tipo, valor)` **não** entra como
índice: nos 297 contatos não mascarados do seed não há uma única repetição, mas o mascaramento
colapsa 1.510 valores (`XXXX` aparece 444 vezes, `(27) 3246-XXXX` 117) e criaria falso duplicado — a
deduplicação fica na aplicação, onde também se decide o que fazer com valor repetido legítimo. Detalhe e como rodar no **Apêndice D**. A reconciliação de nomes
contra o volume de comarcas também fechou: 69/69 comarcas e 72/72 fóruns do seed aparecem no catálogo
com o nome idêntico.

**Revisão 7 (11/11/2026, correção de vocabulário do Enzo).** **Fórum não é órgão.** O fórum é o prédio
onde as unidades estão instaladas; órgão é a Sede (e, na linguagem do ofício de março/2024, as unidades
administrativas). Os dois ocupam o mesmo nível da árvore, então a tabela âncora deixa de se chamar
`orgaos` e passa a **`locais`**, com `tipo` em `FORUM`/`SEDE`; `nos.orgao_id` e `contatos.orgao_id`
passam a `local_id`; as rotas viram `/api/v1/locais?comarca=` e `/locais?sede=1`. "Órgão" fica
reservado à Sede — inclusive na exceção da chave lógica `MUN0000`, que continua sendo a Sede e não uma
comarca. Corrigido em 1, 4.2, 4.4, ADR-006, 5.6, seções 8 e 9, Apêndices A, C e D e em `db/` (o DDL
testado roda com `locais`). As revisões 3 a 6 mantêm o nome antigo de propósito: são registro datado do
que se decidiu então.

**Revisão 8 (11/11/2026, preparação para publicar).** Passada de anonimização: o documento e o `db/`
exemplificavam as quatro grafias de telefone com os **números reais** do ofício de março/2024 — justamente o
que o seed do v2 mascara (`(27) 9XXXX-XXXX`). Agora os exemplos são de forma (`(27) 9XXXX-XXXX`,
`(27) 9XXXXXXXX`, `(27) 3XXXXXXX`, `(27) 9XXXX.XXXX`) e os `INSERT` de `db/verificar-schema.sql` usam
dígitos fictícios, com as invariantes de normalização ajustadas e verdes. Os nomes de unidade
*continuam*: são unidades públicas do tribunal (catálogo de ramais), não estrutura interna sigilosa —
trocá-los por genéricos apagaria a conferência que dá valor à 5.6. Nome de pessoa não existe em nenhum
artefato: os rótulos de gabinete/comissário já entram como `NN`.

**Precedência.** Este documento detalha a Fase 3 e **refina** `docs/arquitetura-v3.md` em dois
pontos (separação em dois bancos lógicos e contrato `/api/v1`), sem reabrir o que já está fechado
lá: Laravel como casca, FastAPI como motor, Postgres gerenciado, um serviço Docker no Render free.
Onde os dois divergirem, vale este; onde este cala, vale aquele. `PROJETOS-FUTUROS.md` continua
dono da fila de projetos e das regras de operação.

**Marcação de origem das afirmações.** `[decidido]` = decisão já registrada ou pedida
explicitamente; `[proposta]` = mecanismo que eu proponho para cumprir o que foi pedido, ainda sem
aval; `[aberto]` = ponto que precisa de decisão antes do código. A seção 8 consolida as três
listas. Nenhum número aqui é estimativa: os de código foram medidos no HEAD citado.

---

## 1. Vocabulário normativo

| Use | Não use | Por quê |
| :--- | :--- | :--- |
| padrões pragmáticos de sistemas distribuídos | padrões estritos de microsserviços | São **dois** serviços. O que se aplica é um conjunto pequeno e verificável (OpenAPI versionado, idempotência, chaves lógicas, correlation id, health/readiness). "Microsserviços" promete o que não existe aqui: service discovery, malha, broker, observabilidade distribuída. |
| chave de escopo de leitura / chave de escopo de escrita | chave pública | Não é par público/privado nem material de criptografia assimétrica: é credencial de serviço com escopo. "Pública" sugere que pode circular. |
| chave lógica imutável | id | Há dois identificadores: o interno (sequencial) e o lógico (string determinística). Chamar os dois de "id" foi a origem do acoplamento. |
| arquivamento (exclusão lógica) / exclusão física (expurgo) | soft delete, sem qualificar | A diferença entre os dois é obrigação legal e regra de integridade, não preferência de ORM. |
| orquestrador (Laravel) / motor do diretório (FastAPI) | backend / API | Os dois são backends. O que os distingue é quem decide (Laravel) e quem calcula sobre os dados do diretório (FastAPI). |
| requisição de leitura em lote | Bulk GET (em prosa) | Na rota pode ser `lote`; em texto, descrever o que é. |
| degradação graciosa | fallback | Nomeia comportamento esperado (responder consulta básica e declarar indisponibilidade), não um remendo. |
| local (fórum ou Sede) | comarca, para designar a Sede | A Sede **não é comarca**: é órgão no nível do local (do fórum), sem comarca, sem IBGE, sem geografia. Comarca é dimensão geográfica; **local** é nível da hierarquia; "órgão" fica reservado à Sede. |

---

## 2. Linha do tempo da evolução do sistema

| Fase | O que é | Prova que ela deixa | Papel |
| :--- | :--- | :--- | :--- |
| **1 — Origem** | Automação e MVP em Google Workspace (Apps Script + Google Sheets), sobre o caso real de estágio | Repositório privado institucional + cópia sanitizada em `~/Emprego/Projetos/diretorio-contatos` | Provar que o problema era real e que a modelagem em quatro níveis veio do uso, não do desenho |
| **2 — Atual** | Monólito em Python stdlib + SQLite expondo APIs REST, com dados fictícios, em ambiente de homologação | `diretorio-contatos-v2` público, CI em duas pernas, demo somente leitura | **Isolar o core do negócio e modelar o contrato** |
| **3 — Próxima** | Separação de responsabilidades: Laravel + Filament como orquestrador, FastAPI como motor do diretório, bancos lógicos isolados | (esta fase) | Provar arquitetura distribuída pragmática em PHP/Laravel sem perder o modelo de dados validado na Fase 2 |
| **4 — Futuro** | Motor de inteligência e qualidade de dados | — | Relatórios pesados, deduplicação assistida, enriquecimento e normalização de base |

### 2.1 Fase 1 — origem

Apps Script + Sheets, uma planilha, abas por nível, sem contrato de API e sem autorização própria
(a permissão era a do Drive). O que sobreviveu: o modelo comarca > fórum > unidade > setor, a
nomenclatura e a navegação em camadas. O que morreu: a lista plana da Sede (herança de planilha,
não de domínio) e a ideia de que a planilha era a base. Uma ressalva medida na Fase 2: a Sede não
morreu inteira — ficou no seed como linha de comarca (`MUN0000`, `tipo = ORGAO`) com 179 nós e 286
contatos pendurados nela. É esse resto de planilha que a Fase 3 desfaz (4.4).

### 2.2 Fase 2 — o papel estratégico (por que ela não é "código descartável")

A Fase 2 não existe para virar produto: existe para **isolar o core do negócio e modelar o
contrato** antes de qualquer framework entrar. Cada rota que o Laravel vai consumir na Fase 3 foi
exercitada aqui, com dados fictícios, sem ORM no meio.

Medido no HEAD `d9a011a`:

- `app.py`: **2.055 linhas**, `class Handler` com **1.027 linhas** e **26 rotas** (mais 3 de
  página/asset: `/healthz`, `/admin`, `/style.css` — 29 no total, o número do README).
- `SCHEMA`: **78 linhas**, **11 tabelas** (`comarcas`, `foruns`, `unidades`, `setores`, `contatos`,
  `uteis`, `historico`, `usuarios`, `notificacoes`, `acessos`, `pedidos`).
- Seed (`seed_mascarado.json`): **69 comarcas + 1 linha que é a Sede** (órgão, `MUN0000`, `tipo =
  ORGAO` — a planilha não distingue), **72 fóruns**, **838 nós** (598 `SETOR`, 210
  `UNIDADE`, 20 de outros tipos — `VARA`, `SECRETARIA`, `JUIZADO`, `SESAO`, `GABINETE`…),
  **1.807 contatos** (1.019 telefone, 444 ramal, 297 e-mail, 47 WhatsApp), **11 úteis**.
- Front-end: **4.786 linhas** (`public.js` 1.461, `admin.js` 2.079, `mapa.js` 110, CSS 542, HTML 594).
- Suíte: **1.288 linhas** em 5 arquivos (`test_regras` 561, `test_seguranca_demo` 323,
  `test_render_seguro` 283, `test_somente_leitura` 87, `test_saude` 34).

O que a Fase 2 prova e a Fase 3 herda: o modelo de quatro níveis, as contagens por nível (a família
de bug de contagem já está travada por teste), a busca, o fluxo pedido → aprovação → edição, e a
noção de escopo por nó (`acessos` + `_cobre`). O que ela prova de negativo, e a Fase 3 não repete:
o FTS5 do SQLite não existe no Postgres; a autorização embutida no mesmo processo não escala para
dois serviços; e o banco em arquivo recriado a cada boot obriga o front-end a tolerar recarga de
seed.

### 2.3 Fase 3 — próxima (escopo deste documento)

- **Orquestrador:** Laravel 13 + TALL + Filament. Dono de identidade, sessão, autorização,
  backoffice e do banco lógico `db_admin`.
- **Motor do diretório:** FastAPI. Dono do modelo do diretório, das consultas, do histórico do
  dado e do banco lógico `db_diretorio`.
- **Fronteira:** HTTP/JSON versionado em `/api/v1`, chaves lógicas imutáveis, idempotência em
  mutações, correlation id, health/readiness.
- **Bancos lógicos isolados** `db_admin` e `db_diretorio` no mesmo servidor Postgres — sem JOIN
  entre eles (ADR-001).

### 2.4 Fase 4 — o que já fica fixado agora

`[decidido]` O motor de relatórios da Fase 4 **não** usa `BackgroundTasks` puro do FastAPI. Motivo:
no Render free o processo é efêmero — dorme após 15 min sem tráfego e é reciclado a cada deploy; uma
tarefa em memória morre junto e o cliente fica esperando resposta que nunca chega. O desenho
obrigatório é:

- tabela de controle de jobs (`jobs_relatorio`) com estado **`pendente` → `processando` → `pronto` | `falhou`**,
  em `db_diretorio`, com `progresso`, `tentativas`, `erro`, `criado_em`, `iniciado_em`, `terminado_em`
  e `request_id` do pedido original;
- resposta **`202 Accepted`** imediata com `Location: /api/v1/relatorios/{chave}`;
- **endpoint de consulta** do estado e do artefato, com o resultado materializado (arquivo/JSON)
  para não reprocessar a cada consulta;
- retomada de jobs `processando` órfãos na subida do serviço (heartbeat + timeout), porque o
  processo pode morrer no meio.

`[decidido]` A biblioteca **`ftfy`** entra **estritamente na ingestão/importação** (correção de
mojibake no que chega de planilha/arquivo), **nunca por requisição web**. Dois motivos: custo de
CPU por requisição sem ganho (o dado já entrou corrigido) e, principalmente, determinismo — se o
mesmo payload pudesse sair diferente em duas chamadas, o `ETag` da leitura em lote e o hash de
idempotência das mutações deixariam de valer.

---

## 3. Mapa de distribuição de módulos

Linhas medidas no v2 (HEAD `d9a011a`); "banco afetado" é onde a **escrita** mora.

| Módulo | Origem no v2 (linhas) | Responsável na Fase 3 | Banco afetado |
| :--- | :--- | :--- | :--- |
| Roteamento HTTP e páginas | `class Handler` (1.027) | Laravel: rotas, controllers, Blade/Livewire | `db_admin` (sessão/RBAC); dados vêm do motor |
| Esquema de dados | `SCHEMA` (78) | migrations do Laravel (`db_admin`) + migrations do motor (`db_diretorio`) | ambos |
| Seed, ETL e ingestão | `_carregar_seed_json` (139) | **FastAPI** (é ingestão de dado do diretório; `ftfy` aqui) | `db_diretorio` |
| Hierarquia e contagens | `build_hierarquia` (114) | **FastAPI** | `db_diretorio` |
| Deduplicação de e-mails | `_unificar_emails` (37) | **FastAPI** | `db_diretorio` |
| Busca textual | `init_fts` (26, FTS5) | **FastAPI**, reescrita em `pg_trgm` + `unaccent` (4.5) | `db_diretorio` |
| Login Google (OAuth) | 78 | Laravel Socialite | `db_admin` |
| Hash de senha | 32 (PBKDF2) | Laravel `Hash` (argon2id) | `db_admin` |
| Envio de e-mail | `_send_email` (27, SMTP) | Laravel `Mail` + API HTTP (SMTP é bloqueado no Render free) | `db_admin` (jobs/fila) |
| Pedidos e aprovação de acesso | 96 | Laravel: controllers + policies + Filament | `db_admin` |
| Usuários, sessões, permissões | `usuarios`, `acessos` | Laravel (RBAC por escopo) | `db_admin` |
| Histórico do contato | `historico` + `log()` | **FastAPI** (é histórico do próprio dado) | `db_diretorio` |
| Notificações | `notificacoes` + `notify()` | Laravel | `db_admin` |
| Telefones úteis | `uteis` (11) | **FastAPI** | `db_diretorio` |
| Front-end | 4.786 linhas | Laravel Blade/Livewire/Alpine, reescrito (≤10% de similaridade com `public.js` do v1, zero classes `fc-*`) | `db_admin` + leitura no motor |
| Backup e exportação | `POST /api/backup` | Laravel (ação no Filament) lendo do motor | leitura nos dois |
| Relatórios pesados | não existe no v2 | **Fase 4**, FastAPI + `jobs_relatorio` | `db_diretorio` |

Regras que a tabela implica:

1. Cada dado tem **um** dono de escrita. `db_admin` não escreve em tabela do diretório;
   `db_diretorio` não escreve em tabela de identidade.
2. **Nenhum JOIN entre os dois bancos.** Toda composição é chamada HTTP + chave lógica — é o preço
   do Database-per-Service (ADR-001).
3. O Laravel **autoriza**; o motor **calcula**. O motor não decide quem pode ver: ele recebe a
   chave de escopo pedida e responde exatamente o que ela cobre.
4. Histórico do contato fica com o dado (motor); auditoria de ação administrativa fica com a
   identidade (Laravel). São dois registros com propósitos diferentes e o `X-Request-ID` costura os
   dois quando for preciso reconstruir uma operação.

---

## 4. Identidade e modelo físico do diretório

### 4.1 Chave lógica: o id do seed (confirmado em 10/11/2026)

`M1`, `F3`, `U12` **não** servem como chave lógica: são exatamente os ids sequenciais internos que o
enunciado manda manter de fora. No v2 eles nascem de `novo_id()` (`app.py:461`), que devolve
`prefixo + contador` na ordem de leitura do seed (`M1`, `F1`, `U1`, `S1`, `C1`) — ou seja, **mudam a
cada recriação do banco**.

O que já é determinístico é o id do próprio seed (`seed_mascarado.json`): `MUN0001`, `FOR0001`,
`UNI0001`, `C000001`, `UTL0001`. É esse conjunto que vira chave lógica.

`[decidido]` Confirmado pelo Enzo em 10/11/2026. `M1`/`F3`/`U12` ficam onde sempre estiveram: dentro
do banco, invisíveis para a integração.

### 4.2 Chave lógica por entidade

| Entidade | Chave lógica no seed | Prefixo | Quantidade | Registros criados na aplicação |
| :--- | :--- | :--- | ---: | :--- |
| Comarca (geografia) | `MUN0001` | `MUN` | 69 | `MUN-<ULID>` |
| Órgão (fórum) | `FOR0001` | `FOR` | 72 | `ORG-<ULID>` |
| Órgão central (Sede) | `MUN0000` | — (legado do seed) | 1 | `ORG-<ULID>` |
| Nó (unidade/setor) | `UNI0001` | `UNI` | 838 | `NO-<ULID>` |
| Contato | `C000001` | `C` | 1.807 | `CT-<ULID>` |
| Útil | `UTL0001` | `UTL` | 11 | `UT-<ULID>` |

`[decidido]` O id sequencial (`bigint`) permanece **estritamente interno** ao serviço dono. `[proposta]`
Novos registros recebem prefixo legível + ULID (26 caracteres, ordenável por tempo, gerado na
aplicação), nunca sequencial.

`[decidido]` **Exceção registrada:** a Sede é órgão, mas sua chave lógica é `MUN0000` — é o id que o
seed usa e que os 179 nós e 286 contatos referenciam. Renomear para `ORG0000` ganharia estética e
perderia rastreabilidade com a origem; fica `MUN0000`, documentado como prefixo herdado da planilha,
não como indicação de que a Sede seja comarca.

### 4.3 Regras

1. A chave lógica é a **única** identidade que cruza a fronteira; nenhuma rota do motor aceita
   `bigint` como identificador.
2. Chave lógica é **imutável e não reutilizada** — nem depois de excluída. `UNIQUE` na coluna e
   tabela `chaves_aposentadas` (chave, entidade, aposentada_em, motivo) para impedir reuso
   silencioso por gerador novo.
3. A hierarquia é expressa em chaves lógicas: `chave_pai` guarda `MUN…`/`FOR…`/`UNI…`, nunca o id
   interno. Assim a árvore sobrevive à recriação do banco sem remapeamento.
4. A chave não carrega semântica de posição: `UNI0001` não significa "primeira unidade do fórum".
   Ordem é `ordem_num`/`ordem_idx`, como no v2.
5. O motor **valida o formato** da chave na entrada (regex por prefixo) e responde `422
   chave_invalida`. Chave arbitrária nunca é aceita como termo de busca.
6. Toda referência guardada no Laravel (escopo de acesso, pedido, relatório salvo, favorito) usa
   chave lógica. É isso que impede a referência quebrada quando o banco do motor é recriado.

### 4.4 Modelo físico: o local como âncora, árvore única abaixo dele `[decidido — vocabulário corrigido em 11/11/2026]`

A árvore tem quatro níveis, e o terceiro é o **local** — o lugar onde as unidades estão instaladas: um
**fórum** (que pertence a uma comarca) ou a **Sede** (que não pertence a nenhuma). **Fórum não é
órgão**: órgão é a Sede, e, na linguagem do ofício de março/2024, também as unidades administrativas
(Diretoria do Foro, Contadorias). O fórum é prédio; chamá-lo de órgão confunde o imóvel com a
instituição e faz a vara parecer "órgão do fórum". Por isso a tabela âncora se chama **`locais`**, com
`tipo` em `FORUM`/`SEDE` — o nível é o mesmo, o que muda é o que a linha é.

Medido no seed: **69 comarcas**, **72 fóruns** (três comarcas têm dois — Vitória, Vila Velha e Viana),
**838 nós** em lista única, 612 com `pai` e profundidade máxima de dois níveis abaixo do local, e
**179 nós / 286 contatos na estrutura central**, todos pendurados na linha `MUN0000` ("Sede
Administrativa", `tipo = ORGAO`) da lista de comarcas.

O seed **já sabe** que a Sede não é comarca (marca `tipo = ORGAO`), mas guarda a informação na tabela
errada — e o v2 convive com isso por caso especial: `COALESCE(f.comarca_id, u.comarca_id)` em
`_cadeia`, em `_pai` e nas contagens, `LEFT JOIN` defensivo em cada listagem e setor virtual "Contatos
Gerais" para contato órfão. Funciona — medido no HEAD `d9a011a`, `/api/comarcas` devolve **286
contatos** para a Sede (0 fóruns, 22 unidades, 166 setores) —, mas funciona porque **toda** consulta
carrega o `COALESCE`. Os dois defeitos que essa modelagem já produziu (`AD-6861`, contagem da Sede
zerada; `AD-A45A`, `INNER JOIN` de `_cadeia` barrando escopo não-admin) foram corrigidos no v2: o que
fica não é bug aberto, é custo estrutural — quem escrever a próxima consulta precisa lembrar da
exceção. A divergência de 286 registros entre v1 e v2 vive nessa mesma região e segue sem explicação
fechada.

Decisão: **`locais` é a âncora** — uma tabela para o nível do fórum, com `tipo` em `FORUM`/`SEDE` e
`comarca_id` nulo apenas na Sede. `comarcas` sai da árvore e fica como dimensão geográfica
(69 linhas; `MUN0000` deixa de existir como comarca). **Tudo abaixo do local vira uma única tabela
`nos` autorreferente** — `unidades` e `setores` deixam de ser tabelas distintas, e o nível passa a ser
a posição (`pai_id` nulo = primeiro; preenchido = segundo).

```sql
CREATE TABLE comarcas (            -- geografia: não é nível da árvore
  id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  chave TEXT NOT NULL UNIQUE,                -- MUN0001 | MUN-<ULID>
  nome  TEXT NOT NULL UNIQUE,
  codigo_ibge TEXT NOT NULL DEFAULT '',
  ordem_num REAL, ordem_idx INTEGER NOT NULL DEFAULT 0,
  arquivado_em TIMESTAMPTZ,
  arquivado_por TEXT NOT NULL DEFAULT '',
  arquivado_motivo TEXT NOT NULL DEFAULT ''
);

CREATE TABLE locais (              -- nível do fórum: fórum (com comarca) ou Sede (sem comarca)
  id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  chave TEXT NOT NULL UNIQUE,                -- FOR0001 | MUN0000 (Sede, legado) | LOC-<ULID>
  tipo  TEXT NOT NULL CHECK (tipo IN ('FORUM','SEDE')),
  comarca_id BIGINT REFERENCES comarcas(id), -- NULL só na Sede
  nome TEXT NOT NULL,
  endereco TEXT NOT NULL DEFAULT '',
  cep      TEXT NOT NULL DEFAULT '',
  email    TEXT NOT NULL DEFAULT '',
  observacao TEXT NOT NULL DEFAULT '',
  ordem_num REAL, ordem_idx INTEGER NOT NULL DEFAULT 0,
  arquivado_em TIMESTAMPTZ,
  arquivado_por TEXT NOT NULL DEFAULT '',
  arquivado_motivo TEXT NOT NULL DEFAULT '',
  CONSTRAINT local_forum_tem_comarca CHECK ((tipo = 'FORUM') = (comarca_id IS NOT NULL))
);
CREATE UNIQUE INDEX ux_locais_nome ON locais (coalesce(comarca_id, 0), nome)
  WHERE arquivado_em IS NULL;

CREATE TABLE nos (                 -- tudo abaixo do local
  id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  chave TEXT NOT NULL UNIQUE,                -- UNI0001 | NO-<ULID>
  origem TEXT NOT NULL DEFAULT 'seed',       -- seed | app
  local_id BIGINT NOT NULL REFERENCES locais(id),
  pai_id   BIGINT REFERENCES nos(id),        -- NULL = primeiro nível sob o local
  tipo  TEXT NOT NULL DEFAULT 'UNIDADE',
  nome  TEXT NOT NULL,
  endereco TEXT NOT NULL DEFAULT '',
  cep      TEXT NOT NULL DEFAULT '',
  observacao TEXT NOT NULL DEFAULT '',
  selecionavel BOOLEAN NOT NULL DEFAULT false,
  virtual      BOOLEAN NOT NULL DEFAULT false,
  ordem_num REAL, ordem_idx INTEGER NOT NULL DEFAULT 0,
  arquivado_em TIMESTAMPTZ,
  arquivado_por TEXT NOT NULL DEFAULT '',
  arquivado_motivo TEXT NOT NULL DEFAULT ''
);
CREATE INDEX ix_nos_local ON nos (local_id) WHERE arquivado_em IS NULL;
CREATE INDEX ix_nos_pai   ON nos (pai_id)   WHERE arquivado_em IS NULL;
```

`nos.local_id` é **NOT NULL** e copiado do pai na criação: todo nó sabe a que local pertence, o que
elimina a cadeia `nó → pai → … → fórum → comarca` das consultas de escopo — e, com ela, o `COALESCE`
do v2. A consistência (`pai.local_id` = `local_id`) é validada na aplicação, porque `CHECK` não
enxerga outra linha.

Invariantes: profundidade ≤ 2 abaixo do local (importação recusa; criação devolve `422
profundidade_invalida`); `FORUM` sempre tem comarca, `SEDE` nunca tem; nó não existe sem
local.

Contrato público (quatro níveis, agora com o nome certo): `GET /api/v1/comarcas` →
`/locais?comarca=` → `/nos?local=` (primeiro nível) → `/nos?pai=` (segundo) → `/contatos?no=`. A Sede
aparece em `/locais?sede=1` — um registro, com 179 nós e 286 contatos abaixo dele; `/locais?sede=0`
lista os 72 fóruns. Contato do local (sem nó) sai em `/contatos?local=` com `no_id` nulo (5.6).

**Por que não manter `unidades` e `setores` separados:** a divisão do v2 é por profundidade, não por
natureza — no seed os tipos do primeiro nível incluem `UNIDADE`, `SECRETARIA`, `VARA`, `JUIZADO`,
`OUVIDORIA` e os do segundo incluem `SETOR`, `VARA`, `JUIZADO`. Duas tabelas obrigam duas consultas,
dois contadores e dois caminhos de importação para o mesmo conceito, e é daí que vêm os casos
especiais. Uma tabela faz a contagem ter um lugar só — a família de bug de contagem que o v2 precisou
travar por teste deixa de existir por construção.

### 4.5 Busca: `pg_trgm` + `unaccent` `[decidido, por delegação]`

Decisão: **sem `tsvector`** na Fase 3. O que se busca no diretório é nome de pessoa, nome de setor e
pedaço de telefone — texto curto, com nome próprio e erro de digitação —, não documento. `tsvector`
com configuração `portuguese` faz stemming, que em nome próprio atrapalha (junta sobrenomes que o
usuário quer distinguir) e não casa busca parcial: quem digita "moul" espera "Moulin".

```sql
CREATE EXTENSION IF NOT EXISTS unaccent;
CREATE EXTENSION IF NOT EXISTS pg_trgm;

-- `lower(public.unaccent(...))` NÃO entra em coluna gerada: `unaccent(text)` é
-- STABLE e o PostgreSQL recusa com "generation expression is not immutable"
-- (verificado em 16.15). O invólucro IMMUTABLE abaixo é o que torna 4.5
-- executável; é o mesmo que roda em db/schema.sql.
CREATE OR REPLACE FUNCTION norm_texto(txt TEXT) RETURNS TEXT
LANGUAGE sql IMMUTABLE STRICT PARALLEL SAFE
AS $$ SELECT lower(public.unaccent('public.unaccent'::regdictionary, txt)) $$;

ALTER TABLE contatos
  ADD COLUMN busca_texto TEXT GENERATED ALWAYS AS (
    norm_texto(coalesce(descricao,'') || ' ' || coalesce(valor,''))
  ) STORED,
  ADD COLUMN busca_digitos TEXT GENERATED ALWAYS AS (
    regexp_replace(coalesce(valor,''), '\D', '', 'g')
  ) STORED;

CREATE INDEX ix_contatos_busca_trgm ON contatos USING gin (busca_texto gin_trgm_ops);
CREATE INDEX ix_contatos_busca_dig  ON contatos (busca_digitos text_pattern_ops);
```

- Filtro: `busca_texto ILIKE '%' || norm_texto(:q) || '%'` (o GIN de trigrama atende o `ILIKE`
  parcial) ou `busca_digitos LIKE :digitos || '%'` quando a consulta é numérica.
- Ordenação: `similarity(busca_texto, :q)` decrescente, com `word_similarity` como desempate.
- Plano B quando o filtro volta vazio: `<%` (word similarity) com limiar 0,45, uma vez — "Moulin"
  acha "Moulym". Nada de `LIKE` em oito colunas como no v2 (`app.py:1390`).
- Volume: 1.807 contatos; o índice é construído em milissegundos, e manter dois motores de busca
  nesse tamanho não se paga.
- `tsvector` fica reservado para a Fase 4, quando texto longo (`descricao`, `observacao`) e
  ranqueamento por relevância passam a importar — e quando a normalização com `ftfy` já aconteceu na
  ingestão.

---

## 5. Ciclo de vida do dado (LGPD) e hierarquia

### 5.1 Regra de decisão

| Caso | Referência externa no Laravel? | Filhos/contatos? | Ação |
| :--- | :--- | :--- | :--- |
| Nó da hierarquia | sim (escopo, pedido, relatório salvo, job) | qualquer | **arquivar** (exclusão lógica): sai da árvore pública, a referência continua resolvendo, o Filament mostra como arquivado |
| Nó da hierarquia | não | sim | **não excluir** — `409 subarvore_ativa`; exige reparentar/esvaziar antes |
| Nó da hierarquia | não | não | **excluir fisicamente** (caso comum: nó criado por engano, nó virtual do seed) |
| Contato | não existe por desenho | — | **excluir fisicamente**, com retrato de exibição no histórico |
| Linha de histórico | — | — | **expurgo** após a retenção (padrão: 180 dias) |
| Telefone útil | mesma regra do nó | sem filhos | arquivar ou excluir, pela mesma tabela acima |

**Por que contato não é arquivado.** O contato é referenciado apenas pelo histórico interno do
próprio banco do diretório — não há nada em outro serviço a preservar. Arquivar contato criaria
lixo sem ganho legal (o direito à eliminação pede apagar) e sem ganho funcional (nada aponta para
ele). O v2 já faz exclusão física (`DELETE /api/contatos/<id>`, `app.py:1988`) e grava um retrato
textual no histórico — a Fase 3 mantém a decisão e acrescenta o prazo de retenção.

### 5.2 Como o motor sabe que existe referência externa

`[proposta]` Tabela espelho em `db_diretorio`, mantida pelo Laravel:

```sql
CREATE TABLE referencias_externas (
  chave_logica TEXT        NOT NULL,
  origem       TEXT        NOT NULL,   -- 'escopo_acesso' | 'pedido' | 'relatorio' | 'job' | 'snapshot'
  referencia   TEXT        NOT NULL,   -- id do registro no db_admin (opaco para o motor)
  criada_em    TIMESTAMPTZ NOT NULL DEFAULT now(),
  PRIMARY KEY (chave_logica, origem, referencia)
);
```

- O Laravel grava/apaga aqui na mesma transação lógica da sua própria escrita (via *outbox*, ver
  ADR-003). Se o push falhar, a operação administrativa não se confirma.
- Reconciliação diária (contagem + diff por chave, nos dois sentidos) registra
  `referencias_divergentes` e **bloqueia o expurgo** enquanto houver divergência.
- **Fail-closed:** chave desconhecida é tratada como referenciada ⇒ nunca exclusão física. Erro de
  sincronização não pode virar perda de dado.

### 5.3 O que acontece com os filhos ao arquivar um nó pai

`[decidido]` Arquivar um pai **não** toca nos filhos: a invisibilidade é **computada**, por subárvore.

```sql
WITH RECURSIVE sub AS (
  SELECT id, arquivado_em FROM nos WHERE chave_logica = $1
  UNION ALL
  SELECT n.id, n.arquivado_em FROM nos n JOIN sub s ON n.pai_id = s.id
)
SELECT bool_or(arquivado_em IS NOT NULL) FROM sub;   -- true ⇒ subárvore invisível na árvore pública
```

Consequências:

1. O nó arquivado continua existindo, com `arquivado_em`, `arquivado_motivo` e `arquivado_por`
   (a string de `X-Acting-User`).
2. Os filhos **não mudam de estado** — nenhuma linha nova recebe `arquivado_em` no arquivamento do
   pai. Restaurar o pai devolve a subárvore inteira de uma vez: arquivar é reversível por
   construção.
3. **Exclusão em cascata é proibida.** Ela destruiria contatos cujo histórico é o único registro e
   furaria a regra de referência externa (5.1), que é justamente a que precisa ser respeitada.
4. Arquivamento permanente de um pai com filhos exige operação explícita de **reparentar** antes
   (`PATCH /api/v1/nos/{chave}/pai`), auditada, com a contagem de filhos movidos na resposta.
   Arquivar com filhos é permitido, mas a resposta informa quantos nós ficaram invisíveis — o
   operador vê o efeito antes de confirmar.
5. Invariantes testáveis: (a) nenhum nó com ancestral arquivado aparece em rota pública; (b) a
   contagem de linhas com `arquivado_em` não muda ao arquivar um pai; (c) restaurar o pai restaura
   a visibilidade de toda a subárvore; (d) exclusão de nó com subárvore ativa devolve `409` e não
   altera nada.

### 5.4 Exclusão física do contato e expurgo do histórico

- `[decidido]` Contato: exclusão física, mantendo no histórico o **retrato de exibição**
  (`contato_rotulo`) e a chave lógica (`contato_chave`), mais `autor` (`X-Acting-User`) e
  `request_id`. O v2 já segue esse padrão (`log(con, "remover", cid, f"removido: {row['nome']}", uid)`).
- `[decidido]` Expurgo do histórico após **180 dias** (padrão; configurável por env), porque a
  linha de histórico é dado pessoal por associação (nome + local + data).
- `[proposta]` Onde o expurgo roda: endpoint idempotente `POST /api/v1/manutencao/expurgo` no motor
  (escopo de escrita), chamado pelo `Schedule::call` do Laravel. Não há worker permanente no free
  tier, então a rotina precisa ser disparada de fora do processo que dorme.
- `[decidido]` Depois do expurgo sobra apenas **agregado**: quantidade de contatos removidos por
  mês, sem nome, telefone, e-mail ou chave lógica. O registro do expurgo (data, quantidade, autor =
  sistema) fica em `db_admin`, para conformidade.

### 5.5 Colunas de ciclo de vida

As colunas de arquivamento nascem no DDL de `nos` (4.4) — nó não ganha ciclo de vida por `ALTER`
posterior. Contato **não** ganha coluna de exclusão lógica: a exclusão é física, e o rastro da
operação (data, quantidade, autor = sistema) vai para `auditoria_expurgo` em `db_admin`, junto do
registro de conformidade (5.4). Guardar `excluido_em` numa linha que deixa de existir seria coluna
morta.

### 5.6 Vínculo do contato: nó ou local `[decidido]`

O catálogo oficial vem em **dois volumes**: `catalogo-comarcas-2026-06-26.pdf` (41 páginas, comarcas,
25/06/2026) e `catalogo-sede-2026-08-28.pdf` (1 página, Sede, 28/08/2026). A fonte tem ainda um **terceiro
documento**, que não é catálogo e é tratado ao fim desta seção. Os dois volumes abrem cada local com um
bloco que **não pertence a nenhuma unidade ou setor** — nos fóruns *Geral · Sala ADM · Recepção ·
Segurança*; na Sede, literalmente `Geral 3334-XXXX · Telefonista XXXX•XXXX•X · Recepção XXXX`, antes
da primeira seção (Presidência). Esses contatos são do local.

Medido no seed: **626 contatos têm `forum` e não têm `uo`** — é exatamente esse bloco, por local. E
**5 contatos da Sede não têm `forum` nem `uo`**, só `comarca = MUN0000`: são o telefone geral
`(27) 3334-XXXX`, três ramais de telefonista e a recepção (todos com `obs` "Catálogo de ramais —
agosto/2026"). O v2 **já** os apresenta no local, não em nó: verificado no HEAD `d9a011a`, os 72
fóruns do payload trazem `contatos` própria do local (os 626) e a Sede traz `contatos` = 5, sem
nenhum nó `virtual` na resposta. O que o v2 não tem é o vínculo explícito no banco — ele é reconstruído
na leitura a partir das colunas `forum`/`uo`.

Decisão: o contato tem **dois níveis possíveis de vínculo**, e o do local é o obrigatório:

```sql
ALTER TABLE contatos
  ADD COLUMN local_id BIGINT NOT NULL REFERENCES locais(id),  -- sempre preenchido
  ADD COLUMN no_id    BIGINT REFERENCES nos(id);              -- NULL = contato do local
```

- `no_id IS NULL` significa "contato do local" (Geral, Recepção, Segurança, Telefonista, Portaria de
  entrada) — não é dado incompleto, é o bloco de abertura do catálogo.
- `no_id IS NOT NULL` exige `nos.local_id = contatos.local_id` (validado na importação e na escrita).
- **Não existe setor virtual.** Contato do local é seção da tela do local no Laravel, não linha de
  banco: nada de "Contatos Gerais", nada de "Atendimento geral" invisível na árvore.
- Os 5 da Sede passam a ser o que são: contatos do local Sede (`MUN0000`), com `local_id` da Sede e
  `no_id` nulo — o mesmo tratamento que os 626 recebem no fórum. Nenhuma unidade precisa ser criada.

**A estrutura da Sede confere com o catálogo.** As 21 seções do volume da Sede são exatamente as 21
unidades de primeiro nível do seed (Presidência, Assessorias, Vice-Presidência, Corregedoria, Escola da
Magistratura, Ouvidoria, Coordenadorias, Desembargadores, Tribunal do Pleno, Conselho Superior, Seção
Cível, Seção Criminal, Controle Interno, Secretaria Geral, Judiciária, Engenharia/Predial, Infraestrutura,
TI, Gestão de Pessoas, Finanças e Comissões). Dos 149 nós cujo nome é comparável, **148 aparecem no
catálogo** com ≥70% dos termos (a única diferença é `Subsecretaria Geral` × `Sub Secretaria Geral`); os
outros **30 são os rótulos anonimizados** `Gabinete de Desembargador NN`/`Comissário NN`, que no catálogo
trazem nome de pessoa e por isso não são comparáveis — nem devem aparecer em nenhum artefato do projeto.
Logo, os 179 nós e os 286 contatos não são invenção do v2: são a Sede do catálogo, um nível abaixo do
local. Observação honesta: o PDF da Sede é uma página em quatro colunas e a extração de texto intercala
colunas — a conferência acima é de **estrutura e nomes**, não de contagem de contato; os 286 continuam
sendo o número medido no seed.

**O terceiro documento da fonte: o ofício de março/2024.** Fora do catálogo, a fonte inclui
`oficio-2024-03.pdf` — *ofício de março/2024 – Diretoria do Foro*, processo administrativo interno, **março de 2024**, uma página com e-mail e telefone de **21 unidades** da
Capital. Ele **não** é catálogo de ramais e não descreve árvore: agrupa por diretoria administrativa, não
por prédio. É a fonte dos **43 contatos** do seed marcados `Contato provisório — validar vigência.` —
**23 e-mails e 20 telefones**, conferidos um a um nas 21 unidades: 10 varas criminais, Auditoria Militar,
Violência Doméstica, Diretoria do Foro, 1ª e 2ª Contadorias, Protocolo/Distribuição, Central de Mandados,
1º/2º/3º Juizado Especial Criminal (o 2º e o 3º com **dois** e-mails, gabinete e cartório) e o 3º Juizado
Especial Cível (Justiça Volante). O seed acerta os detalhes finos: a **1ª Contadoria não tem telefone** no
documento e é a única das 21 com um só contato; a Auditoria Militar e a Diretoria do Foro vêm anotadas
*(whatsapp)* e estão como `WhatsApp` no seed; o 2º JECrim traz gabinete em domínio privado, e o seed o
separa em `E-mail` com `descricao = Gabinete` e `Cartório`.

Três consequências para o modelo:

1. **`descricao` do contato é obrigatória para e-mail** — `Gabinete`, `Cartório`, `Institucional`. Uma
   coluna `email` em `nos` não representaria o 2º e o 3º JECrim, que têm dois endereços cada.
2. **A data do documento é dado, não rodapé.** Este ofício é de 03/2024; o catálogo é de 06 e 08/2026. O
   seed guarda a diferença em texto livre (`obs`), que não se filtra, não expira e não se audita: no
   DDL, `contatos` recebe `fonte` (nome do arquivo) e `vigente_desde`, e o que veio do ofício fica com
   `vigente_desde = '2024-03'` e `fonte = 'oficio-2024-03.pdf'` — a tela marca "confirmar
   vigência" a partir da coluna, não de string.
3. **Quatro grafias de telefone no mesmo documento** — móvel com hífen `(27) 9XXXX-XXXX`, móvel sem
   hífen `(27) 9XXXXXXXX`, fixo sem hífen `(27) 3XXXXXXX` e ponto no lugar do hífen `(27) 9XXXX.XXXX`.
   (Os números vão mascarados, como no seed do v2: o que importa é a forma, não o dígito.) Nada disso
   sobrevive à importação: o valor é normalizado em `(27) 9XXXX-XXXX` / `(27) 3XXX-XXXX` e `busca_digitos`
   (4.5) garante que qualquer grafia digitada ache o registro.

**Divergência aberta (uma):** o seed marca o telefone da **2ª Contadoria** como `WhatsApp`, e este
documento não traz essa anotação — ele anota *(whatsapp)* só na Auditoria Militar e na Diretoria do Foro.
Pode ser informação de outra fonte; fica registrado como ponto a confirmar, não como erro.

**O que o documento não decide:** o 3º Juizado Especial Cível (Justiça Volante) aparece no bloco da
Diretoria do Foro, mas no seed está sob o **Fórum Cível**, na 10ª Secretaria Inteligente (`UNI0040`). O
agrupamento do ofício é administrativo; a árvore do v3 segue o catálogo, e o ofício serve para contato e
para conferir existência — nunca para inferir pai.

---

## 6. Protocolo de integração e rede

### 6.1 Cabeçalhos

| Cabeçalho | Direção | Obrigatório | Formato | Onde é registrado |
| :--- | :--- | :--- | :--- | :--- |
| `X-Request-ID` | Laravel → motor; devolvido na resposta | sim, em **toda** requisição (inclusive GET) | UUIDv4 ou ULID | log estruturado dos dois serviços, `historico.request_id`, `auditoria.request_id`, header da resposta e envelope de erro |
| `Idempotency-Key` | Laravel → motor | sim em `POST`/`PUT`/`PATCH`/`DELETE`; opcional no POST de leitura em lote | string `^[A-Za-z0-9._:-]{8,64}$` | tabela `idempotencia` no motor + log do Laravel |
| `X-Acting-User` | Laravel → motor | sim em mutação; opcional em leitura | string sanitizada, `^[A-Za-z0-9][A-Za-z0-9._:-]{0,63}$`, valor `<id-opaco>:<papel>` (ex.: `usr_00042:admin`) | `historico.autor`, `auditoria.autor` |

**`X-Acting-User` — o padrão "informado pelo Laravel":**

1. É **metadado de auditoria, não fator de autorização**. Quem autoriza é o Laravel (ADR-002) e o
   escopo da chave de serviço. O motor registra o que recebeu e nunca eleva privilégio por causa
   dele.
2. "Informado pelo Laravel" é literal: o valor **não** vem do navegador. Como a chave do motor só é
   usada pelo Laravel, a garantia é de credencial; a validação de formato é defesa contra injeção
   (CRLF em header, log injection).
3. **Sem e-mail e sem nome de pessoa.** O valor é opaco (`usr_00042:admin`); o nome de exibição é
   resolvido no Laravel, onde o dado pessoal já mora. O motor não precisa dele para auditar.
4. Valor fora do charset/comprimento ⇒ `400 acting_user_invalido`, com `X-Request-ID` na resposta.

### 6.2 Escopo das chaves de API

| Escopo | Cobre | Não cobre | Quem usa |
| :--- | :--- | :--- | :--- |
| **escopo de leitura** (`diretorio:read`) | todos os GET de dados, inclusive a leitura em lote | qualquer mutação; dump bruto | Laravel (render de páginas, cache, relatórios simples) |
| **escopo de escrita** (`diretorio:write`) | leitura + mutações (contato, arquivar/restaurar nó, reparentar, expurgo) | dump bruto / backup completo | Laravel, apenas no caminho de ação administrativa |

- Transporte: `Authorization: Bearer <chave>`; comparação em tempo constante (`hmac.compare_digest`).
- Armazenamento: `sync: false` no `render.yaml` (valor só no painel); no banco fica o hash, não a
  chave. Rotação com duas chaves válidas ao mesmo tempo (janela de sobreposição) e registro de qual
  chave atendeu cada requisição.
- O motor **não** tem sessão, cookie nem CSRF: é API de serviço, não aplicação web.
- Falhas: ausente ⇒ `401 chave_ausente`; escopo insuficiente ⇒ `403 escopo_insuficiente`. Nada de
  `404` para esconder rota — o motor não é público, esconder rota só atrapalha o diagnóstico.

### 6.3 Política de retry e idempotência

| Método | Retry automático | `Idempotency-Key` | Semântica |
| :--- | :--- | :--- | :--- |
| `GET` / `HEAD` | **sim** (até 3 tentativas; backoff 0,5 s / 1 s / 2 s) | não exigido | segura e repetível |
| `POST` | **não** | obrigatório | executado uma vez por chave; repetição devolve a resposta gravada |
| `PUT` | não | obrigatório (recomendado) | idempotente por natureza; a chave protege contra repetição parcial |
| `DELETE` | não | recomendado | idempotente: `204` na primeira, `200 ja_excluido` nas seguintes |
| `POST` de leitura em lote | **sim** (allowlist explícita) | opcional | segura e repetível: não cria, não altera, não notifica, não envia e-mail |

```sql
CREATE TABLE idempotencia (
  chave           TEXT        PRIMARY KEY,
  requisicao_hash TEXT        NOT NULL,   -- sha256(método + rota + corpo normalizado)
  status          SMALLINT    NOT NULL,
  resposta        JSONB       NOT NULL,
  criada_em       TIMESTAMPTZ NOT NULL DEFAULT now(),
  expira_em       TIMESTAMPTZ NOT NULL
);
```

- Mesma chave + mesmo hash ⇒ devolve `status` e `resposta` gravados com
  `Idempotency-Replayed: true`. **Nunca reexecuta**: não insere, não notifica, não envia e-mail.
- Mesma chave + hash diferente ⇒ `409 idempotency_key_reuse`. É bug do cliente e engolir isso
  esconderia escrita perdida.
- TTL de 24 h; a linha só é gravada **depois** do commit da transação de negócio — chave nunca
  aponta para operação que falhou. A purga do TTL entra na mesma rotina do expurgo.
- Timeout do motor no meio da operação: o Laravel **não** repete o POST sozinho; o operador repete
  com a mesma chave e recebe a resposta gravada. É o ponto inteiro do desenho.
- Configuração do cliente HTTP no Laravel: `->retry(3, 500, throw: false)` **só em GET**; mutações
  com `retry(0)`; `connect_timeout: 2`, `timeout: 10`. Primeira chamada depois do cold start do
  motor (até ~45 s): tolerância longa **apenas em GET**, com 1 retry. Timeout em mutação nunca vira
  nova tentativa — vira erro para o operador.

### 6.4 Leitura em lote (mitigação de N+1)

**Motivação.** No v2 a cadeia inteira vem de uma chamada (`/api/hierarquia`), porque tudo é um
processo e um banco. Separado em dois serviços, renderizar a cadeia (comarca > local > nó > nó) mais
os contatos do nível vira N+1: uma chamada por nó, cada uma pagando HTTP (e cold start, se o motor
estiver dormindo).

| Cenário | Chamadas ao motor |
| :--- | ---: |
| v2 hoje (equivalente) | 1 (`/api/hierarquia`, payload cresce com a base) |
| Fase 3 sem lote | 4 chamadas de nível + 40 contagens = **44** |
| Fase 3 com lote | **2** (1 lote de nós + 1 de contatos do nível) |

**Forma 1 — padrão:**

```
GET /api/v1/nos?chaves=UNI0001,FOR0001,UNI0034&expandir=contagens
```

Até 50 chaves. Segura, cacheável, com retry automático liberado.

**Forma 2 — lote grande ou composto:**

```http
POST /api/v1/nos/lote
X-Request-ID: 01J8Z7...
Idempotency-Key: (opcional)
Authorization: Bearer <escopo de leitura>
```

```json
{
  "chaves": ["UNI0001", "FOR0001"],
  "expandir": ["contatos", "contagens"],
  "limite_contatos": 200
}
```

```json
{
  "gerado_em": "2026-11-10T23:20:00-03:00",
  "itens": [
    {
      "chave": "UNI0001",
      "tipo": "SETOR",
      "nome": "Protocolo • Distribuição",
      "caminho": ["MUN0001", "FOR0001", "UNI0001"],
      "contagens": { "contatos": 12, "filhos": 0 },
      "contatos": []
    }
  ],
  "ausentes": ["UNI9999"],
  "truncado": false,
  "request_id": "01J8Z7..."
}
```

Regras:

1. Chave inexistente **não** derruba o lote: entra em `ausentes` e a resposta é `200` (sucesso
   parcial). `404` em lote faz o cliente repetir tudo — é o que gera carga.
2. Teto de 200 chaves por chamada; acima disso `422 lote_excedido` (com o número recebido na
   mensagem). O cliente divide o lote; não existe paginação de lote.
3. `expandir: ["contatos"]` respeita `limite_contatos` e marca `truncado: true` quando corta —
   nunca devolve subárvore inteira sem limite.
4. Cache: `ETag` derivado de `gerado_em` + contagens; o Laravel guarda 60 s em
   `CACHE_STORE=database` e usa `If-None-Match` (o `304` não consome payload).
5. Escopo de leitura; retry automático permitido (allowlist de POST seguro) porque o endpoint não
   cria, não altera, não notifica e não envia e-mail.
6. As chaves entram por `= ANY($1::text[])` com teto validado **antes** de tocar o banco.

### 6.5 Contrato, versão, health e degradação

- **Versão:** prefixo `/api/v1` em todas as rotas do motor. Mudança incompatível ⇒ `/api/v2`; adição
  compatível não muda versão.
- **OpenAPI 3.1 é a fronteira:** o motor serve `/api/v1/openapi.json` (gerado pelo FastAPI) e o
  mesmo contrato é versionado em `contracts/diretorio-v1.yaml` no repositório do v3. O CI compara a
  especificação servida com a versionada (quebra se divergir) e roda um teste de fumaça nas rotas
  que o Laravel usa. O Laravel não consome "o que o motor devolver".
- **Envelope de erro:** `{"erro": {"codigo": "escopo_insuficiente", "mensagem": "...", "request_id": "..."}}`.
  O v2 devolve `{"erro": "texto"}`; a Fase 3 precisa de **código estável** para o Laravel traduzir
  na interface sem casar string.
- **Health:** `GET /healthz` (processo vivo, sem tocar banco — é o `healthCheckPath` do Render) e
  `GET /readyz` (banco alcançável, seed carregado, versão do contrato). O `/readyz` **não** é usado
  pelo Render: seed lento não pode virar reinício de serviço.
- **Correlação:** o `X-Request-ID` recebido é o mesmo gravado no log dos dois serviços, no
  histórico/auditoria e devolvido na resposta. Log em JSON de uma linha por requisição (`ts`,
  `servico`, `rota`, `status`, `ms`, `request_id`, `acting_user`, `escopo`), sem corpo e sem dado
  pessoal.
- **Degradação graciosa:** motor fora ou dormindo ⇒ o Laravel responde a consulta básica com o
  último snapshot em cache e **declara** o estado ("dados podem estar desatualizados; motor
  indisponível"), nunca um 500 cru. Circuit breaker: 3 falhas consecutivas de GET abrem por 60 s,
  com uma sonda (half-open) por `X-Request-ID`. Mutação **não** tem modo degradado: motor fora,
  escrita falha com mensagem clara.

---

## 7. ADRs

### ADR-001 — Banco de dados por serviço (Database-per-Service)

**Contexto.** A Fase 3 tem dois serviços com ciclos de vida diferentes: o orquestrador
(identidade, sessão, autorização, backoffice) e o motor do diretório (modelo do diretório, consultas,
ingestão). Um banco único compartilhado por dois serviços acopla migrations, impede qualquer
deploy independente e transforma o esquema em contrato implícito — sem versão, sem OpenAPI, sem
idempotência. No free tier não existe rede privada entre serviços, então a fronteira já é HTTP
público com token: insistir em banco único economizaria o contrato que **precisa** existir de
qualquer forma.

**Decisão.** Dois bancos **lógicos** isolados — `db_admin` (Laravel) e `db_diretorio` (FastAPI) — no
mesmo servidor Postgres gerenciado. Cada serviço tem credencial própria, com permissão apenas no seu
banco (o `GRANT` não cruza). Migrations são independentes. Nenhum JOIN entre os bancos; nenhuma
chave estrangeira entre eles. Integração por HTTP/JSON em `/api/v1`, com chaves lógicas imutáveis
(seção 4).

**Consequências.**

- *Positivas:* esquema do diretório evolui sem tocar identidade e vice-versa; o contrato passa a ser
  explícito e versionado (OpenAPI), o que permite teste de contrato; privilégio mínimo real (cada
  credencial vê um banco); a fronteira de serviço sobrevive a uma futura separação de servidor sem
  reescrita; o motor pode ser reconstruído a partir do seed sem afetar contas e escopos.
- *Negativas aceitas:* sem transação distribuída — operações que atravessam os dois serviços
  precisam de saga/outbox (ADR-003); composição de dados que hoje é um JOIN vira chamada HTTP, com
  latência e possibilidade de falha parcial; dois conjuntos de migrations e dois backups.
- *Reabre se:* (a) aparecer necessidade real de consistência transacional entre identidade e
  diretório; (b) a latência de composição por HTTP inviabilizar um caso de uso medido; (c) o free
  tier mudar de forma que bancos separados passem a custar o dobro.

### ADR-002 — Autorização centralizada por escopo no orquestrador (Opção B)

**Contexto.** Com dois serviços, autorização pode morar em dois lugares: (A) no motor, que passaria a
conhecer usuários, papéis e escopos — e portanto a duplicar a fonte de verdade da identidade; ou
(B) só no orquestrador, com o motor recebendo a chave de escopo já resolvida. O v2 hoje faz uma
versão de A dentro do monólito (`usuarios`, `acessos`, `_cobre`), o que só funciona porque tudo é um
processo e um banco.

**Decisão.** **Opção B.** O Laravel é o único ponto de autorização: resolve identidade (sessão/OAuth
+ allowlist de admin), calcula o escopo efetivo do usuário (tabela `acessos` em `db_admin`) e envia
ao motor a **chave de escopo de leitura** já resolvida, junto da chave de serviço e do
`X-Acting-User`. O motor **não** decide quem pode ver: ele responde exatamente o que a chave de
escopo cobre, e nunca expande além dela. Escrita exige chave de escopo de escrita, que só o Laravel
possui e só usa no caminho de ação administrativa.

**Consequências.**

- *Positivas:* uma única fonte de verdade de identidade e permissão (o RBAC do Laravel/Filament é o
  produto, não uma cópia); o motor fica sem sessão, sem cookie e sem CSRF — superfície menor; testar
  autorização é testar uma coisa só; trocar de provedor de identidade não toca o motor.
- *Negativas aceitas:* o motor **confia** no escopo recebido — quem tiver a chave de serviço obtém o
  que pedir. Aceito porque dono, credencial e rede são os mesmos, e o motor não é público. A
  consequência é que a chave de escrita vale mais que qualquer senha: rotação e `sync: false` não
  são opcionais.
- *Invariante testável:* nenhuma resposta do motor contém chave fora do escopo pedido (teste com
  escopo de comarca verificando que nenhum nó de outra comarca aparece).
- *Reabre se:* (a) surgir consumidor do motor que não seja o Laravel (app móvel, integração
  externa) — aí o escopo precisa de token próprio; (b) o motor virar público; (c) auditoria exigir
  autorização no ponto de dados.

### ADR-003 — Mitigação de sagas e referências quebradas por chaves lógicas e exclusão lógica condicional

**Contexto.** Sem transação distribuída, duas classes de falha aparecem: **referência quebrada** (o
Laravel guarda um vínculo com um registro do diretório que deixou de existir — ou pior, que foi
recriado com outro conteúdo, porque o banco é reconstruído do seed e o sequencial voltou ao início) e
**operação meio aplicada** (o lado administrativo confirmou e o motor não, ou o inverso). O gatilho
concreto do projeto: o banco é recriado do seed em qualquer recarga de base, restauração de backup ou
ambiente novo, então o identificador sequencial **não** é estável entre recriações — e o banco free do
Render, que expirava em 30 dias, foi descartado exatamente por transformar isso em rotina (Apêndice
B-1).

**Decisão.** Três mecanismos combinados:

1. **Chaves lógicas imutáveis** (seção 4): toda referência entre serviços usa chave determinística
   (`MUN…`, `FOR…`, `UNI…`, `C…`, `UTL…` do seed; `ULID` com prefixo para o que nasce na
   aplicação). O sequencial nunca cruza a fronteira, e chave aposentada não é reutilizada — é isso
   que impede o vínculo apontar para o registro errado depois de uma recriação.
2. **Exclusão lógica condicional** (seção 5): o lado que tem referência externa **não** é apagado
   fisicamente — vira `arquivado`, e a referência continua resolvendo. Contato, que só tem
   referência interna, é excluído fisicamente com retrato no histórico; histórico tem expurgo por
   retenção.
3. `[proposta]` **Outbox transacional no orquestrador**: a mudança em `db_admin` e a mensagem para
   o motor (push de `referencias_externas`, arquivamento, reparent) são gravadas na **mesma**
   transação em `outbox_integracao` (`pendente`/`aplicada`/`falhou`, com `Idempotency-Key` e
   `request_id`). Um comando agendado drena a outbox, com retry e chave idempotente — então
   reexecutar não duplica. Não há broker nem Redis no free tier, e isso não é preciso: a outbox dá
   entrega ao-menos-uma-vez, e a idempotência do motor transforma isso em efeito único.

Nenhum estado fica "meio aplicado" silenciosamente: a operação administrativa é
`pendente → aplicada | falhou`, com o `request_id` ligando as duas pontas, e a reconciliação diária
(ADR-003, item 3) aponta órfãos nos dois sentidos.

**Consequências.**

- *Positivas:* recriar banco/seed não quebra vínculo; exclusão no diretório não destrói referência
  administrativa; falha de rede entre serviços não perde operação nem duplica registro; o relatório
  de divergência dá visibilidade a deriva que hoje seria invisível.
- *Negativas aceitas:* um pouco mais de código do que "chamar e torcer" (outbox, reconciliação,
  idempotência); nós arquivados continuam ocupando linha (irrelevante: são 838 nós e crescem
  devagar); a operação de arquivamento não é instantânea em toda a base — é por subárvore, como
  manda a seção 5.3.
- *Reabre se:* (a) aparecer caso de uso que exija exclusão física imediata de nó referenciado
  (obrigação legal sobre dado específico) — aí a regra ganha uma exceção explícita e auditada;
  (b) a outbox acumular sem drenar em produção; (c) surgir broker no free tier que simplifique
  comprovadamente o desenho.

### ADR-004 — Idempotência obrigatória em mutações e retry automático restrito a leitura *(derivada)*

**Contexto.** O cliente HTTP do Laravel tem política de retry; do outro lado, um `POST` repetido é um
registro duplicado (contato duplicado é o pior caso visível no diretório). Repetir automaticamente
uma operação mutável é a forma mais fácil de criar dado duplicado sem ninguém perceber.

**Decisão.** Retry automático **somente** em `GET`/`HEAD`. Toda mutação exige `Idempotency-Key`
acompanhada de `X-Request-ID`; o motor grava `chave → (hash da requisição, status, resposta)` e
devolve a resposta gravada na repetição; chave reutilizada com corpo diferente devolve `409`. O
`POST` de leitura em lote entra numa allowlist explícita de "POST seguro" (sem efeito colateral) e
pode ter retry.

**Consequências.** *Positivas:* retry deixa de ser risco; a repetição manual do operador é segura e
rastreável; `request_id` fecha o rastro dos dois lados. *Negativas aceitas:* tabela de idempotência
com purga por TTL (24 h) e exigência de disciplina no cliente (chave por operação, nunca
reutilizada). *Reabre se:* a latência de gravar a chave competir com o ganho (não deve: é uma linha
por mutação).

### ADR-005 — Leitura em lote e contrato OpenAPI versionado como fronteira *(derivada)*

**Contexto.** Separado em dois serviços, o render da cadeia de setores vira N+1 (44 chamadas no
exemplo da seção 6.4), e cada chamada paga HTTP — e cold start, se o motor estiver dormindo. Ao
mesmo tempo, "o Laravel consome o que o motor devolver" é contrato invisível: quebra sem aviso.

**Decisão.** Endpoint de leitura em lote nas duas formas (GET com chaves até 50; `POST /api/v1/nos/lote`
até 200, com `ausentes` em vez de `404` parcial e `truncado` quando corta), mais OpenAPI 3.1 servido
pelo motor e versionado em `contracts/diretorio-v1.yaml`, com teste de contrato no CI.

**Consequências.** *Positivas:* 44 chamadas viram 2; falha parcial não derruba o render; mudança de
contrato falha no CI em vez de falhar em produção. *Negativas aceitas:* um endpoint com semântica de
leitura em método POST (documentado e com allowlist de retry), e o custo de manter o contrato
versionado a cada mudança compatível. *Reabre se:* o lote virar caminho de dado sensível (aí volta
para GET com escopo explícito) ou se o Laravel passar a gerar cliente tipado a partir do contrato
(melhora, não reabre).

### ADR-006 — Órgão como âncora da hierarquia, árvore única abaixo dele

**Contexto.** O seed é uma lista única de 838 nós com campo `pai` (612 preenchidos) e profundidade
máxima de dois níveis abaixo do local. Duas distorções vêm da origem em planilha:

1. A Sede — que é **órgão, no mesmo nível do fórum** — está gravada como **linha de comarca**
   (`MUN0000`, "Sede Administrativa", marcada `tipo = ORGAO`), e é dela que pendem os 179 nós e os
   286 contatos da estrutura central. A planilha não tinha onde pôr a Sede, então pôs na aba de
   comarcas.
2. O v2 dividiu os nós em `unidades` e `setores` **por profundidade**, o que produziu uma situação que
   não existe no domínio: unidade sem fórum (exatamente a estrutura central, 179 nós). Contato sem nó
   ele já resolve bem — fica no local (5.6) —, mas por reconstrução na leitura, não por vínculo no
   banco.

O custo disso é medido: toda consulta de contagem paga `COALESCE(f.comarca_id, u.comarca_id)` e toda
listagem carrega `LEFT JOIN` defensivo. Essa modelagem já produziu dois defeitos, ambos corrigidos no
v2 — `/api/comarcas` informando **0 contatos** para a Sede (`AD-6861`) e o `INNER JOIN` de `_cadeia`
barrando escopo não-admin (`AD-A45A`); hoje as duas rotas funcionam com o `COALESCE` explícito. O que
sobra é estrutural: a exceção precisa ser repetida em cada consulta nova. A divergência de 286
registros entre v1 e v2 vive na mesma região e continua aberta.

**Decisão.** O nível que ancora a árvore é o **local** — fórum ou Sede —, e não o órgão: fórum é
prédio, não órgão. Uma tabela `locais` (`tipo` em
`FORUM`/`SEDE`, `comarca_id` nulo apenas na Sede) substitui `foruns`; `comarcas` deixa de ser
nível da árvore e vira dimensão geográfica de 69 linhas; a linha falsa `MUN0000` desaparece como
comarca e reaparece como o órgão Sede, mantendo a chave `MUN0000` para preservar rastreabilidade.
Tudo abaixo do local fica em **uma tabela `nos` autorreferente**, com `local_id NOT NULL` (copiado do
pai) e profundidade máxima de 2 validada na importação e na criação. O contrato público mantém quatro
níveis, derivados da posição: `/comarcas` → `/locais?comarca=` → `/nos?local=` → `/nos?pai=`.

**Consequências.**

- *Positivas:* a Sede deixa de ser caso especial — tem linha própria em `locais`, com os 286 contatos
  contados pela mesma consulta que conta os fóruns, o que elimina a causa que exigiu o `COALESCE` e
  que já produziu `AD-6861` e `AD-A45A`; uma consulta por nível e um lugar só para contar contatos;
  `nos.local_id` dispensa a cadeia de joins que existia só para descobrir a comarca de um nó.
- *Negativas aceitas:* `foruns` desaparece como nome, então o contrato da Fase 3 troca
  `/api/v1/foruns` por `/api/v1/locais?tipo=FORUM` (a rota antiga fica como alias na migração do
  v2); a importação precisa tratar a marcação `tipo = ORGAO` do seed em vez de copiar a linha como
  comarca; o breadcrumb de um local sem comarca não tem comarca para exibir (mostra o órgão direto);
  perde-se a tradução direta "tabela = nível", e quem conhece o v2 precisa reaprender os nomes.
- *Reabre se:* o domínio ganhar um terceiro nível real (subsetor) — a árvore suporta, muda o contrato;
  ou se aparecer outro local sem comarca (ex.: núcleo regional), o que já cabe em `locais`
  com um valor novo em `tipo`.

### ADR-007 — Busca por trigrama, com `tsvector` adiado para a Fase 4

**Contexto.** O `FTS5` do SQLite não existe no Postgres e o v2 resolve busca com `LIKE` em oito
colunas (`app.py:1390`). O domínio não é documental: busca-se nome de pessoa, nome de setor e pedaço
de telefone, em uma base de 1.807 contatos.

**Decisão.** `pg_trgm` + `unaccent` como motor único: coluna gerada normalizada (`busca_texto`), GIN
de trigrama, `ILIKE` parcial como filtro, `similarity`/`word_similarity` como ordenação, coluna de
dígitos para telefone. `tsvector` **não** entra na Fase 3; fica reservado para a Fase 4, quando texto
longo e relevância passarem a importar.

**Consequências.** *Positivas:* casa busca parcial ("moul" → "Moulin") e tolera acento e erro de
digitação, que é o caso real; um índice, uma regra, sem dicionário de stemming para manter.
*Negativas aceitas:* trigrama é lexical — não entende sinônimo nem semântica; o índice cresce com o
texto indexado e o ranking não é "inteligente". *Reabre se:* a base crescer para dezenas de milhares
com texto longo (aí `tsvector` ganha), ou se a Fase 4 exigir relevância semântica (aí entra o motor de
qualidade, não o `pg_trgm`).

---

## 8. O que está decidido, o que é proposta, o que está aberto

**Decidido** (registrado, pedido explicitamente ou delegado em 10/11/2026): separação em dois serviços
com dois bancos lógicos; chave lógica = id do seed (`MUN…`/`FOR…`/`UNI…`/`C…`/`UTL…`) cruzando a
fronteira e sequencial interno; modelo de nós com o local como âncora (fórum ou Sede) e árvore única
abaixo dele (ADR-006); contato ancorado no local (`local_id` obrigatório) com `no_id` nulo para o
bloco Geral/Recepção/Segurança do local, sem setor virtual (5.6); busca por
`pg_trgm` + `unaccent` (ADR-007); banco Neon free (B-1); soft delete só para nó com referência externa
e exclusão física/expurgo para o que só tem referência interna; arquivamento de pai sem cascata de
estado; retry automático só em leitura; `Idempotency-Key` + `X-Request-ID` em mutação; `X-Acting-User`
como string sanitizada informada pelo Laravel; escopos de leitura e escrita; `/api/v1`; vocabulário da
seção 1; Fase 4 com `jobs_relatorio` em `db_diretorio` + `202 Accepted` + consulta, e `ftfy` só na
ingestão.

**Proposta** (mecanismo meu, ainda sem aval): tabela espelho `referencias_externas` e a regra
fail-closed; outbox transacional para as chamadas ao motor; formato `NO-<ULID>`/`CT-<ULID>`; expurgo
de 180 dias disparado por `Schedule::call` no endpoint idempotente do motor; allowlist do POST de
leitura em lote; envelope de erro com código; circuit breaker de 60 s; log JSON de uma linha.

**Aberto** (decisão de produto, não de engenharia):

| # | Ponto | Por que decide antes |
| :--- | :--- | :--- |
| A-5 | **Login:** manter Google OAuth no Laravel (Socialite) e matar o login local em produção, ou abrir e-mail/senha para demonstração | Afeta consent screen, allowlist, o texto do README e o que o recrutador consegue fazer no demo |

A-1 a A-4 estão fechados nesta revisão: Neon free (B-1), árvore única de nós (ADR-006), `pg_trgm` +
`unaccent` (ADR-007) e `jobs_relatorio` em `db_diretorio` (2.4).

---

## 9. Critérios de aceite da Fase 3 (verificáveis)

1. **Contrato:** `contracts/diretorio-v1.yaml` idêntico ao `/api/v1/openapi.json` servido (teste de
   contrato verde no CI) e teste de fumaça nas rotas usadas pelo Laravel.
2. **N+1:** render da cadeia completa consome **2** chamadas ao motor (medido por contador no log do
   Laravel), contra 44 do desenho ingênuo.
3. **Idempotência:** repetir `POST /api/v1/contatos` com a mesma `Idempotency-Key` devolve a mesma
   resposta e **não** cria segundo registro (contagem de contatos inalterada); com corpo diferente,
   devolve `409`.
4. **Retry:** nenhuma mutação é repetida automaticamente — teste com motor que devolve erro na
   primeira chamada e contagem de registros igual a 1.
5. **Arquivamento:** arquivar um nó pai com filhos deixa a contagem de linhas com `arquivado_em`
   inalterada; a subárvore some da rota pública; restaurar devolve tudo.
6. **Exclusão:** contato excluído não existe mais na tabela (exclusão física) e o histórico guarda o
   retrato; nó sem referência e sem filhos é excluído; nó com subárvore ativa devolve `409` sem
   alterar nada.
7. **Escopo:** resposta do motor nunca contém chave fora do escopo pedido (teste com escopo de uma
   comarca); chave de leitura recebe `403` ao tentar mutação.
8. **Cabeçalhos:** requisição sem `X-Request-ID` é recusada; `X-Acting-User` fora do charset devolve
   `400`; o `X-Request-ID` aparece no log do motor e no `historico` da operação.
9. **Health/degradação:** `/healthz` responde 200 com o banco fora; com o motor parado, o Laravel
   responde a consulta básica e declara indisponibilidade em vez de 500.
10. **Recriação de banco:** recriar `db_diretorio` do seed não quebra nenhum vínculo guardado em
    `db_admin` (escopos, pedidos e relatórios salvos continuam resolvendo por chave lógica).
11. **Modelo:** nenhum nó com profundidade acima de 2 abaixo do local entra (importação recusa,
    criação devolve `422 profundidade_invalida`); a Sede responde em `/api/v1/locais?sede=1` como
    **local** (não comarca) e as contagens fecham contra o seed: 69 comarcas, 72 fóruns, 1 local (a Sede)
    central com **179 nós e 286 contatos**. Nenhuma consulta de contagem usa `COALESCE` nem
    `LEFT JOIN` defensivo para achar comarca de nó.
12. **Busca:** "vitoria" acha "Vitória", "moul" acha "Moulin", telefone digitado sem máscara acha o
    contato; consulta que não casa nada volta vazia em menos de 100 ms na base de 1.807.
13. **Contato sem nó:** contato com `no_id` nulo aparece no bloco do local (Geral/Recepção/Segurança),
    não em setor virtual; a Sede mostra 286 contatos, 5 deles direto no local; o motor não cria
    nenhum nó `virtual`.

---

## Apêndice A — superfície do v2 (29 rotas) e destino na Fase 3

| v2 (método e rota) | Destino na Fase 3 | Observação |
| :--- | :--- | :--- |
| `GET /healthz` | Laravel `/up` + motor `/healthz` | health do Render por serviço |
| `GET /admin` | Laravel (Filament + Livewire) | backoffice deixa de ser HTML próprio |
| `GET /style.css` | pipeline de assets do Laravel | — |
| `GET /oauth/login` | Laravel (Socialite) | — |
| `GET /oauth/callback` | Laravel (Socialite) | `redirect_uri` a partir do `X-Forwarded-Proto` |
| `GET /api/hierarquia` | motor `GET /api/v1/hierarquia` | cacheado; usado no primeiro render |
| `GET /api/comarcas` | motor `GET /api/v1/comarcas` | 69 comarcas; contagem por local, não por comarca (o v2 conta a Sede com `COALESCE`) |
| `GET /api/foruns?comarca_id=` | motor `GET /api/v1/locais?comarca=` | `foruns` vira `locais` com `tipo = FORUM` (ADR-006) |
| `GET /api/unidades?forum_id=` | motor `GET /api/v1/nos?local=` | primeiro nível abaixo do local |
| `GET /api/setores?unidade_id=` | motor `GET /api/v1/nos?pai=` | — |
| `GET /api/contatos?q=&setor_id=…` | motor `GET /api/v1/contatos?…` | filtros por chave lógica, não por id |
| `GET /api/contatos/<id>` | motor `GET /api/v1/contatos/{chave}` | id vira chave lógica |
| `GET /api/busca?q=` | motor `GET /api/v1/busca` | `pg_trgm` + `unaccent` (4.5) |
| `GET /api/resumo` | motor calcula, Laravel autoriza | relatório administrativo |
| `GET /api/usuarios` | Laravel | `db_admin` |
| `GET /api/meus-pedidos` | Laravel | `db_admin` |
| `GET /api/pedidos` | Laravel | `db_admin` |
| `GET /api/notificacoes` | Laravel | `db_admin` |
| `GET /api/historico` | motor `GET /api/v1/historico` | histórico do dado; auditoria administrativa fica no Laravel |
| `GET /api/me` | Laravel | — |
| `POST /api/login` | Laravel | login local morre em produção (A-5) |
| `POST /api/logout` | Laravel | — |
| `POST /api/pedidos` | Laravel | público |
| `POST /api/pedidos/<id>/aprovar` | Laravel | cria usuário; policy + Filament |
| `POST /api/backup` | Laravel (ação no Filament) | dump bruto não sai do motor |
| `POST /api/notificacoes/lidas` | Laravel | — |
| `POST /api/me/senha` | Laravel | — |
| `POST /api/contatos` | motor `POST /api/v1/contatos` (via Laravel) | com `Idempotency-Key` obrigatória |
| `PUT /api/contatos/<id>` | motor `PUT /api/v1/contatos/{chave}` | — |
| `DELETE /api/contatos/<id>` | motor `DELETE /api/v1/contatos/{chave}` | exclusão física + retrato no histórico |

---

## Apêndice B — divergências com o registro vigente

**B-1 — Banco da Fase 3 (resolvido em 10/11/2026).** O enunciado partia do Postgres free do Render,
que expira em 30 dias; a decisão registrada em 10/11/2026 (`PROJETOS-FUTUROS.md`,
`docs/arquitetura-v3.md`) era **Neon free**. Enzo confirmou **Neon free**. O argumento que fechou:
com Render Postgres, `db_admin` também expiraria — contas, escopos, pedidos e a configuração
administrativa morreriam junto com um banco que é reconstruível do seed. Consequências registradas:

1. A premissa "o banco morre em 30 dias" sai do documento. Quem recriar `db_diretorio` o faz por
   operação (recarga de base, restauração, ambiente novo), não por prazo.
2. As chaves lógicas continuam necessárias — e agora por motivo honesto: a recarga do seed é
   operação possível a qualquer momento, e o sequencial não sobrevive a ela.
3. O runbook de recarga está escrito no **Apêndice C** (projeto no Neon, `GRANT` das duas
   credenciais, `migrate --force` nos dois bancos, carga do seed pelo motor, com a regra da Sede e as
   contagens de conferência). A parte de `GRANT` só pode ser executada quando o projeto existir; o
   procedimento não pode ficar implícito no `entrypoint.sh`.

**B-2 — Números de medição.** `docs/arquitetura-v3.md` registra `app.py` com 2.003 linhas,
`class Handler` com 992, `SCHEMA` com 122 e o carregador de seed com 230. Medido agora no HEAD
`d9a011a`: **2.055**, **1.027**, **78** e **139**. A diferença é de recorte e de commits
posteriores no mesmo dia; os números deste documento são os medidos hoje e devem ser reconferidos
antes de qualquer texto público que os cite.

**B-3 — Precedência sobre `arquitetura-v3.md`.** Este documento altera dois pontos daquele:
(i) o motor passa a ter banco lógico próprio (`db_diretorio`), e não o mesmo banco do Laravel;
(ii) o contrato ganha prefixo `/api/v1` com OpenAPI versionado. O resto daquele documento
(por que Laravel como casca, por que o FastAPI fica fora do caminho crítico do primeiro deploy,
custos aceitos, caminho de validação) continua valendo.

---

## Apêndice C — runbook de recarga do `db_diretorio`

Vale para Neon free (B-1) e para qualquer ambiente novo. Recarga é operação **idempotente por
chave**: nenhum vínculo do `db_admin` depende do sequencial, então recarregar não quebra pedido,
escopo nem relatório salvo.

1. **Projeto e bancos.** Um projeto no Neon, dois databases — `db_admin` e `db_diretorio` — com
   credenciais distintas (`DATABASE_URL_ADMIN`, `DATABASE_URL_DIRETORIO`, `sslmode=require`). Duas
   credenciais não é preciosismo: é o que impede o motor de escrever no banco do orquestrador por
   engano de configuração.
2. **Migrations na ordem.** Laravel primeiro (`php artisan migrate --force`), motor depois
   (`python -m motor.migrate`). O motor não depende do admin; o inverso só vale para login.
3. **Carga do seed** pelo motor, a partir de `seed_mascarado.json`:

   - A linha de `comarcas` com `tipo = "ORGAO"` (`MUN0000`) **não** entra em `comarcas`: vira
     `locais` com `tipo = 'SEDE'`, `comarca_id = NULL` e `chave = 'MUN0000'`. Os **179 nós** e os
     **286 contatos** pendurados nela vão junto. Se essa regra for esquecida, a Sede volta a ser
     comarca e o `COALESCE` do v2 volta a ser necessário.
   - Contato com `forum` e sem `uo` (626 no seed) → `local_id` preenchido, `no_id` nulo: é o bloco
     Geral/Recepção/Segurança do local (5.6).
   - Contato sem `forum` e sem `uo` (5 no seed, todos da Sede) → `local_id` da Sede, `no_id` nulo.
   - Contato com `uo` (1.134 no seed, 42 deles também com `forum`) → `no_id` do nó, e `local_id`
     copiado de `nos.local_id`.
   - Vitória, Vila Velha e Viana têm dois fóruns cada: dois fóruns na mesma comarca, sem
     consolidação.
4. **Conferência obrigatória** (números do seed):

   | Consulta | Esperado |
   | :--- | ---: |
   | `GET /api/v1/comarcas` | 69 |
   | `GET /api/v1/locais?tipo=FORUM` | 72 |
   | `GET /api/v1/locais?sede=1` | 1 local (a Sede), 179 nós, 286 contatos |
   | `GET /api/v1/nos` | 838 |
   | `GET /api/v1/contatos` | 1.807 |
   | Contatos com `no_id` nulo | 631 (626 + 5) |

5. **Neon free hiberna o compute** por inatividade: a primeira consulta acorda o projeto e pode
   levar alguns segundos. O health/readiness do motor tem de tratar falha de conexão como
   **degradação graciosa** (responder o que está em cache e declarar indisponibilidade), nunca como
   "banco vazio" — base vazia é estado legítimo depois de uma recarga, e confundir os dois faz o
   Laravel apagar/recriar dado que existe.
6. **Recarga em produção** é ação de admin registrada em `auditoria_expurgo`/`historico`, com autor e
   contagem antes/depois. Não é comando de terminal no dia do deploy.

---

## Apêndice D — o DDL executável (`db/`)

O modelo das seções 4.4, 4.5, 5.2, 5.3, 5.6 e 6.3 deixou de ser fragmento de papel: virou um arquivo
que roda. Nada aqui foi escrito de cabeça — cada número abaixo saiu de execução em PostgreSQL 16.15.

### D.1 Arquivos

| Arquivo | O que é |
| :--- | :--- |
| `db/schema.sql` | DDL completo: extensões, `norm_texto`/`so_digitos`, 9 tabelas, índices, `CHECK`s, triggers. Idempotente (`CREATE … IF NOT EXISTS`, `DROP TRIGGER IF EXISTS`). |
| `db/verificar-schema.sql` | 41 invariantes em `DO`/`plpgsql`: cada `CHECK` que deve recusar, cada um que deve aceitar, as colunas geradas, a busca e a ausência de cascata. |
| `db/carregar-seed.py` | Emite o SQL de carga do `seed_mascarado.json` (só stdlib). Não é o ingestão oficial do motor: é a prova de que o schema aguenta os dados medidos. |
| `db/verificar-carga.sql` | 20 invariantes sobre a base carregada: volumes, vínculos, profundidade, procedência e busca. |
| `db/verificar-plano.sql` | 5 invariantes de **plano**: o índice GIN serve a `busca_texto ILIKE norm_texto(…)`; a forma ingênua `lower(unaccent(valor))` não usa índice nenhum; `busca_digitos LIKE '273134%'` usa o `text_pattern_ops`. |
| `db/medir-limiar.sql` | Medição, não invariante: `similarity`/`word_similarity` contra o vocabulário real, para o limiar de sugestão sair de número medido. |
| `db/expurgar-historico.sql` | Retenção: agrega em `historico_mensal` e apaga as linhas com mais de 180 dias, na mesma transação; idempotente. |
| `db/testar-schema.sh` | Orquestra: cria `<base>_teste`, aplica o DDL **duas vezes**, roda as invariantes, e opcionalmente carrega o seed (`SEED_JSON=…`). Apaga o banco no fim. |

### D.2 Como rodar

```bash
# só o schema (invariantes estruturais)
db/testar-schema.sh

# schema + carga dos 1.807 contatos + invariantes de dados
SEED_JSON=../diretorio-contatos-v2/seed_mascarado.json db/testar-schema.sh

# contra o Neon (o script só age em banco terminado em _teste)
DATABASE_URL='postgresql://…' db/testar-schema.sh
```

Local, sem servidor de sistema: `initdb -D … --auth=trust --no-locale -E UTF8` e
`pg_ctl -o '-p 55432 -k /tmp'`. O `psql` do Debian precisa de `PGHOST` apontando para o socket.

### D.3 O que o banco garante sozinho (e o que não dá)

| Garantido por `CHECK`/`FK`/índice | Fica na aplicação (o `CHECK` não enxerga outra linha) |
| :--- | :--- |
| chave lógica única e no formato (`MUN/FOR/UNI/C/UTL` + sufixo ULID) | `nos.local_id = pai.local_id` |
| `FORUM` ⇔ `comarca_id NOT NULL`; `SEDE` ⇔ nulo | `contatos.local_id = no.local_id` |
| nome de comarca único; nome único entre irmãos (fórum na comarca, nó no pai) | profundidade ≤ 2 abaixo do local |
| contato com valor, e-mail com `@`, número com 8–13 dígitos | deduplicação de contato |
| sem cascata: `DELETE` de local/nó/comarca com filhos é recusado | vigência ("reconfirmar este contato") |
| `atualizado_em` por trigger; histórico com autor opaco | expurgo de 180 dias (job) |

Os quatro índices únicos parciais (`WHERE arquivado_em IS NULL`) existem para que o arquivamento não
bloqueie a criação de um item com o mesmo nome depois — o v2 não tinha como resolver isso.

### D.4 Os três achados que o papel não tinha

1. **`lower(unaccent(…))` não compila em coluna gerada.** `public.unaccent(text)` é `STABLE`, e o
   PostgreSQL exige `IMMUTABLE` em `GENERATED ALWAYS AS … STORED` — a 4.5, como estava escrita, daria
   `ERROR: generation expression is not immutable`. Solução: `norm_texto(text)` como invólucro
   `IMMUTABLE STRICT PARALLEL SAFE`, usada na coluna gerada, no índice GIN **e** na consulta
   (`busca_texto ILIKE '%' || norm_texto(:q) || '%'`). A 4.5 já está corrigida.
2. **O `CHECK` de telefone tem de aceitar valor mascarado.** A base publicada é mascarada
   (`(27) 3134-XXXX`, e `XXXX` puro no ramal): com a regra só de 8–13 dígitos, o repositório público
   não carregaria no próprio schema. O `CHECK` aceita as duas formas e continua recusando
   `(27) 9abc-0000`.
3. **`UNIQUE (local, nó, tipo, valor)` não entra.** Nos 297 contatos não mascarados do seed não há uma
   única repetição, mas o mascaramento colapsa 1.510 valores (`XXXX` 444 vezes, `(27) 3246-XXXX` 117),
   então o índice único recusaria dado legítimo da base pública. A deduplicação fica na aplicação.

4. **O índice de busca só serve se a consulta escrever a mesma expressão.** Criar o GIN não basta:
   `busca_texto ILIKE '%' || norm_texto(:q) || '%'` usa o índice; `lower(unaccent(valor)) ILIKE …` não
   usa — e as duas devolvem o mesmo resultado hoje, com 1.807 linhas, o que esconde o defeito até a base
   crescer. `db/verificar-plano.sql` lê o `EXPLAIN` e trava as duas coisas: o índice **é** usado pela
   forma correta (com `enable_seqscan` desligado) e **não** é usado pela ingênua. Medido com as
   configurações de fábrica: `cartorio` (68 linhas) sai por Bitmap Index Scan; `vara` (65) e `de` (291)
   saem por Seq Scan — nesta escala o planejador escolhe bem dos dois jeitos.
5. **O limiar de similaridade depende do vocabulário, não é constante.** Medido com
   `db/medir-limiar.sql`: `similarity('vitora','vitoria')` = 0,500, mas o vizinho mais próximo entre os
   nomes **inteiros** é `diretora` — nome longo dilui o trigrama e a sugestão erra. Reduzindo o
   vocabulário a palavras (4+ letras, 467 tokens no seed), as cinco consultas com erro de digitação
   (`vitora`, `cartoro`, `ciminal`, `unificad`, `jecrim`) caem no token certo com o limiar **0,45** do
   padrão, 1 candidato cada. Regra para o motor: busca principal por `ILIKE` (sem limiar); sugestão de
   erro comparando **palavra** com `similarity ≥ 0,45` e `ORDER BY similarity DESC LIMIT 5`.

Além disso, dois ajustes de forma: `nos.origem` (`seed` | `app`) separa o que veio do catálogo do que
foi criado na tela, e a Sede entra como `locais` de `chave = 'MUN0000'` — a chave lógica do seed é
imutável, então não se renomeia para `ORG…`; o `tipo = 'SEDE'` é o que a distingue.

### D.5 Resultado medido

| Verificação | Resultado |
| :--- | ---: |
| Invariantes estruturais | 41 PASS |
| DDL aplicado duas vezes seguidas | sem erro |
| Invariantes de carga | 20 PASS |
| Invariantes de plano | 5 PASS |
| comarcas / locais / fóruns | 69 / 73 / 72 |
| nós (1º nível / total) | 226 / 838 |
| contatos (no local / em nó) | 631 / 1.176 |
| contatos da Sede | 286 |
| contatos com local divergente do local do nó | 0 |
| nós com profundidade > 2 | 0 |
| procedência: ofício de março/2024 · volume da Sede · base sem documento | 43 · 286 · 1.478 |

A procedência não é chute: sai da observação de cada contato no seed. `Catálogo de ramais —
agosto/2026` → `catalogo-sede-2026-08-28.pdf`, 28/08/2026; `Contato provisório — validar vigência.` →
`oficio-2024-03.pdf`, 01/03/2024; sem observação → `seed (base mascarada do v2)`, com
`vigente_desde` nulo em vez de data inventada. Quando o motor importar o catálogo de verdade, esse
campo passa a ser preenchido por documento e os 1.478 "sem documento" desaparecem.

### D.6 Reconciliação de nomes com o catálogo (a checagem que faltava)

Comparação por nome normalizado (sem acento, minúsculas, pontuação removida) contra o texto extraído
de `catalogo-comarcas-2026-06-26.pdf`:

| Conjunto do seed | Total | Nome idêntico no catálogo |
| :--- | ---: | ---: |
| comarcas | 69 | **69 (100%)** |
| fóruns | 72 | **72 (100%)** |
| nós de 1º nível | 226 | 202 (89,4%) |
| nós de 2º nível | 612 | 418 (68,3%) |

As comarcas e os fóruns batem integralmente — a taxonomia do seed é a do catálogo. O que não bate são
nós com nome **composto** que o seed montou a partir de rótulo curto do PDF
("Gabinete, Assessoria e Sala de Audiência da 10ª Secretaria Inteligente (1º, 6º, 8º e 9º JECs)"), não
divergência de conteúdo. Consequência prática: na importação do v3, o nome do nó vem do documento, não
do seed — e a tela precisa exibir nome curto com o complemento como subtítulo, senão a coluna de
unidade fica ilegível.

Isso também fecha, com ressalva, a hipótese sobre a diferença de volume entre v1 e v2: o bloco da Sede
(286 contatos, ausente do v1) responde sozinho pela maior parte dela — 1.807 − 286 = 1.521. A ressalva
é que o v1 não tem base versionada de contatos para comparar (`24_DadosExemplo.gs` tem 122 linhas de
exemplo), então isso continua **hipótese**, não fato medido.

### D.7 O que ficou fora, e por quê

- **Migrations do Laravel (`db_admin`) e esqueleto do motor: adiados por decisão do Enzo
  (11/11/2026).** Só fazem sentido quando a v3 começar de fato; escrever agora seria código para um
  projeto que ainda não abriu. O que existe hoje é o DDL do banco de dados (`db_diretorio`), que é
  justamente o que não muda quando o motor começar.
- **`fonte` + `vigente_desde` foram aplicados no v2** (não no v3 apenas): colunas aditivas, carga por
  `UPDATE` a partir da observação, backfill idempotente no boot, edição manual de telefone/e-mail
  marcada como `edição manual` com a data. Nenhuma rota mudou; `/api/contatos` e `/api/hierarquia`
  passaram a expor os dois campos.
- **Expurgo do histórico** existe como script (`db/expurgar-historico.sql`), não como agendamento: sem
  produção não há o que expurgar. O agregado (`historico_mensal`) é o que sobrevive — contagem por mês,
  ação e entidade, sem dado pessoal.
- RBAC, paginação e migrações: seções 8 e 9, ainda abertas.
- O ingestão oficial (normalização, `fonte` por documento, reconciliação de nome) é do motor FastAPI;
  `db/carregar-seed.py` é o andaime de teste.
