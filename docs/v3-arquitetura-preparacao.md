# V3 (Laravel) e V4 (FastAPI): plano

Registro de 11/11/2026, revisado em 12/11/2026. Vive em `docs/` deste repositório: foi o plano vigente da v3 e da v4 até a consolidação do `plano-v3.md` (a v4 foi adiada pelo ADR-010), e substitui, **para a v3**, o plano de dois serviços de `docs/fase-3-arquitetura-e-adrs.md` (revisões de 10 e 11/11) — desse plano só continua valendo o que está em "Arquivado para a v4", e o documento fica preservado como especificação da v4. Nome da fase do FastAPI: **v4**, não "v3.1" (`docs/adr-008-v3-um-servico-e-fastapi-na-v4.md`). Código lido: v2 no HEAD a93aeb1 (12/11, 13:43; clone raso, somente leitura; suíte rodada em cópias isoladas, nos dois modos). O SQL e o PHP citados aqui são esboço: não há Postgres nem PHP neste ambiente.

## O que mudou na revisão de 12/11
- **HEAD do v2:** d565fda → a93aeb1. A suíte passou de 71/72 para **94 casos** (medido; ver "Testes").
- **README do v2:** já descreve a v3 como Laravel, mas o agente reescreveu à mão (commit a93aeb1); o `readme-v3-laravel.patch` provavelmente não se aplica mais (ver "Roadmap").
- **Ids:** `M1`, `F3`, `U12`, `S3`, `C746` são ids de execução do v2, não ids do arquivo de seed (ver "Chaves").
- **O v2 ganhou funções** que o plano de 11/11 não listava: procedência, conferência ("✓ Conferi"), histórico mensal, backup, exportação CSV com procedência (ver "O que o v2 entrega à v3").
- **Neon:** os itens "não verificado" de armazenamento e `pg_cron` foram resolvidos na documentação oficial (ver "Esboço da v3").
- **Repositório:** além de `refs/pull/1/head`, agora existe `refs/pull/2/head` (olhei os dois: ver "Verificado").

## Decisões do usuário (11/11/2026)
- **v3:** Laravel + Filament + PostgreSQL, com o diretório como módulo do próprio Laravel. Hospedagem: Render (imagem Docker) + Neon (Postgres).
- **v4:** extração do diretório para FastAPI, só depois de a v3 estar no ar e quando houver motivo concreto (ver "Critério e candidatos").
- **Exportação assíncrona fica no Laravel (filas).** Saiu da lista de justificativas do FastAPI.
- **Refatoração do v2 para por aqui:** deploy no Render, correção dos 286 contatos e README. Está cancelada a extração de repositório, serviços e esquemas que preparava o FastAPI.
- **Narrativa:** a v3 reimplementa o sistema a partir do contrato de rotas, do modelo de dados e dos testes do v2. Nenhum código Python migra.
- **Interface da v3: direção A (mapa protagonista).** Vale como especificação de design, reconstruída do zero em Blade, Livewire, Alpine e Tailwind; nenhum HTML, CSS ou JS do mockup nem o `mapa.js` do v2 é reaproveitado. Mapa em Leaflet sem camada de tiles, com GeoJSON municipal do ES versionado no repositório (fonte IBGE; formato e licença a confirmar). Briefing para o Hermes refazer o mockup com mapa real e layout de celular em `docs/v3-interface.md`.

## Roadmap
- Fase 1 (v1): Google Apps Script + Google Sheets (MVP original; é Google Workspace, não "ecossistema Office": corrigir em qualquer texto público).
- Fase 2 (v2): Python stdlib + SQLite, dados fictícios, contratos de rotas. Público.
- Fase 3 (v3): Laravel + Filament (TALL) + PostgreSQL (Neon), um deploy só.
- Fase 4 (v4): serviço FastAPI para o que Python resolve melhor (`ftfy`, deduplicação, busca fonética em português).
- **README do v2 (a93aeb1):** já fala em v3 com Laravel + Blade/TALL num serviço Docker e Postgres gerenciado (Neon): tabela de seis aspectos (linhas 29–34), "Fase 3" (~linha 90) e bloco "Sucessor" (~linha 238). Isso saiu do commit a93aeb1 ("docs: tabela v1/v2/v3 em 6 aspectos e as tres fases mais curtas"), não do `readme-v3-laravel.patch`. O patch está nas mãos do usuário, não neste ambiente, e não o testei contra o HEAD novo: tratar como obsoleto até alguém rodar `git apply --check`. O README **não cita Filament nem a v4/FastAPI**; decisão do usuário se cita. Ele também promete para a v3 "PHP-FPM atrás do nginx, pool de conexões", "expiração de sessão, papéis granulares" e "testes de contrato" (tabela): alinhar o plano ou o README quando a v3 sair, para não prometer o que o código não tem.

## O que o v2 entrega à v3 (especificação, não código)
- **Modelo de dados e seed** (conferido no arquivo `seed_mascarado.json` em a93aeb1): 1.807 contatos (1.521 nas 69 comarcas e 286 da estrutura central); 70 comarcas (69 com `ibge` de 7 dígitos, únicos, começando em 32; a Sede Administrativa tem `tipo` ORGAO e `ibge` vazio); 72 fóruns; 856 nós (226 sem pai, que o carregador do v2 transforma em unidades, e 612 com pai, que viram setores; todos com id `UNI####`); 11 úteis. Ids únicos em todas as listas, e `pai` e `uo` apontam sempre para um nó existente. **631 contatos não têm `uo`** (ficam direto no fórum ou na comarca; ver `test_contatos_gerais`), então o modelo da v3 precisa aceitar contato sem unidade ou setor. E-mails sempre `@exemplo.jus.br` (297 contatos e 72 de fóruns e comarcas); telefones (1.019) e WhatsApp (47) terminam em `XXXX`; os 444 ramais vêm como `XXXX` inteiro.
- **Contrato de rotas:** hierarquia (com `codigoIbge` por comarca), contatos, busca, pedidos, notificações, resumo e, novos desde 11/11: procedência (`fonte` e `vigente_desde`; mudar o valor de um contato grava "edição manual" e a data), conferência ("✓ Conferi": grava `conferido_em` e `conferido_por`, exige sessão e escopo do setor, deixa rastro no histórico), histórico mensal (`/api/historico/mensal`: 12 meses terminando no corrente, zero nos meses vazios, `total_geral`), backup (`/api/backup`, `VACUUM INTO`: só faz sentido em SQLite), `/healthz` e exportação CSV (montada no navegador, com quatro colunas de procedência e conferência). Fonte: docstrings dos testes e linhas de `app.py`; não li os handlers inteiros.
- **Regra de escopo:** `_escopo`, `_cadeia`, `_pais` e `_cobre` continuam como métodos do `Handler` em a93aeb1 (`app.py`, linhas 1282 a 1354). Vira Policy em PHP; a tabela de casos de `tests/test_regras.py` é a especificação.
- **Testes:** em a93aeb1 a suíte `unittest` tem **94 casos rodando**: 95 executados no modo 1 (1 pulado) e 96 no modo 0 (2 pulados), contra servidor local, com `SIMULACAO_AUTO_APROVAR` e `ADMIN_EMAILS` no ambiente do executor. Medi essas duas pernas, em cópias isoladas e com Python 3.13; a terceira (modo 1 sem `ADMIN_EMAILS`) não. README, cartilha e `scripts/contagens.json` dizem 94, e `scripts/conferir-contagens.py` roda no CI, então o "71/72" e o "56 na tabela" do plano de 11/11 estão superados. A classificação abaixo vem das docstrings e do número de funções de teste; não li os corpos.
  - **Portar para Pest** (regra ou contrato): `test_regras` (escopo e `TestContagens`, menos o trecho que abre o SQLite direto), `test_procedencia` (9), `test_conferencia` (5), `test_contatos_gerais` (3), `test_historico_mensal` (8), `test_somente_leitura` (3), `test_seguranca_demo` (8), `test_integridade_dados` (11; vira teste de migração e de seed: órfãos, nulos, ids repetidos), `test_sem_telefone_real` (2; vira teste do seed) e `test_saude` (3).
  - **Decidir:** `test_demonstracao` (11; trilha de demonstração que o banco grava ao nascer; só faz sentido se a v3 tiver o mesmo histórico de demonstração).
  - **Reescrever, não portar:** `test_csv_export` (trava peças do JS do navegador; a v3 exporta no servidor).
  - **Não portar:** `test_backup_restauracao` (`VACUUM INTO`; no Postgres seria `pg_dump` ou branch do Neon), `test_cache_busting`, `test_cache_http` e `test_render_seguro` (front estático do v2).
  - A fumaça Playwright (44 verificações) e `acessibilidade.mjs` (10 checagens) são do front do v2 e não portam.
- **Já travado pelo v2:** `TestContagens` exige lista plana = árvore = banco. Foi a divergência que escondeu os 286 contatos da estrutura central fora de `/api/contatos`; está corrigido e o teste passa nos dois modos em a93aeb1.

## Esboço da v3
- **Módulos (código, não serviços):** `Directory` (comarcas, fóruns, unidades, setores, contatos, úteis, histórico) e `Access` (usuários, acessos, pedidos, notificações). `Directory` não depende de `Access`; `Access` referencia nós pelo `codigo`, nunca pelo id do banco. Recomendação minha: custa pouco agora e é o que a extração da v4 vai exigir. Banco: **um único Postgres na v3**; a separação `db_diretorio`/`db_admin` só volta na v4 (ADR-008).
- **Chaves:** `codigo` único e imutável por nó; o `bigint` fica interno. **Atenção:** `M1`, `F3`, `U12`, `S3` e `C746` são ids de execução do v2, gerados por `novo_id` na ordem em que o seed é lido (`app.py`, ~linha 473; carga em `_carregar_seed_json`, ~linha 626). Eles **não aparecem** em `seed_mascarado.json`, cujos ids são `MUN0000…`, `FOR0001…`, `UNI0001…` (unidades e setores juntos) e `C000001…`. Os ids de execução mudam se a ordem do seed mudar. Recomendação minha: usar o id do arquivo de seed como `codigo` dos nós do demo (estável por construção, únicos conferidos) e ULID/UUID gerado na aplicação para os criados depois. Decisão do usuário. A decisão de 10/11 continua valendo: o `codigo` também protege os acessos se o seed do demo for recriado.
- **Autorização:** Policies em PHP, com testes em Pest cobrindo a tabela de casos do v2. Preferir Resources Eloquent do Filament (Policies se aplicam; conhecimento geral). Na doc 5.x, tabela com `records()` (dados não-Eloquent) não aplica Policies nem global scopes: só se a fonte for externa, e aí a autorização é manual.
- **Busca:** `pg_trgm` + `unaccent`. `unaccent` não é IMMUTABLE, então índice ou coluna gerada pede um wrapper (conhecimento geral; testar cedo). O v2 devolve `nome_hl` com `<b>` embutido: na v3, devolver trechos ou offsets e deixar a marcação para a interface.
- **Exportação:** fila do Laravel com tabela de jobs (`pendente`, `processando`, `pronto`, `falhou`), `202 Accepted` e endpoint de consulta; executor trocável (`sync` no demo, worker depois). O disco do Render free é efêmero: guardar o resultado no banco ou em armazenamento externo. Não verifiquei se o Render free permite processo em segundo plano. Referência do v2: o CSV sai do navegador com as colunas de procedência e conferência; a v3 mantém essas colunas, geradas no servidor.
- **Hospedagem:** o Render exige imagem Docker para PHP; o serviço free dorme após 15 min e leva cerca de 1 min para acordar (informação de 11/11, não rechecada). No Neon Free o compute suspende após 5 min ocioso e isso não dá para desligar (confirmado na FAQ oficial, 12/11); a volta leva de centenas de milissegundos a alguns segundos (as fontes divergem). Primeiro acesso = Render acordando + Neon acordando. O `/healthz` não deve tocar no banco, como no v2.
- **Neon (FAQ oficial "Free plan limits and quotas", atualizada em 03/11/2026, lida em 12/11):** Free com 100 projetos, **1 GB de armazenamento por projeto** (20 GB no total), 100 CU-horas por projeto por mês, até 2 CU quando ativo, suspensão após 5 min sem opção de desligar. 1.807 contatos cabem com folga. Isso substitui a dúvida de 11/11 entre 1 GB e 0,5 GB. O Free ser permanente, ao contrário do Postgres free do Render (expira em 30 dias), é informação de 11/11 que não rechecei. `pg_trgm`, `unaccent`, `fuzzystrmatch` e `pgvector` constam como suportados (levantamento de 11/11, não rechecado).
- **`pg_cron` no Neon (doc oficial, 12/11):** existe, exige habilitar `cron.database_name` e reiniciar o compute, mas **só roda com o compute ativo**. Com a suspensão obrigatória do Free, não serve para rotina agendada: agendar fora do banco. A doc sugere Function Triggers; não verifiquei se existem no Free. O scheduler do Laravel precisa de um processo ou cron externo: não verifiquei o que o Render free oferece.
- **Dados pessoais:** contatos com remoção física ou expurgo agendado (num cenário real, LGPD).
- **Demo:** somente leitura, pela mesma razão do v2 (aberto ao público, sem vigilância). Seed determinístico.
- **Não repetir da v2 (reverificado em a93aeb1 onde indicado):**
  - **FKs não aplicadas.** O esquema declara `REFERENCES` (`app.py`, linhas 260 a 333), mas `PRAGMA foreign_keys` não aparece em lugar nenhum do `app.py`, e o SQLite não aplica FKs sem ele. A integridade é auditada por teste (`test_integridade_dados`), não pelo banco. O README diz "SQLite (FKs, …)" (linhas 29 e 74) sem esse detalhe: se perguntarem em entrevista, dizer que as chaves são declaradas e a integridade é auditada por teste. Um esquema PostgreSQL com FKs e CHECK carrega o seed sem ajuste (verificado em 86999f3, não rechecado).
  - **`PUT` de contato não atualiza `atualizado_em`** (`app.py`, ~linha 2285; a coluna só tem o default da criação). Continua verdadeiro, mas o v2 agora guarda `fonte`, `vigente_desde` (quando o valor muda) e `conferido_em`/`conferido_por`. Na v3: `updated_at` do Eloquent mais esses campos.
  - **Estado em memória:** `SESSIONS`, `OAUTH_STATES` e `_CONT` (`app.py`, linhas 64, 65 e 470) continuam; o `lru_cache` não rechequei.
  - **Não rechecado em a93aeb1:** `aprovar_pedido` e `recusar_pedido` devolverem status HTTP dentro da regra e enviarem e-mail antes do commit (visto em 86999f3).

## Escopo mínimo publicável da v3 (ordem)
1. Migrações e seed (1.807 contatos fictícios) + ambiente com Sail.
2. Policies com Pest (regras de escopo do v2).
3. Busca pública em Livewire com `pg_trgm` e `unaccent`, com o painel e o mapa da direção A (`docs/v3-interface.md`). A Sede Administrativa (286 contatos, sem código IBGE e sem polígono) precisa de entrada própria no painel; o mapa e o layout de celular são o grosso deste item.
4. Painel Filament: CRUD, pedidos, aprovação, histórico.
5. CI com Pest, deploy no Render + Neon e README com o link do demo.
Exportação e notificações entram depois. Se o item 5 não existir, a v3 não conta como publicada.

**Decisão aberta (do usuário):** procedência (`fonte`, `vigente_desde`) e conferência ("✓ Conferi" e fila de reconfirmação) entram no mínimo? O v2 tem os dois, com 14 funções de teste (9 + 5) que já servem de especificação. Recomendação minha: a procedência entra nas migrações (item 1) porque é só coluna e regra simples; a conferência fica para depois do item 5. É recomendação, não decisão.

## Critério e candidatos para o FastAPI (v4)
Critério: separar um serviço só quando o trabalho é pipeline de dados ou de texto em que Python é claramente melhor, ou precisa ficar fora do ciclo de requisição. Autenticação, pedidos e notificações ficam no Laravel.

| Candidato | Veredito | Por quê |
|---|---|---|
| Importação com dry-run (CSV/XLSX → `ftfy` → normalização → validação Pydantic → relatório de aceitos, corrigidos e rejeitados; grava só quando o admin confirma) | Justifica | Lugar natural do `ftfy`. Não conheço equivalente maduro em PHP (não pesquisei a fundo). |
| Deduplicação (RapidFuzz sugere pares; o admin mescla no Filament) | Justifica, se medida | Reaproveita o motor de similaridade. Comparar com `similar_text`/`levenshtein` do PHP antes de afirmar vantagem. |
| Busca fonética em português | Em parte | A tolerância a erro de digitação roda no Postgres. Sobram as regras fonéticas (Thiago/Tiago, Jéssica/Jessica, Luiz/Luís) e o ranking. Algoritmos clássicos são orientados ao inglês: validar com nomes brasileiros. |
| Painel de qualidade dos dados | Só como consumidor | Agregados em SQL cabem no Laravel; só entra se exibir relatórios do FastAPI (duplicados, formatos inválidos). |
| Embeddings / busca semântica | Opcional, por último | Exige provedor externo ou modelo local que provavelmente não cabe numa instância free (não verifiquei). Vira enfeite se a busca textual já resolve. |
| Exportação assíncrona | Não justifica | As filas do Laravel resolvem; só mudaria se dependesse de biblioteca Python. |

ADR-0001 da v4: "Extraí porque [motivo concreto, medido]". Sem motivo medido, não extrair. O ADR também registra o que não se faz: broker, saga, gateway, service mesh.

## Arquivado para a v4 (do plano de 10/11)
Decisões do usuário que voltam quando o FastAPI existir:
- Autorização só no Laravel. O FastAPI valida duas API Keys (leitura e escrita; `escrita` inclui `leitura` e é a única que enxerga arquivados e histórico; chaves por cliente, só o hash guardado) e recebe `X-Acting-User` só para registrar o autor. Esse cabeçalho é declaração do cliente: gravar id e nome (nome em percent-encoding) e marcar como "informado pelo Laravel".
- Database-per-service: `db_diretorio` e `db_admin`, sem FKs entre eles, relações por chave lógica. `arquivado` só nos nós da hierarquia que o Laravel referencia; contatos por remoção física ou expurgo.
- Contrato `/api/v1` OpenAPI-first; retry automático só em GET; POST com `Idempotency-Key` obrigatório; `X-Request-ID` nos dois serviços; health e readiness.
- Dois bancos: o Neon Free permite 100 projetos (FAQ de 03/11/2026); o limite de um Postgres free por workspace é do Render, não do Neon.
- Custos da separação, para o ADR citar: o Laravel precisa da cadeia do alvo (o `GET` do setor devolve `cadeia`; endpoint em lote para nomes, sem N+1); painel e Registro são compostos de duas fontes; criar contato e notificar deixa de ser transação; a única consistência entre serviços é arquivar um nó que tem acessos ou pedidos no Laravel (reconciliação por rotina).

## Como explicar o v2 (versão curta; a longa está no chat de 11/11)
- O v2 não foi um degrau planejado para o Laravel: a v3 era FastAPI até 10/11 e virou Laravel em 11/11. Não dizer que foi.
- Dizer o que ele provou, com fatos que o repositório sustenta: o produto roda fora do Google, com esquema de FKs declaradas, suíte de 94 casos, CI em duas pernas e demo público; os testes acharam um bug real (os 286 contatos escondidos).
- Dizer o que o v3 aproveita: contrato, modelo e testes. Não dizer "migrei o código", "produto próprio" nem "independente" (as telas partem do v1, como o README declara).
- O motivo de ter escolhido Python no v2 é do usuário: usar o motivo verdadeiro. O README dá o de ser só stdlib (zero dependências, deploy gratuito trivial).

## Verificado em 12/11/2026
- **Seed e suíte:** números do seed e da suíte acima, medidos por mim em a93aeb1 (suíte em cópias isoladas, Python 3.13, modos 1 e 0, ambos com `ADMIN_EMAILS`).
- **`refs/pull/1/head` (0f8b4b6, 11/11, "ci: fumaca sem apt…") e `refs/pull/2/head` (af85a5a, 12/11, "ci: acessibilidade medida entra no workflow…"):** autor e committer são o e-mail noreply do usuário; a única base de dados nas duas árvores é `seed_mascarado.json`. Sem seed antigo nem nomes.

## Não verificado
- Versões de PHP, Laravel, Filament e Livewire a usar: decidir no dia de criar o repositório e checar os requisitos.
- Processo em segundo plano no Render free; Laravel através do pooler do Neon (PgBouncer em modo transação costuma pedir cuidado com prepared statements: conhecimento geral); wrapper IMMUTABLE do `unaccent`; Function Triggers da Neon no Free.
- Formato, licença e atribuição da malha municipal do IBGE para o mapa (não consegui abrir a documentação da API); comportamento do Leaflet com os polígonos pequenos da Grande Vitória.
- O run do GitHub Actions no HEAD atual: não o vejo (só por print do usuário).
- A terceira perna da suíte (modo 1 sem `ADMIN_EMAILS`) e `scripts/conferir-contagens.py --medir`.

## Fontes
- Neon, "Free plan limits and quotas" (atualizada em 03/11/2026): https://neon.com/faqs/free-plan-limits-and-quotas
- Neon, extensão `pg_cron`: https://neon.com/docs/extensions/pg_cron
