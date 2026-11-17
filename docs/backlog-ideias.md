# Backlog de ideias — diretório de contatos

Registro de 12/11/2026; itens 15–18 acrescentados em 16/11/2026, vindos da revisão externa do
wireframe. Cada ideia tem **fase**, **gatilho** (quando ela pode começar) e **veredito**
(fazer / condicional / não fazer). Nada aqui é compromisso: só vira trabalho por decisão do Enzo.

Regra de leitura: uma ideia só entra na fila quando o gatilho aconteceu. Ideia com gatilho futuro não
autoriza código agora.

## Quadro

| # | Ideia | Fase | Gatilho | Veredito |
| :-- | :--- | :--- | :--- | :--- |
| 01 | Mapa geocodificado (CEP → lat/lon) | v3, busca pública | migrations + seed carregados | **fazer** |
| 02 | Índice de confiança + tela "Saúde da base" | v3, painel | ADR-008 em aberto (procedência no mínimo da v3) | condicional |
| 03 | Ingestão com quarentena e diff por chave | v4 | v4 ter começado, com "motivo medido" | **fazer** (é o motivo da v4) |
| 04 | Sinônimos e intenção na busca | v3, busca pública | busca `ILIKE` + `word_similarity` já em pé | **fazer** |
| 05 | Atestado de vigência em PDF com QR | v3, depois do deploy | ideia 02 entregue | condicional |
| 06 | Paridade de contrato v2 × v3 | v3, CI | rotas de leitura da v3 respondendo | **fazer** |
| 07 | "Modo balcão": busca em menos de 5 s | v3, busca pública | mesma etapa da ideia 01 | **fazer** |
| 08 | URL estável por chave + breadcrumb compartilhável | v3, busca pública | rotas públicas definidas | **fazer** |
| 09 | Benchmark publicado com base sintética 10× | v3/v4 | paginação implementada | condicional |
| 10 | Paleta de comandos (Ctrl+K) | v3, polimento | busca pública fechada | condicional |
| 11 | Observabilidade barata (`/healthz`, eventos, log) | v3, deploy | primeiro deploy no ar | **fazer** |
| 12 | Acessibilidade como gate de CI | v3, CI | CI da v3 existir | **fazer** |
| 13 | Contrato de API documentado (OpenAPI + coleção) | v4 | v4 com rotas | condicional |
| 15 | Idade do dado na unidade (“mais recente: ofício de março/2024 · há 2 anos”) | v3, painel | export do seed (D-3) e selo no contato (F-2) | **fazer** |
| 16 | Copiar a unidade como texto (nome, endereço, canais) | v3, painel | tela de unidade fechada | **fazer** |
| 17 | “Consultados hoje” em Favoritos | v3, polimento | favoritos híbridos com conta | condicional |
| 18 | Imprimir fórum e comarca, além da unidade | v3, impressão | ficha da unidade fechada | **fazer** |

## Detalhe das ideias novas (12/11/2026)

### 01 — Mapa geocodificado por CEP

**O que:** lat/lon dos 73 locais (72 fóruns + Sede) obtidas uma única vez a partir do `cep` e do
`end` já presentes no seed, guardadas em `locais_geo` (lat, lon, precisão, fonte, data). O mapa da
direção A passa a ordenar por distância e agrupar por comarca.

**Por que:** a Direção A é "mapa protagonista"; sem coordenada o mapa é decoração. É a ideia de maior
retorno visual por hora no projeto, e a base já tem o que ela precisa (endereço e CEP em comarcas,
fóruns e nós).

**Como:** script versionado (`db/geocodificar.py` ou `scripts/`) que lê o seed, consulta
Nominatim/BrasilAPI com 1 req/s, e grava CSV **fora do git** (regra do workspace: dado derivado não
versiona). A tabela recebe a carga por script idempotente na chave lógica. Front: Leaflet + tiles
OSM, sem chave de API e sem plano pago.

**Cuidado:** coordenada é dado derivado de endereço público de prédio público — publicável, mas a
fonte e a data entram na tabela, porque geocodificação de CEP erra. Precisão de CEP é aproximada:
rotular como "aproximado" quando o resultado vier do centroide do CEP.

### 02 — Índice de confiança do contato + "Saúde da base"

**O que:** selo por contato derivado de `fonte` + `vigente_desde` + última reconfirmação ("confirmado
há 12 dias", "nunca reconfirmado desde 08/2026"), e uma tela com views SQL baratas: telefone repetido
em N contatos, nó sem contato, comarca sem fórum, `provisório` há mais de 180 dias, contato em nó
inativo.

**Por que:** a procedência já está modelada e testada (9 casos no v2). Falta a leitura: hoje o dado
existe e ninguém vê. É a tela que faz alguém de secretaria dizer "isso eu usaria".

**Dependência:** o ADR-008 deixa em aberto se procedência e fila de reconfirmação entram no mínimo da
v3. Sem essa decisão, a ideia fica desenhada e não implementada. As views de saúde da base, por outro
lado, não dependem da fila — dependem só do seed carregado, então podem vir antes.

### 03 — Ingestão com quarentena e diff por chave lógica

**O que:** planilha nova entra em tabelas de staging; o sistema mostra novo / alterado / removido por
chave (`MUN`/`FOR`/`UNI`/`C`/`UTL`); aprovação linha a linha, com autor e data; aplicação em
transação com dry-run.

**Por que:** é o que um NAP de verdade precisa quando a base é revisada, e é o **motivo medido** que
o ADR-0001 da v4 pede para o FastAPI existir — ETL, `ftfy`, deduplicação. Sem isso, a v4 é simetria.

**Como:** `referencias_externas` e as tabelas de staging já estão previstas no DDL. A v4 não escreve
direto no diretório: escreve em staging, a aprovação é no Laravel, a aplicação é uma rotina única.

### 04 — Sinônimos e intenção na busca

**O que:** tabela `sinonimos` ("reconhecimento de firma" → cartório de notas; "vara do trabalho" →
competência trabalhista) expandida na consulta, somada ao `word_similarity >= 0.45` já medido.

**Por que:** trigrama resolve erro de digitação, não vocabulário. O vocabulário da base tem 467
palavras — pequeno o bastante para um léxico curado à mão, sem modelo e sem custo.

**Como:** `sinonimos(termo, expansao[])` + view `busca_texto` expandida. Publicar no README uma
tabela de 30 consultas reais com resultado antes/depois: é a cultura de "medido, não estimado"
virando feature.

### 05 — Atestado de vigência em PDF com QR

**O que:** PDF por contato ou por unidade — "confirmado em 12/11/2026 por fulano", QR apontando para
a página pública.

**Por que:** é o artefato que alguém imprime e leva para uma reunião. Separa "mais um diretório" de
"sistema que alguém usa".

**Cuidado:** só faz sentido depois da ideia 02; atestado de dado não reconfirmado é atestado de nada.
Nome de servidor que confirmou é dado pessoal — no artefato sai o papel, não o nome, salvo decisão
contrária.

### 06 — Paridade de contrato v2 × v3 (nova)

**O que:** um harness que roda as mesmas ~30 consultas contra a API do v2 (local) e a da v3 e compara
o JSON campo a campo, com diff legível.

**Estado 12/11/2026:** contrato **congelado** — `docs/paridade-contrato-v2.md`, fixture
`docs/paridade/esperado.json` (capturado do v2) e os dois scripts
(`scripts/capturar-contrato.py`, `scripts/comparar-contrato.py`, autoteste PASS). Falta só capturar o
lado da v3 quando as rotas de leitura existirem. A captura já pagou o custo: revelou que o `LIKE` do
v2 é sensível a acento (`cartorio` 0 × `cartório` 209), que a busca satura no limite de 20 e que
`unidades`+`setores` do v2 não mapeiam 1:1 nos 838 `nos` da v3.

**Por que:** os 94 casos do v2 são a especificação da v3. Hoje a paridade é afirmação em README;
com o harness, vira prova executável — e é argumento forte em entrevista ("migrei de stack mantendo
contrato, e provei"). Custo baixo: o v2 já roda local e o contrato já está documentado.

### 07 — "Modo balcão": busca em menos de 5 s (nova)

**O que:** um caminho de tela otimizado para quem atende telefone: foco no campo ao abrir, resultado
com "copiar telefone" e "copiar e-mail" a um toque, teclado numérico para ramal, sem exigir mouse.

**Por que:** é o uso real do sistema, e ninguém desenha para ele. Barato, e conta uma história de
produto em vez de uma história de CRUD.

### 08 — URL estável por chave lógica + breadcrumb (nova)

**O que:** rotas legíveis e permanentes por chave (`/c/FOR0001/UNI0040`), breadcrumb
Sede/comarca → fórum → nó, e o link colável no WhatsApp.

**Por que:** hoje toda navegação é estado de tela. Link estável é o que permite alguém mandar "o
contato do protocolo de Vitória" para um colega — e é SEO/SSR de graça no Blade.

### 09 — Benchmark publicado com base sintética 10×

**O que:** gerador determinístico (Faker com seed) de ~18 mil contatos, e um `k6`/`locust` com
números no README: p50/p95 por rota, antes e depois do índice.

**Por que:** é a forma honesta de falar de performance sem inventar número, e prova que paginação e
índice GIN servem para algo. Condicional: só depois de paginação implementada, senão mede o que não
existe.

### 10 — Paleta de comandos (Ctrl+K)

**O que:** sobreposição de busca rápida com navegação por teclado sobre a árvore de locais.

**Por que:** sinal de front-end moderno, custo baixo em Alpine. Condicional: entra em polimento, não
antes da busca pública funcionar.

### 11 — Observabilidade barata

**O que:** `/healthz` (banco + versão do commit), log estruturado em JSON, tabela `eventos` com
contagem por rota e erro, e um resumo diário impresso no log.

**Por que:** no free tier não há APM. Três arquivos dão o que um entrevistador quer ouvir sobre
operação, e é o que permite dizer "sei o que está quebrado" em vez de "acho que está no ar".

### 12 — Acessibilidade como gate de CI

**O que:** axe-core ou Lighthouse no CI das páginas públicas, com limite de falha; contraste AA como
o v2 já corrige na v1.1 do quiz.

**Por que:** a v3 é o projeto que vai para entrevista; acessibilidade quebrada é o primeiro defeito
que alguém nota. O v2 já tem acessibilidade no workflow — manter a régua.

### 13 — Contrato de API documentado

**O que:** OpenAPI gerado, exemplos de request/response e uma coleção HTTP versionada.

**Por que:** a v4 é serviço HTTP por definição; contrato publicado é o que a torna integrável e não
só um script.

### 14 — Reconciliação contra o catálogo oficial — FEITA (12/11/2026)

**O que:** confrontar a base com os três documentos de origem (catálogo por comarcas de
junho/2026, catálogo da Sede de agosto/2026 e ofício de março/2024) e provar a aderência por
contagem e por nome.

**Estado:** feito e registrado em `docs/reconciliacao-2026-11-12.md`, com o script
`scripts/reconciliar-catalogo.py`. Resultado: 69 comarcas e 72 fóruns sem falta nem sobra;
659 nós de comarca e 179 da Sede conferidos; 295 de 297 e-mails confirmados por documento;
uma única divergência de conteúdo (e-mail provisório no PID de São José do Calçado, onde o
catálogo tem o endereço real).

**Por que valeu:** a conferência de 12/11 mostrou que a base **não** está desatualizada na
estrutura — o que estava em dúvida era o inverso. Também separou o que é diferença de redação
(5 casos) do que é dado inventado (1 caso), que é a distinção que faltava para decidir se
alguma coisa precisava ser corrigida antes do deploy.

### 15 — Idade do dado na unidade (nova, 16/11/2026)

**O que:** abaixo de “10 contatos · 2 setores”, uma linha de 11 px: “Mais recente: ofício de março/2024 ·
01/03/2024 · há 2 anos e 8 meses”; para os 1.478 sem documento, “Sem documento de origem registrado”.
A mesma linha entra na ficha impressa.

**Por que:** é a pergunta que a pessoa tem ao discar (“este número está atual?”) e hoje ela exige abrir a
ficha de cada contato. Usa `fonte` e `vigente_desde`, que o seed já tem em 329 contatos — é o selo do v2
trabalhando na escala da unidade.

**Gatilho:** export do seed com procedência (D-3) e o selo por contato (F-2).

### 16 — Copiar a unidade como texto (nova, 16/11/2026)

**O que:** botão “Copiar tudo” ao lado de Imprimir: nome, endereço e todos os canais, no texto da ficha.

**Por que:** servidor passa número por mensagem, e o valor solto perde o dono. Sem dependência de dado.

### 17 — “Consultados hoje” em Favoritos (nova, 16/11/2026)

**O que:** bloco com os últimos cinco locais visitados (via trilha), com estrela para fixar.

**Por que:** o atendimento volta aos mesmos dez cartórios, e `/favoritos` vazio desperdiça dois terços do
painel. Custou 3 h no protótipo; no v3 depende de favoritos com conta.

### 18 — Imprimir fórum e comarca (nova, 16/11/2026)

**O que:** além de “Imprimir unidade”, “Imprimir fórum” e “Imprimir comarca”, com as unidades e os
contatos agregados.

**Por que:** a ficha de uma unidade pequena sai 70% vazia; o escopo natural de quem afixa papel na parede é
o fórum inteiro.

## Não fazer (registrado com motivo)

| Ideia descartada | Por quê |
| :--- | :--- |
| Quarto repositório para a v4 | ADR-009: dois deployáveis, um repo. DDL duplicado e deriva de docs. |
| Juntar histórico de v1/v2/v3 num repo | Reescreve SHA publicado, briga com o dossiê da AE3 e com o CI de cada um. Ganho cosmético. |
| Submódulos git para o seed | Acoplamento pior que o caminho de irmão, e mais um passo para quem clona. |
| Busca vetorial / LLM na consulta | Sem usuário e sem medição que justifique; `pg_trgm` + léxico resolve o caso real. Custo de infra e de latência no free tier. |
| PWA / offline | O uso é balcão com rede; service worker em Blade server-rendered é complexidade sem retorno. |
| `Next.js`/SPA no front | A v3 é Blade + Livewire de propósito (stack do portfólio). SPA reabriria a decisão de 10/11. |
| Mapa com tiles pagos | Restrição vigente: só free tier. OSM raster basta. |
| Autenticação própria (senha local) | Google OAuth já decidido; senha local traz recuperação, hash e política sem ganho de portfólio. |
