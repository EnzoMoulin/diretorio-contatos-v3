# V3: interface na direção A (mapa protagonista). Decisão e briefing para o Hermes

Decisão do usuário em 11/11/2026, depois de comparar as três direções do arquivo `direcoes-visuais.html` (A mapa protagonista, B command-first, C editorial): **A**, com mapa em Leaflet **sem camada de tiles** e geometria municipal do ES **versionada** no repositório. Este documento é autossuficiente: quem executa não precisa do histórico da conversa. Revisado em 12/11/2026: os números do seed foram reconferidos no HEAD a93aeb1 do v2.

## O que se quer
Refazer o mockup da direção A como arquivos estáticos, com o mapa real do Espírito Santo, o layout de celular e os estados que o mockup atual não tem. O mockup é especificação de design para a v3 (Laravel + Blade + Livewire + Alpine + Tailwind). **Não é código a ser aproveitado**: a implementação em Laravel será escrita do zero.

Ponto de partida do design: `direcoes-visuais.html` (direção A). Se o arquivo não estiver acessível, parar e pedir ao usuário; não reconstruir de memória.

## Entrega
Arquivos soltos, fora do repositório do v2 (público; não colocar nada da v3 lá):
- `mapa-a.html` (um arquivo, CSS e JS inline, exceto o Leaflet);
- `es-municipios.geojson` (geometria simplificada);
- `NOTAS.md` com o que a seção "O que reportar" pede;
- capturas em 1280×800 e 390×844.

## Restrições do projeto (valem sobre tudo)
- Nenhuma marca ou nome institucional na interface, no seed, nas capturas nem no `NOTAS.md` (nenhuma sigla do tribunal, da fundação de previdência ou do laboratório de inovação). Dados fictícios: e-mails `@exemplo.jus.br`, nomes inventados.
- Não copiar código de `public.js`, `admin.js` nem `mapa.js` do v1 ou do v2. O `mapa.js` do v2 se declara espelho do mapa original. Escrever o componente do mapa a partir da documentação do Leaflet.
- Não usar: classes `fc-*`, azul institucional (#2563eb, #1d4ed8), sidebar navy com marca fixa, cascata obrigatória de selects Comarca → Fórum → Unidade → Setor.
- Sem tiles (nem OpenStreetMap nem outro). Sem requisição a terceiros em tempo de execução, exceto o Leaflet com versão fixa (1.9.4) no mockup; na v3 ele entra por npm. Nada de GeoJSON baixado de repositório alheio na branch `master`.
  - O que o v2 faz, e a v3 não repete (conferido em `frontend/mapa.js` e `public.html`, 12/11): busca `geojs-32-mun.json` de `tbrugz/geodata-br` (branch `master`, via `raw.githubusercontent.com`), usa tiles de `tile.openstreetmap.org`, carrega o Leaflet de `unpkg.com` e casa polígono e comarca pelo **nome** (`properties.name`). Na v3: arquivo versionado, sem tiles, casamento por código IBGE.
- Não afirmar "acessível" nem "WCAG conforme": reportar só os contrastes medidos.
- Não alterar histórico do git de nenhum repositório.

## Dados
- Fonte: `seed_mascarado.json` do v2. Reconferido em 12/11/2026 (HEAD a93aeb1): 1.807 contatos; 70 entradas em `comarcas`, das quais 69 são comarcas e 1 é a **Sede Administrativa** (`tipo` ORGAO, `ibge` vazio, 286 contatos). As 69 comarcas somam 1.521 contatos. Exemplos conferidos: Vitória 166, Vila Velha 92, Cariacica 137, Serra 59, Guarapari 84.
- Casar polígono e comarca pelo **código IBGE**: campo `ibge` no seed (`codigoIbge` na API do v2). São 69 códigos de 7 dígitos, únicos, todos começando em 32 (UF do ES). Não casar por nome.
- Reportar toda comarca sem polígono e todo polígono sem comarca. O ES tem 78 municípios (conhecimento geral, conferir contra a malha): o esperado é 78 polígonos, 69 casados e 9 sem comarca própria. Número diferente disso entra na lista.
- Dados fictícios no seed: todo e-mail é `@exemplo.jus.br`; telefones (1.019) e WhatsApp (47) terminam em `XXXX`; os 444 ramais vêm como `XXXX` inteiro. A interface mostra o valor como está, sem completar nada.
- Texto do chip: não somar a Sede às comarcas sem dizer. Sugestão: "69 comarcas e a estrutura central · 1.807 contatos".

## Geometria
- Fonte candidata: malha municipal do IBGE para o ES (UF 32). Confirmar formato disponível, licença e a atribuição exigida; registrar a fonte, a data e a URL no `NOTAS.md`. Se não for possível baixar do IBGE, dizer isso; não trocar por outra fonte sem avisar.
- Simplificar (ex.: mapshaper) mantendo o contorno reconhecível e as ilhas da Grande Vitória. Registrar a ferramenta e a tolerância. Meta sugerida: abaixo de ~150 KB (palpite, não medido); reportar o tamanho real.
- Incluir a legenda de atribuição no rodapé do mapa.

## Comportamento do mapa
- Leaflet sem tiles, fundo escuro da direção A, polígonos em teal. Zoom e arraste por toque e mouse.
- Vitória, Vila Velha, Cariacica e Serra são as maiores em contatos e as menores no mapa. Escolher uma comarca (pelo polígono ou pela lista) enquadra o polígono com folga para o painel: padding à direita no desktop, abaixo no celular.
- Clique no polígono e clique na lista produzem o mesmo estado. Passar o mouse na lista destaca o polígono, e o inverso.
- Polígonos não são acessíveis por teclado por padrão: a lista de comarcas é o caminho por teclado. Foco visível nos itens da lista.

## Painel e navegação
- Busca no topo do painel, sempre visível; é o caminho principal. O mapa é o modo de navegar.
- Entrada fixa "Estrutura central" no topo da lista (a Sede não tem polígono), com a contagem dela.
- Drill-down no mesmo painel, sem trocar de tela, com breadcrumb: comarca → fórum → unidade → setor → contato. Nem todo contato passa por unidade e setor: 631 dos 1.807 não têm unidade no seed (ficam direto no fórum ou na comarca), então o breadcrumb precisa aceitar um contato logo abaixo do fórum ou da comarca.
- Telefones e WhatsApp com os 4 últimos dígitos ocultos (`XXXX`), como no seed; ramais aparecem como `XXXX`.

## Estados a desenhar
1. Inicial (mapa inteiro, lista de comarcas).
2. Comarca selecionada (fóruns e unidades).
3. Contato aberto (nível mais fundo).
4. Município sem comarca própria: mensagem "Sem contatos cadastrados". O seed não diz qual comarca o atende; não inventar.
5. Busca sem resultado.
6. Carregando.
7. Falha ao carregar o mapa: a lista continua utilizável, com aviso.

## Celular (390×844)
- Painel vira folha inferior com três alturas (recolhida com busca e chip, meia, cheia). Alvos de toque de pelo menos 44 px. Sem rolagem horizontal. Os controles de zoom do Leaflet não podem ficar sob a folha.

## Visual
- Paleta da direção A: #0B1220, #111C2E, #2DD4BF, #5EEAD4, #EAFBFF.
- O mockup original cita "Grotesk" sem carregar fonte nenhuma. Escolher uma grotesca de licença aberta (ex.: Space Grotesk, Schibsted Grotesk; conferir a licença) hospedada localmente (Fontsource), sem Google Fonts. `mono` para números.
- Contraste: o rótulo "COMARCAS" do original (10 px, #5d7a8f) mede 3,78:1 sobre #111C2E e 4,14:1 sobre #0B1220 (cálculo WCAG feito em 12/11; o fundo exato do painel no mockup precisa ser medido). Abaixo de 4,5:1 nos dois, que é o mínimo para texto pequeno. Clarear. Medir todos os pares texto/fundo e reportar. **Recalculado em 14/11/2026: `#8CA3B6` mede 7,16:1 sobre `#0B1220` e 6,53:1 sobre `#111C2E` e é o candidato para este token** (o mais discreto que ainda passa seria `#748692`, 4,96 / 4,53, sem margem). Accent `#2DD4BF` (10,06 / 9,18) e texto `#EAFBFF` (17,60 / 16,06) não mudam.

## Critérios de aceite
1. As 69 comarcas têm polígono e a Sede tem entrada própria, ou a divergência está listada (com a contagem esperada de 78 polígonos e 9 sem comarca, ou a diferença explicada).
2. Os sete estados existem e dá para chegar a cada um.
3. Capturas em 1280×800 e 390×844 de pelo menos os estados 1, 2 e 3.
4. Nenhuma classe `fc-*`, nenhuma cor proibida, nenhuma requisição externa além do Leaflet 1.9.4.
5. Tamanho do GeoJSON e do HTML reportados.

## O que reportar (no `NOTAS.md`)
Fonte, licença e atribuição da geometria; ferramenta e tolerância da simplificação; tamanhos; comarcas sem polígono e polígonos sem comarca; tabela de contrastes medidos; qualquer ponto em que não deu para cumprir este briefing, dito sem suavizar.
