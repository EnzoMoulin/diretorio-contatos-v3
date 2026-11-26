# Revisão do wireframe pelo Claude — rodada 2, triagem

**Data:** 16/11/2026 · **Fonte:** `IDEIAS PARA O V3/ramal-revisao-wireframe-2.md`, resposta ao
`wireframe-v3-telas.pdf` de 16 páginas (gerado às 13:05).
**Material enviado sobre:** não registrado no envio — PDF de 16 páginas de 16/11/2026 (o rastreio de
base por commit começou em 26/11/2026).
**Método:** cada afirmação foi conferida no protótipo (`IDEIAS PARA O V3/wireframe`), no seed do v2
ou no texto vetorial do PDF. Onde não reconferi, está dito.

## Veredito

A rodada 2 acerta o alvo: para de elogiar o desenho e pergunta o que o mapa responde. Três dos cinco
problemas são defeitos reais e baratos (a busca não acende o mapa, o chip cobre conteúdo, as
contagens e a legenda não fecham), um é a melhor ideia de produto da revisão ("perto de mim" por
comarca) e um (pinos) diagnostica certo, mas propõe mexer numa decisão documentada.

Três imprecisões mudam o que fazer: o chip **tem** regra (esconde com o cartão de "perto de mim"
aberto), o aviso de "posição aproximada" **já está** na linha da lista, e o círculo de precisão
**existe** (só não é legível). E um número atinge o briefing, não a tela: 454/1.807 = **25,1%** — o
`docs/identidade-visual.md:118` já diz 25%; o prompt dizia "~30%".

## Os cinco problemas

| # | Afirmação | Evidência | Veredito |
|---|---|---|---|
| 1 | "Perto de mim" ordena por distância e chama fóruns de "unidades" | `mapa.js:792` escreve "unidades mais próximas"; `Diretorio::locaisNoMapa()` (`Diretorio.php:169-194`) devolve só os 72 fóruns; a p. 13 lista fóruns; o `sort` (`mapa.js:749-760`) é haversine puro | **Procede.** Decisão de produto. A resposta certa é a comarca, não o vizinho geográfico |
| 2 | Pinos sem identidade e desenhados para mouse | `.pino-alvo span` 13 px girado (`app.css, linhas 2080 a 2090`), alvo de 44 px; `click` navega direto (`mapa.js:656-658`); dica só no hover | **Procede em parte.** O two-tap é decisão; o anel do disco não deve sair |
| 3 | A busca não acende o mapa | `PainelController::pagina()` (`:150-170`) monta o mesmo `mapa`/`locais` para toda rota; a view da busca não passa as comarcas que casaram; a p. 6 é o mapa da p. 2 | **Procede.** É o problema mais caro de reputação: a tela principal do produto não usa o mapa |
| 4 | O chip tampa o que deveria mostrar | `.mapa-chip` centrado (`app.css, linhas 2157 a 2163`), `bottom` vem da folha (`mapa.js:719-723`); cobre o litoral e discos na p. 12 e um pino na p. 15 | **Procede na geometria.** A "falta de regra" não: esconder com o cartão aberto é decisão (`mapa.js:798`) |
| 5 | Números e chave que não conferem | Cabeçalho = 11 contatos que casaram (`busca.blade.php:5,27-31`); linhas = 25 contatos dos nós (`:115`); legenda 8/13/18 px (`app.blade.php:214-216`) contra 11,4/19,9/27,8 do `raioDe` (`mapa.js:192,291`); 454/1.807 = 25,12% | **Procede**, com uma correção: a legenda certa é 11,4/19,9/27,8; a dele (12/20/28) fica a menos de 1 px |

### 1. "Perto de mim" — aceitar a tese, com a versão barata

O cartão responde "qual fórum está mais perto", e a distância em linha reta entre municípios
separados por baía não é o que o cidadão precisa. A malha municipal já está versionada
(`data/es-municipios.geojson`), cada comarca carrega `ibge` (`Diretorio.php:133`) e o mapa já tem os
polígonos em memória: ponto-em-polígono é ~30 linhas, sem terceiros.

Mudança: bloco `VOCÊ ESTÁ EM` / `Vitória · comarca com 2 fóruns`, os fóruns dessa comarca (máx. 2),
depois `OUTROS FÓRUNS PRÓXIMOS · EM LINHA RETA` até 5. Cortar o "de 4,3 km a 6,2 km".

Estados: município hachurado → frase que não inventa comarca; fora do ES → sem lista; permissão
negada e timeout já existem (`mapa.js:818-835`). O `perto-meta` já imprime "posição aproximada"
(`mapa.js:785`); falta só o `~` na distância quando a precisão do navegador passar de 1 km.

Custo: ~1 h no protótipo, 0,5 dia na v3. É a única mudança que troca a resposta da tela, não a
pintura.

### 2. Pinos — dois toques só onde eles se sobrepõem

O diagnóstico é certo: 13 px de losango com alvo de 44 px, dois pinos a 22 px um do outro e nenhum
estado de seleção. Mas:

- No desktop o vínculo pino ↔ painel **existe** em movimento: `mouseover` chama `realcar(l.comarca)`
  (`mapa.js:653-655`). O PDF não mostra hover; "nada liga" vale para o papel, não para a tela.
- **Não tirar o anel branco do disco em zoom ≥ 10,25.** Ele é o realce da seleção nas quatro
  superfícies (polígono, disco, linha do painel, recorte). O "aproximado" é um losango tracejado
  (`app.css, linhas 2105 a 2109`), não um anel; tirar o anel remove o único retorno de seleção no mapa para
  resolver uma colisão fraca.
- Two-tap: aceitaria **só no zoom de comarca**, onde os alvos se sobrepõem; em "perto de mim" a lista
  já desambigua e o número no pino resolve. O cartão com `Abrir ›` de 44 px é a parte boa.
- 55% de opacidade nos pinos de outras comarcas: barato e correto; exige classe por pino, ~20 min.

Custo do pacote: ~2 h.

### 3. A busca acende o mapa — aceitar

A p. 6 mostra 9 resultados em 8 comarcas + Sede e o mapa não muda um disco. É o mesmo mapa da p. 2
porque `PainelController::pagina()` entrega o mesmo `cfg` a todas as rotas.

Mudança mínima: o controller passa as comarcas que casaram (de `$r['comarcas']` e do `comarca` de
cada `$r['locais']`); o mapa pinta as comarcas com resultado a 100% + anel de 2 px e as demais a 35%.
A Sede não tem polígono: acende o item "Sede" do rail com um ponto. Sem pinos e sem mudar o zoom.
Cabeçalho: `9 unidades e setores`, e a contagem de contatos fica na seção "contatos", que já a
imprime.

Custo: ~1 h. É o que transforma o mapa de decoração em resposta na tela que mais importa.

### 4. O chip — regra de zoom e sumir na tela de unidade

A geometria procede. O que não procede é "sem regra declarada": o chip se esconde quando o cartão de
"perto de mim" abre (`mapa.js:798`) porque dois flutuantes no mesmo canto competem pela leitura — é
decisão registrada.

Falta a regra de zoom: `display:none` com `zoom >= ZOOM_PINOS` (10,25), quando o chip já não tem
função e cobre o mapa. Na tela de unidade (p. 15), o chip cobre o pino do próprio local; ali ele não
deve aparecer — a saída é o chip "voltar".

O `right:16px` do Claude: testar antes. Em 6,75 deve cair sobre o oceano; em 8,75 pode cobrir
Guarapari. A regra de zoom + sumir na unidade resolve a função sem mover o chip.

### 5. Números — três correções e uma do briefing

- **Cabeçalho da busca:** 11 é o número de contatos que casaram com o termo (`total_contatos`), 25 é
  a soma dos contatos dos nós listados. Os dois são verdade e a palavra é a mesma. Deixar o cabeçalho
  só com `9 unidades e setores`; a seção "contatos" já mostra 11 (`busca.blade.php:112-115`).
- **Legenda:** os tamanhos são escritos à mão (`app.blade.php:214-216`) e o mapa os calcula
  (`raioDe = 4 + 13·√(n/286)`, com o máximo na Sede, 286). Com o mesmo cálculo: 5 → 11,4 px, 60 → 19,9,
  166 → 27,8. A legenda do Claude (12/20/28) fica a menos de 1 px; o certo é ler do cálculo.
- **25,1%, não ~30%:** 454/1.807 = 25,12%; 454/1.521 = 29,85%. A decisão 1 do prompt misturou as
  duas bases. O `docs/identidade-visual.md:118` já está certo ("454 dos 1.807 contatos, 25%"). O
  próximo prompt leva "25% (454 de 1.807)".
- **7 municípios ≠ 454 contatos:** 166+92+137+59+37+84+13 = 588. O chip enquadra os 7 da RMGV; os 454
  são 4 comarcas. A decisão 1 precisa dizer qual conjunto está em jogo.
- **Cabeçalho do celular:** `inicio.blade.php:6` escreve "69 comarcas e a estrutura central · 1.807
  contatos" e quebra com "CONTATOS" órfão. A linha da Sede já diz "estrutura central · sem polígono
  no mapa"; no celular, `69 comarcas · 1.807 contatos`.

## O que ele errou ou já está feito

1. **Chip sem regra:** existe, e é deliberada (`mapa.js:798`).
2. **"Posição aproximada" só na dica:** já está na linha da lista (`mapa.js:785`). Falta o `~` na
   distância, não o aviso.
3. **Ponto da posição sem círculo de precisão:** existe (`L.circle` com `radius = accuracy`,
   `mapa.js:759-767`), mas com 300 m a este zoom é invisível. O problema é legibilidade, não
   ausência.
4. **"Pinos somem abaixo de 10,25 em área rural":** o enquadramento de "perto de mim" é uma caixa
   fixa de 16 km (`mapa.js:778`), não os 5 pinos; o zoom resultante fica ~10,75 no celular e ~12 no
   desktop, com ou sem área rural. Numerar os 5 continua bom para casar lista e mapa, mas não para
   evitar desaparecimento.
5. **"Nada liga cada losango ao cartão":** no desktop o `mouseover` realça a comarca; o PDF não tem
   hover.
6. **Alvos de 35×35 e 91×35:** o CSS declara 36/44 (`h-9`/`h-11`), e a medição do bloco A deu
   92×36. O 1 px é a caixa pintada, não o alvo. O chip "voltar" em 32 px é decisão.
7. **"Sem legenda" nas págs. 12–15:** a legenda é `display:none` no celular por decisão; o que
   falta ali é o cabeçalho em uma linha, não a legenda.

## Rodada 1 que continua de pé

| Item | Status | Onde |
|---|---|---|
| Mapa idêntico nas págs. 6–9 | **procede** — é o problema 3 | `PainelController.php:150-170` |
| "VILA VELHA" sobre "GUARAPARI" | **procede** — as caixas se sobrepõem em x e y na p. 2 | `app.blade.php`/`ETIQUETAS` |
| Recorte sem disco, nome e clique | **parcial** — o clique existe (cada forma é botão); sem hover não há nome; nomes fixos no recorte são decisão | `references/wireframe-e-mapa.md` |
| Linha de contato com tile + copiar + seta + "Telefone" repetido | **decisão adiada** (B5/B6) | `docs/revisao-claude-wireframe-2026-11-16.md` §B |
| Datas ISO na p. 9 (`2024-03-01`) contra `01/03/2024` na p. 5 | **procede** — `$i['desde']` é ISO cru | `Diretorio.php:425,435`; `reconfirmar.blade.php:35` |
| Alvos 35×35, 31, 91×35 | **não procede como alvo** — mede a caixa pintada | `.topbotao`, `h-9`, `h-11` |
| "Malha municipal: IBGE" em 10 px e baixo contraste | **parcial** — contraste foi a 7,11:1 no bloco A; 10 px fica; sobreposição na lista do celular procede | `app.css:656-663`; `mapa.js:81` |
| Selo "Demonstração" ausente no topo do celular | **decisão** — está na gaveta desde o bloco A | `layouts/app.blade.php` |
| "na versão em produção ela acompanha a conta" (p. 7) | **procede** — texto de desenvolvedor na tela pública | `favoritos.blade.php:8-9` |
| Telefone `(27) 3232. XXXX` com ponto solto | **procede** — divergência do seed; B4 adiado | `docs/...` §B4 |
| Valor sem reticências, linha 2 da busca, "0 comarcas" | **resolvidos** no bloco A | `docs/...` §A |

## Ideias novas, por ganho ÷ custo

1. **Subtração no cabeçalho do celular** — sai o breadcrumb raiz, sai o cabeçalho de coluna, o
   título vira uma linha. ~76 px, de ~5,5 para ~6,9 linhas. 30 min. **Aceitar.**
2. **Município digitado como entrada** — a versão barata (20 min) é o texto do estado negado e a 1ª
   linha quando o termo casa com comarca; a versão completa é o mesmo dado do problema 1.
   **Aceitar a barata junto do problema 1.**
3. **Precisão da posição** — `~` nas linhas dos 18 aproximados e nota quando a precisão do navegador
   passar de 1 km. O círculo já existe; torná-lo legível. 15 min. **Aceitar.**
4. **Recorte "Grande Vitória" vivo** — 4 nomes e discos clicáveis. 1,5 h; 1 dia na v3. **Adiar.**

## Decisões que só você toma

1. **"Perto de mim" passa a responder a comarca?** Recomendo sim, na versão barata: `VOCÊ ESTÁ EM` +
   fóruns da comarca, depois os próximos em linha reta. É o único item que muda a resposta, e a
   malha já está no projeto.
2. **A busca acende o mapa?** Recomendo sim: comarcas com resultado a 100% + anel, as demais a 35%,
   a Sede no rail. Sem pinos e sem zoom. É a tela principal; hoje o mapa é decoração nela.

## Lote A aplicado (17/11/2026)

- **Legenda:** as amostras saem do mesmo `raioDe()`; medido no DOM: **11,44 / 19,91 / 27,80 px**
  (antes 8/13/18 escritos à mão).
- **Busca:** o cabeçalho só conta unidades quando há linhas; `cejusc` → `9 unidades e setores`, e a
  seção "contatos" continua com os 11 que casaram.
- **Celular:** `69 comarcas · 1.807 contatos` em uma linha, 390 px, sem estouro horizontal.
- **Reconfirmar:** `desde` em dd/mm/aaaa (`01/03/2024`), sem ISO na lista.
- **Favoritos:** saiu "na versão em produção ela acompanha a conta".
- **Chip:** só existe com zoom < 10,25 (em 6,75 visível; em 10,5 `display:none`) e não é renderizado
  na tela de unidade.
- **IBGE:** o crédito sobe para `altura da folha + 4 px` no celular; medido: topo 349, base 367,
  folha em 371,4 — sem sobrepor a lista.
- **PDF:** regerado com a mesma ordem e as mesmas 16 páginas.

## Lote B — busca acesa (17/11/2026)

- **De onde vem o destaque:** `PainelController::busca()` monta `destaque` com as chaves das comarcas
  que casaram pelo nome, das unidades/setores (`comarca_chave`, novo em `Diretorio::busca()`) e dos
  contatos (`_comarca`, resolvido por `resolveComarca()` — `uo` → nó → fórum → comarca). A Sede entra
  como `destaque_sede`, sem polígono.
- **Mapa:** comarcas fora do conjunto ficam a 35% (polígono e disco); as acesas mantêm o disco cheio e
  ganham **anel de 2 px** (`radius + 2`, sem preenchimento). Rótulos das apagadas recebem
  `.rotulo-apagado`. Nada de pino, nada de zoom: o `getZoom()` fica em 8,25.
- **Sede:** o rail ganha um ponto ao lado de "Sede"; o mapa não inventa polígono para ela.
- **Medido:** `cejusc` → 10 discos acesos/10 anéis, 59 apagados, ponto na Sede; `3jecivel` → 1/68;
  `sede` → 0 apagados e só o ponto; home → 0 apagados, sem anel. O cabeçalho continua
  `9 unidades e setores`; o destaque tem 10 comarcas porque os 11 contatos também contam como
  ocorrência — o anel segue resultado, não a contagem da lista.
- **PDF:** regerado; a p. 6 mostra os anéis, o resto apagado e o ponto da Sede, no mesmo enquadramento.

## Não conferi

- A geometria em pixel do chip nas págs. 12 e 15 e a distância de 22 px entre os dois pinos: são
  medidas do vetor dele, não repeti.
- Os estados de hover e o toque no pino: o PDF não os tem.
- O telefone `(27) 3232. XXXX`: não abri a linha na p. 8.

## Lote B — perto de mim por comarca (17/11/2026)

- **Onde estou:** `municipioDe()` faz ponto-em-polígono na `camadaGeo` que já está no mapa e cruza o
  `codarea` com `porIbge`; nada de serviço externo. O cartão passa a abrir com `VOCÊ ESTÁ EM` e
  `Vitória · comarca com 2 fóruns`.
- **Ordem:** os fóruns da comarca (até 2) vêm primeiro; depois a seção
  `OUTROS FÓRUNS PRÓXIMOS · EM LINHA RETA` completa as cinco linhas. A distância ganha `~` quando o
  fórum é `aprox` (a linha já dizia "posição aproximada").
- **Estados medidos:**
  - Vitória (-20,3155, -40,3128): 2 fóruns de Vitória, 3 outros, 5 linhas; zoom 12,25 no desktop e
    11 no celular.
  - Município sem comarca (centroide de 3201159): rótulo `SEM COMARCA`, a mensagem "Seu município
    não tem comarca própria…" e 5 fóruns mais próximos; Ibatiba com `~24,1 km`.
  - Fora do ES (-23,5505, -46,6333): `Sua posição está fora do Espírito Santo.`, sem lista e sem
    enquadrar — o zoom fica em 8,25.
  - Permissão negada: `Sem acesso à localização. Digite seu município na busca.` e o foco vai para o
    campo `q`.
- **Dois toques:** no desktop o clique continua abrindo a unidade. No celular, o primeiro toque
  seleciona o pino (`.pino-ativo`, losango maior), abre a dica com nome/comarca e o aviso
  `Toque de novo para abrir`; o segundo abre `/local/FOR0001`. Com o cartão de "perto de mim" aberto,
  o toque abre direto (a lista já desambiguou).
- **PDF:** regerado; a p. 13 (celular) mostra o cartão com a comarca, os dois fóruns e a seção dos
  outros, e a legenda passou a falar em comarca/fóruns.

## Lote B — rótulos sem colisão (17/11/2026)

- **Causa:** os rótulos permanentes eram fixos por lado (`ETIQUETAS`); em 1440 px, `Vila Velha`
  (direção `bottom`) e `Guarapari` (direção `right`) caíam na mesma faixa e a caixa de Guarapari
  ficava 58 × 14 px sob a de Vila Velha.
- **Correção:** `ajustarRotulos()` mede as caixas no DOM e tenta o lado preferido, depois
  `bottom → right → left → top`; se nenhum lado couber, o rótulo sai de cena. A ordem de prioridade
  é Vitória, Vila Velha, Guarapari, Cariacica, Serra e Cachoeiro; a função roda no carregamento, no
  `zoomend` e no `resize` do mapa. `Guarapari` passou a preferir `bottom`; no zoom 7 ele gira para
  `left` sozinho, e no zoom 6 Cachoeiro é o único que sai (é o menos prioritário).
- **Medições:** sem interseção de rótulos em 1024, 1280, 1366, 1440, 1600, 1920 e 2560 px e nos zooms
  6, 6,5, 7, 7,5, 8, 8,25, 9, 10 e 11. No enquadramento inicial de 1440, Guarapari fica em
  `(543, 724)` e Vila Velha em `(587, 685)`: 19 px de folga vertical.
- **PDF e imagens:** regenerados; a p. 2 e as capturas de busca/perto já mostram os dois rótulos
  separados.

## Próximo passo

O **lote A** e o **lote B** estão aplicados e medidos: a busca acende as comarcas com resultado, o
"perto de mim" abre em "VOCÊ ESTÁ EM" com os fóruns da comarca, os estados de sem comarca/fora do
ES/permissão negada estão cobertos, o primeiro toque no pino seleciona antes do segundo abrir e os
rótulos fixos não se sobrepõem mais (Vila Velha/Guarapari inclusive). O PDF foi regerado (16 páginas;
p. 2, 6 e 13 são as que mudaram). O próximo passo é a sua leitura dessas páginas: se elas convencerem, o
ciclo da revisão 2 fecha. A precisão dos 18 pinos aproximados não foi alterada — o protótipo só a
declara; corrigir a coordenada é decisão de dados, não de tela.
