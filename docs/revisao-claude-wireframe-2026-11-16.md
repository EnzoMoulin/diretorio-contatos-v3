# Revisão do wireframe pelo Claude — triagem

**Data:** 16/11/2026 · **Fonte:** resposta do Claude ao PDF `wireframe-v3-telas.pdf` (15 páginas)
**Método:** cada apontamento foi conferido no código do protótipo (`IDEIAS PARA O V3/wireframe`),
arquivo e linha citados. Onde não conferi, está dito.

O que ele fez de diferente e que valeu o envio: mediu contraste e alvo no raster do PDF e leu o
texto vetorial. Duas medidas dele corrigem números que eu tinha passado no briefing — ver §F.

**Status (16/11/2026):** o **bloco A** está aplicado e medido (fim deste documento). Do **bloco B em
diante nada se decide agora** — a v3 está na etapa de direção visual e a arquitetura (Laravel) já está
fechada; essas escolhas entram no começo da v3, quando houver tela para comparar. O destino de cada
bloco está no mapa do fim do documento.

---

## A. Aceitos como ajuste de wireframe (baratos, verificados no código)

| # | Defeito | Onde está | Correção |
|---|---|---|---|
| A1 | **O valor é cortado com reticências** — “editais-civel-vitoria@exemplo.ju…” | `components/contato-linha.blade.php:19` usa `truncate` | Quebra em vez de reticências: `break-words` + `<wbr>@`, como já faz `painel/contato.blade.php:44`. Reticências só na linha de contexto. Mesmo defeito em `painel/reconfirmar.blade.php:29` |
| A2 | Tamanho do valor varia por tela (13/15/14 px) | idem, `text-[0.8125rem]` vs `[0.9375rem]` | Um tamanho por contexto (linha e ficha), não três |
| A3 | Subtítulo da linha (unidade de origem) truncado — “Central de Distribuição … · V…” | `contato-linha.blade.php:20` | Duas linhas quando precisar; nunca reticências no meio de “Vitória/Vila Velha/Viana” |
| A4 | Trilha corta cada migalha: “Vit… › Fórum … › 10ª Secretaria In…” | `layouts/app.blade.php:238-243` (`truncate` em cada passo) | Duas linhas na trilha em vez de encurtar cada rótulo |
| A5 | Cabeçalho da busca imprime zero: “0 comarcas · 84 locais · 68 contatos” | `painel/busca.blade.php:22` | Esconder contagem zerada; “locais” → “unidades e setores” (73 é o total de locais; 84 é o número de nós que casaram) |
| A6 | Contagem sem rótulo nos resultados (“7”, “2”, “1”) | `busca.blade.php:71` e `:93` (`linha-contagem`) | “7 telefones”, “3 contatos” |
| A7 | Placeholder da topbar não cita o que as pessoas buscam | `layouts/app.blade.php:63` “comarca, unidade ou município” | “Buscar comarca, unidade, setor, telefone ou ramal” |
| A8 | **Legenda do mapa com cor literal** — no tema claro as bolhas da legenda ficam `#7CE3D6` e as do mapa `#419691` | `.legenda-item i`, `css/app.css:753` (`rgba(45,212,191,.62)`) | `var(--mapa-disco)` e `var(--mapa-disco-contorno)`. É a mesma classe de defeito já paga uma vez (hex fixo sobrevive à troca de tema) |
| A9 | **Ficha imprime a data em UTC** — saiu 16/11/2026 às 21h de 15/11 | `config/app.php:68` `'timezone' => 'UTC'` | `America/Sao_Paulo` |
| A10 | Ficha não diz o fórum — Vitória tem dois | `painel/local.blade.php` (`ficha-sub`: tipo · comarca · contatos) | Subir “Fórum Cível” quando a unidade tem pai |
| A11 | “FICHA DA UNIDADE · 16/11/2026” se lê como data do dado | idem, `now()->format('d/m/Y')` | “Emitida em 16/11/2026 01:09” — e a data do dado, quando houver, é outra linha |
| A12 | Alvos abaixo da régua: imprimir 83×24, chip “voltar” 31 px, alternador 36×36 | `local.blade.php:18` (`px-2 py-1 text-[0.625rem]`), `.topbotao` (2,25 rem) | Imprimir `h-9` (36) no desktop e `h-11` (44) no celular; alternador 44 no celular. O chip de voltar em 32 px é tamanho decidido — sobe só se você quiser consistência |
| A13 | Contraste abaixo de 4,5:1: crédito “Malha municipal: IBGE” (3,47:1 e 3,65:1), “N canais” (4,0:1), contadores (4,03:1), placeholder no claro (4,02:1) | crédito e `text-muted/80` em `local.blade.php:145` | Clarear os quatro; o crédito é atribuição obrigatória, não pode ser o texto menos legível da tela |
| A14 | **Texto de desenvolvedor na tela pública**: “localStorage”, “no v3”, “protótipo”, “no seed” | `painel/favoritos.blade.php:8-9`, `painel/uteis.blade.php:35` | “Guardados neste navegador”, “Sem documento de origem registrado” |
| A15 | “Sede”, “Sede Administrativa” e “estrutura central” para o mesmo objeto; “canais” e “contatos” contando a mesma coisa | `inicio.blade.php`, `local.blade.php:145` | Um nome só; “canais” → “contatos” |
| A16 | Selo “Demonstração · dados fictícios” ausente no celular | topbar mobile | Levar o selo para a gaveta e o cabeçalho da folha |
| A17 | Sede é a única linha com tile de ícone, justamente o caso excepcional | `inicio.blade.php:18` (`predio`) | Só chevron, como as comarcas |
| A18 | “Telefones úteis” usa `tel:` no desktop, contra a regra “`tel:` só no celular” | `painel/uteis.blade.php:25` | Esconder o link no desktop e manter o copiar, como nas demais linhas |
| A19 | O próximo render precisa de uma unidade **com ramal** — nenhuma das 14 telas mostra o tipo ramal | amostra (`UNI0013` não tem ramal) | Trocar a unidade da captura; o seed tem 444 ramais |

**Tempo estimado do bloco A:** ~1h20, em dois commits (verdade visível, depois linguagem).

---

## B. Precisa de decisão sua

| # | Questão | Meu parecer |
|---|---|---|
| B1 | **Painel de 424 → 520 px a partir da comarca** (ele propõe `--painel` variável: 420 na inicial, 520 depois) | Primeiro A1: com o valor quebrando em duas linhas, o corte deixa de existir e a largura vira conforto, não função. A medição dele confirma a minha: o mapa é limitado pela altura (silhueta ~480 px em ~920 px livres), então 520 **não encolhe** o mapa — só estica as linhas de leitura. Teste de 10 min com as duas capturas depois do A1; minha aposta é manter 424 |
| B2 | **Busca multitermo** | Hoje `Diretorio::busca()` (linha 448) faz `str_contains` do termo inteiro: “1 vara civel vitoria” não acha nada, e “cartorio vitoria” também não, porque a frase não existe. Tokens com AND custa ~10 linhas no protótipo e muda o SQL da v3 (um `ILIKE` por token sobre `busca_texto`). Recomendo aceitar: é o que a pessoa digita de fato |
| B3 | **Ordem das comarcas “parece importância e depois alfabética”** | Não é acidente: são as 7 da Grande Vitória e depois o interior em ordem alfabética, herdadas do catálogo. O problema é não estar declarado. Dois cabeçalhos fixos (“Grande Vitória”, “Interior”) e o cabeçalho de colunas fixo resolvem sem tocar a ordem — que continua estável, que é o motivo de ela existir |
| B4 | **Formato único de telefone na tela** (“(27) 3145 XXXX”, “(27) 3232. XXXX”, “(27) 3145-XXXX”) | A divergência é do seed, e o briefing manda mostrar o valor como está. Normalizar separador **não completa dígito** (o mascaramento continua), então aceitaria: um formato só na tela e no copiar |
| B5 | **Inverter a anatomia da linha de contato** (rótulo antes do valor, como a pág. 7) | Aceito — a pessoa procura “Recepção” e hoje o rótulo está embaixo, em 11 px cinza. É o mesmo defeito que ele descreve como “peso sem informação” |
| B6 | **Tirar o tile de ícone da linha de contato** | Aceito: o tipo já está no formato do valor e no rótulo |
| B7 | **Fila de reconferência por local, com progresso** | Não investir no wireframe: no v3 essa tela é Filament (`docs/plano-v3.md`). Registrado como requisito do painel, não como peça do protótipo |

---

## C. Backlog (ideias dele que valem registro)

1. **Idade do dado na unidade** — “Mais recente: ofício 432/2024 · 01/03/2024 · há 2 anos e 8 meses”, com “Sem documento de origem registrado” para os 1.478. O dado existe (`fonte`/`vigente_desde` em 329 contatos); é a melhoria mais forte da lista porque responde “posso confiar neste número?” sem abrir a ficha.
2. **Copiar a unidade como texto** — nome, endereço e canais, no formato da ficha.
3. **Consultados hoje** — últimos cinco locais em `/favoritos`, que hoje é a tela mais vazia.
4. **Imprimir fórum e comarca**, não só a unidade.
5. **URL por estado do painel** (já é o item 08 do `backlog-ideias.md`).

---

## D. Rejeitados, com motivo

| Sugestão | Por que não |
|---|---|
| Tirar o botão “voltar” da ficha de contato porque a trilha já volta | Decisão registrada: o destino do voltar é resolvido pelo servidor (unidade, fórum, comarca ou `/`) e cobre link direto, favorito e histórico vazio; contato de setor cai em `unidade#setor` e a trilha não expressa isso |
| Tirar a seta e a ficha de contato | A ficha é o último nível da navegação e é onde vive o selo `fonte · desde`; a duplicação com a unidade é o preço de ter URL por contato |
| Reduzir o mapa a 60% na tela inicial | Direção A, e você pediu para aproximar o mapa, não afastar. A silhueta já é limitada pela altura: menos largura não dá mais painel útil |
| Tirar “Gestão” do rail público | É modo no pé do rail, não destino; e a fila de reconferência é a vitrine do lado interno no portfólio |
| “Reescrever o título do resultado da busca como nome da unidade” | É o que ele viu: título = nome do nó (“Cartório”), subtítulo = pai · comarca. A decisão de 15/11 já é essa; o que ele acertou foi o **truncamento** do subtítulo (A3) e a falta de realce no termo que distingue |

---

## E. Onde ele leu errado ou foi impreciso

1. **“Estados ausentes: carregando, falha do mapa, busca sem resultado, favorito preenchido.”** Existem como rota — `/?mapa=falha`, `/?mapa=lento`, `/municipio/{ibge}` e busca vazia estão no protótipo. Faltaram no PDF, que tem 14 telas de 7 estados possíveis.
2. **“Em nenhuma das 14 páginas há linha do tipo Ramal.”** Verdade na amostra, falso no produto: o seed tem 444 ramais; a unidade escolhida (`UNI0013`) não tem nenhum. É falha de amostra (A19), não de cobertura.
3. **“Alternador de tema 35×35.”** Mede 36×36 (`.topbotao`, 2,25 rem). Falha o alvo de 44 no celular; não falha o de 36 no desktop.
4. **Contradição interna:** afirma “a busca tem 416×36” no item 3 e depois “busca 44 (passa)” no item 8.
5. **“Voltar 75×31 abaixo de 36.”** O chip tem 32 px no desktop por decisão; está abaixo da régua de 36, mas é deliberado, não descuido.
6. **“A página fica 70% vazia.”** Unidade pequena usa `ficha-curta` (≤3 blocos e ≤30 contatos) por decisão: uma coluna com corpo maior. O vazio é consequência de não inventar conteúdo para preencher folha.
7. **“0 comarcas · 84 locais”: o zero é real**, mas 84 é a contagem de nós que casaram — a base tem 73 locais. O rótulo é que está errado (A5); a contagem não.

---

## F. Correção no meu briefing (ele mediu certo, eu passei o token)

O prompt dizia painel `#111C2E` e texto secundário 6,53:1. O painel pinta
`color-mix(in srgb, var(--color-surface) 97%, transparent)` (`css/app.css:1149`) sobre o gradiente do
palco, então o fundo renderizado é **~#0B1321** e o secundário mede **7,11:1**. A conclusão não muda
(os tokens passam), mas o número que eu dei descrevia o token, não o que a tela pinta. Corrigir na
skill e no próximo prompt.

---

## G. Não conferido

- **“O mesmo e-mail aparece como contato direto da comarca (pág. 3) e dentro do 3º Juizado (pág. 5)”** — exige consultar o seed; se for verdade, é um erro de dado de verdade e não de tela.
- As medidas em pixel (422 px, 234–270 px, 480 px de silhueta) são dele; conferi a estrutura, não a régua.
- Ordem de grandeza do item “3.800 px de rolagem” para 70 comarcas.

---

## Ordem de execução sugerida

1. **Bloco A** em um commit: valor que quebra, trilha em duas linhas, rótulos da busca, legenda com var, fuso, ficha com fórum, alvos e contrastes. É o que um visitante vê sem clicar.
2. **Bloco A (linguagem)** em outro commit: A14 a A17 — tirar “seed”, “localStorage”, “v3” e “protótipo” da tela pública.
3. **As decisões B** depois do seu aval — B2 (multitermo) é a que muda comportamento, não aparência.
4. Regerar o PDF e comparar antes/depois nas páginas 2, 4, 6 e 15.

---

## Aplicado em 15/11/2026 (bloco A)

A1–A19 aplicados no protótipo. Medições depois da mudança, com o servidor em `127.0.0.1:8123`
(`scrollWidth > clientWidth` para corte, `getBoundingClientRect` para alvo, luminância WCAG para
contraste):

| O que | Antes | Depois |
|---|---|---|
| Valores com reticências | 4 telas | **0** — 42 `.num` na busca, 160 em `/reconfirmar`, 10 em `/local/UNI0013` |
| Tamanho do valor nas listas | 13 / 15 / 14 px | **14 px** em todas |
| Botão imprimir | 83×24 | **92×36** no desktop, 92×44 no celular |
| Alternador de tema | 36×36 | **44×44** no celular (36 no desktop) |
| “Malha municipal: IBGE” | 3,47:1 | **7,11:1** |
| Contadores da busca | 4,03:1, sem rótulo | **7,11:1**, “7 contatos” |
| Legenda no tema claro | `#7CE3D6` literal | **`--mapa-disco` = `#0c7a6f`**, igual ao mapa |
| Ficha | “Ficha da unidade · 16/11/2026” (UTC), sem fórum | “emitida em 15/11/2026 22:37” (local) e “unidade · **Fórum Cível** · Vitória · 10 contatos · 2 setores” |
| Cabeçalho da busca | “0 comarcas · 84 locais · 68 contatos” | “**84 unidades e setores** · 68 contatos” |
| Placeholder da topbar | cortado em “ou ram” com a frase nova | campo de 26 → **30 rem**; folga de 69 px |
| Selo de demonstração no celular | ausente | visível **na gaveta** (a ficha já trazia no pé) |
| Amostra do PDF | unidade sem ramal | ficha de contato agora é **ramal** (`C000008`) |

Duas notas de execução:

- O chip “voltar” ficou em 32 px no desktop, como decidido; subiu só o alternador.
- O rail continua dizendo **“Úteis”**, como decidido em 15/11 — trocar por “Telefones” é decisão
  sua, não ajuste de wireframe. Na gaveta, “Telefones Úteis” virou “Telefones úteis” (caixa).
- Linguagem: “localStorage”, “no v3”, “protótipo” e “no seed” saíram da tela pública.
- Nada foi commitado: `IDEIAS PARA O V3/` está no `.gitignore` (linha 31). O protótipo vive só no disco,
  e o PDF é o artefato que sai daqui.

**Pendente:** os itens **B** (painel 424×520, busca multitermo, cabeçalhos Grande Vitória/Interior,
formato único de telefone, anatomia da linha, tile de ícone, reconferência em Filament).

---

## Onde cada bloco foi anotado para depois

| Bloco | O que é | Onde ficou |
|---|---|---|
| **A** | 19 ajustes de wireframe | **aplicados em 15/11/2026**, medidos na seção acima |
| **B** | 7 decisões de interface | `docs/plano-v3.md` §13, itens **F-5 a F-11** |
| **C** | 5 ideias novas | `docs/backlog-ideias.md`, itens **15 a 18** (a quinta já era o item 08) |
| **D** | 5 sugestões recusadas | só aqui, na tabela da §D — recusa com motivo não vira fila |
| **E** | leituras erradas do revisor | só aqui, na §E |
| **F** | correção do briefing (painel `#0B1321`, 7,11:1) | skill `diretorio-contatos-v3`, seção “Pegadinhas já pagas” |
| **G** | e-mail possivelmente em dois níveis | `docs/plano-v3.md` §13, item **F-12** (conferência de dado) |

Regra que vale para os dois arquivos: **gatilho não aconteceu, não se implementa**. O bloco B só entra
quando a v3 tiver a tela correspondente na frente — decidir largura de painel sem a lista real é garantir
refazer a medição depois.
