# Revisão do wireframe pelo Claude — rodada 3, triagem

**Data:** 17/11/2026 · **Fonte:** `IDEIAS PARA O V3/ramal-revisao-wireframe-3-capturas.md`, resposta
às 12 capturas de 17/11/2026 (`IDEIAS PARA O V3/capturas-rodada-3/`).
**Material enviado sobre:** não registrado no envio — 12 capturas de 17/11/2026 (o rastreio de base
por commit começou em 26/11/2026).
**Método:** cada afirmação foi conferida no protótipo (`IDEIAS PARA O V3/wireframe`, com
`arquivo:linha`) ou contradita por ele. As medições em pixel das imagens **não** foram refeitas; onde
o número é inferência, está dito. A lista "conferido como resolvido" dele não foi reauditada.

## Veredito

A rodada 3 muda de qualidade: mede em pixel, diz onde não mediu e separa *ajuste de wireframe* de
*decisão de produto*. Três achados procedem e custam uma linha cada (crédito do IBGE, chave
`com resultado`, barra de rolagem), dois procedem em parte (o corte da dica existe, mas a config
`interactive` já está resolvida no código; o selo falta no celular, mas a legenda lá não é defeito,
é ausência deliberada) e o item 1 — a regra nova de rótulos — é o único remédio que não se aplica: é
a mesma família de critério já medida e descartada por apagar nome de região, e o passo 4 dele
escreve essa falha como regra.

Duas imprecisões mudam o que fazer: `interactive:true` é no-op, e a hipótese de `--altura-folha`
valendo 0 para explicar o crédito no topo não fecha — zero põe o crédito no rodapé, atrás da folha.

## Os cinco pontos

| # | Afirmação | Evidência | Veredito |
|---|---|---|---|
| 1 | Rótulo × disco: incluir todos os discos como obstáculo, exigir que o disco mais próximo da caixa seja o próprio e sumir com o rótulo quando não houver posição | `ajustarRotulos()` de fato só compara rótulo × rótulo (`mapa.js:332-371`); discos da RMGV a 20–30 px e rótulos de 44–151 px (`capturas-rodada-3/README.md`) | **Diagnóstico procede; remédio não.** Cada critério que usa o disco como obstáculo já foi medido: ou troca o lado definido no `ETIQUETAS` (Serra de `top` para `right`), ou esconde nome de região. O critério dele é mais estrito que os quatro medidos |
| 2 | Folha × pino: `panInside` com o padding medido da folha, `interactive:true`, `Toque de novo` fora | `mapa.js:791` (`direction:'top'`, offset −16); `app.css:541` (`meia` = 56dvh) | **Procede no corte; parcial no remédio.** `panInside` existe (Leaflet 1.9.4), mas `interactive:true` é no-op e `cheia` (68 px de área livre) não cabe com padding nenhum |
| 3 | Selo de demonstração no celular; legenda como botão `ⓘ` | `.selo-demo{display:none}` abaixo de 768 px (`app.css:1350`), visível só na gaveta (`:2084`); `.legenda{display:none}` no mesmo corte (`:741-745`) | **Selo procede; legenda é escopo novo, não defeito** |
| 4 | O que as capturas ainda não provam (7 itens) | `mapa.js:795-801` e `:811-818`; `busca.blade.php:108` | **Parcial: dois já têm resposta no código**, um perde objeto com o item 1, quatro são captura |
| 5 | Cinco defeitos nas imagens | um a um, abaixo | **Os cinco procedem.** O do crédito procede por outro mecanismo |

### 1. Rótulo × disco — o diagnóstico é a parte boa

A causa que ele aponta é literal: `ajustarRotulos()` acumula só as caixas de rótulo em `colocados`
e testa `colidem()` contra elas (`mapa.js:328-371`); nenhum disco entra na conta. E o sintoma que ele
mediu — 5 dos 6 rótulos fixos tocando disco alheio — é o preço conhecido de manter seis nomes em um
aglomerado onde os discos estão a 20–30 px e os rótulos têm 44–151 px.

O que não se aplica é o remédio. Esta família de critério já foi medida nesta máquina: **caixa do
disco, 60% do raio, área coberta e centros cobertos** — cada um ou movia um lado que o `ETIQUETAS`
definia (Serra de `top` para `right`), ou escondia nome de região no enquadramento de estado (8,25).
O critério 3 do Claude ("o disco mais próximo da caixa é o próprio") é mais estrito que os quatro: com
um rótulo de 151 px entre discos de 25 px, o vizinho quase sempre fica mais perto da caixa que o dono,
e o passo 4 manda o rótulo sair de cena. No desktop isso apaga os nomes da Grande Vitória — o desktop
é quem decide o produto.

O que sobra dessa família é largura de texto: encurtar `CACHOEIRO DE ITAPEMIRIM`, esconder quem cobrir
≥ 2 discos, ou halo. E a alternativa que o próprio Enzo levanta — **sem rótulo fixo nenhum** —, que é a
única que elimina a classe do defeito em vez de administrá-la (ver §Decisões).

### 2. Folha × pino — o corte procede; falta o caso `cheia`

O corte é real e já estava registrado: a dica nasce ancorada no pino (`direction:'top'`, offset −16 —
`mapa.js:791`), a folha em `meia` começa em 371 px e o pino da captura 08 está em y≈663, atrás dela.
`mapa.panInside()` existe na 1.9.4 e resolve.

Dois ajustes do ramal não se aplicam:

- **`interactive:true` é no-op.** A dica já é clicável: `.leaflet-tooltip.pino-dica` tem
  `pointer-events:auto` e `cursor:pointer` (`app.css, linhas 2198 a 2214`), e `tooltipopen` liga `dica.onclick`
  (`mapa.js:795-801`). Pino e dica já abrem; se a captura não mostra, é caso a reproduzir, não config
  faltando.
- **`cheia` ficou de fora.** Com a folha em `cheia` (92dvh) a área livre é de ~68 px e nenhuma
  combinação de `paddingTopLeft`/`paddingBottomRight` faz caber uma dica de 66 px. Nesse estado a
  saída é não abrir a dica (ou recolher a folha), e isso é decisão de produto.

Efeito colateral que ele não pesou: no primeiro toque o mapa passa a se mover sob o dedo. É o preço do
`panInside`, e vale porque o primeiro toque já é o de seleção.

### 3. Legenda e selo no celular — o selo procede, a legenda não é defeito

- **Selo:** procede. `.selo-demo` é `display:none` abaixo de 768 px (`app.css:1350`) e só reaparece
  dentro da gaveta (`:2084`); a barra do celular de fato tem a folga que ele mediu. Um selo de duas
  linhas, 10 px, não interativo, não pede 44 px.
- **Legenda:** não é defeito — `.legenda{display:none}` abaixo de 768 px é decisão (`app.css:741-745`)
  e a captura 05 se chama, por isso, `05-celular-home-sem-legenda-selo.png`. O botão `ⓘ` é escopo novo.
  Se ele entrar, tem um detalhe técnico: as amostras de tamanho saem de `raioBase`
  (`mapa.js:211-221`), **sem** o fator 0,74 que o celular aplica nos discos (`:212`) — as três
  amostras mediriam 26% a mais do que os discos da própria tela. O botão exigiria `raioDe` no lugar de
  `raioBase`, mais um estado de abertura/fechamento.
- **Chave `com resultado`:** procede e é o achado mais fino do ramal. `.legenda-anel` troca o
  preenchimento por uma borda de 2 px (`app.css, linhas 2465 a 2470`), mas largura e altura só são escritas para
  `[data-legenda-disco]` (`mapa.js:216-221`): o `i` do anel fica 0×0 e a borda rende ~4 px de ponto, em
  vez do anel de 14 px em volta de um disco teal.

### 4. O que as capturas não provam — dois dos sete têm resposta no código

- **Alvo do 2º toque:** respondido. O pino navega (`mapa.js:811-818`) e a dica também
  (`:795-801`; o `onclick` é ligado a cada `tooltipopen`). Não é pergunta aberta, é captura faltando.
- **Destino do chevron em `Brejetuba`:** respondido. A linha linka `route('municipio', $m['ibge'])`
  (`busca.blade.php:108`), que para município sem comarca é a tela `/municipio/{ibge}` — a mesma que já
  desenha o contorno tracejado do município (`mapa.js:448`) e o rótulo do nome (`:544-556`).
- **Rótulos depois da correção do item 1:** perde objeto se o item 1 não for aplicado como ele propõe.
- **Hover e foco, timeout, `meia` com pino embaixo, fim da busca `cejusc`, tema claro:** captura mesmo —
  e hover/foco são capturáveis por CDP (`Input.dispatchMouseEvent` antes do `captureScreenshot`), o que
  transforma "não aparece em captura estática" em item de checklist. Ver
  `capturas-rodada-3/README.md` §Checklist de captura.

### 5. Outros defeitos — os cinco procedem, um com outra causa

1. **Crédito do IBGE.** (a) Procede: no celular a regra muda só o `bottom` (`app.css:681-684`) e a
   âncora continua à direita — o mesmo canto do chip, cujo `bottom` sai da mesma altura de folha
   (`mapa.js:930-937`). (b) Procede o sintoma, **não** a hipótese: `--altura-folha` é a altura medida
   do `.painel` (`mapa.js:934-937`), então zero levaria o crédito para y≈836 (atrás da folha), não para
   o topo. Para o crédito cair em y≈50 o deslocamento tem de ser ≈780 px, que é `92dvh` + 4 — o valor
   de `cheia`. Ou seja: a captura 08 herdou a altura de outro estado, provavelmente pelo atraso de
   380 ms de `posicionar()` depois de `folha:mudou` (`mapa.js:1250`; transição de 340 ms em
   `app.css:535`). **Inferência** — não remedi no render; no 08 o chip não denuncia o mesmo atraso
   porque em zoom 13 ele está escondido (`mapa.js:918`).
2. **Busca por município hachurado que não acende o mapa.** Procede: `PainelController` só põe chave de
   comarca em `destaque` (`:76-90`) e a rota de busca passa `focoIbge => null` (`:188`). Mas o desenho
   **já existe**: a rota de município passa `focoIbge` (`:124`) e a malha pinta o município focado com
   contorno de 2 px tracejado (`mapa.js:448`). O ajuste é passar o `ibge` do município casado como
   `foco_ibge` na página de busca, em vez de inventar um anel solto no centróide — anel sem disco
   dentro é um símbolo novo, e o mapa já tem gramática para acender.
3. **Dois números lado a lado.** Procede: `:35` conta os achados da busca, `:94` o total da comarca
   (`busca.blade.php`). `84 contatos na comarca` resolve.
4. **Marcadores numerados redondos.** Procede: `.pino-num{border-radius:999px}` (`app.css:2139`) contra
   o losango de `.pino-alvo span` (`:2108`). E há um degrau a mais: o pino numerado **não carrega
   `pino-aprox`** (`mapa.js:987`), então "os 18 aproximados, vazados" não sai só trocando a forma — o
   `aprox` da lista precisa chegar ao `divIcon`.
5. **Barra de rolagem nativa no cartão do "perto".** Procede: `.mapa-perto-lista{overflow:auto}` sem a
   classe `.rolagem` (`app.css, linhas 2370 a 2378`); a barra fina existe só na classe (`:57-74`) e é usada no
   `.painel-corpo` (`app.blade.php:311`).

## Decisões que só você toma

O ramal escreve "*decisão:*" nos itens 1 e 3 — são propostas dele, não decisões suas; voltam como
pergunta.

1. **Rótulos fixos do mapa** (a pergunta que você levantou): **remover todos** — recomendação —, o que
   elimina a classe do defeito, tira ~80 linhas de `mapa.js` (`ETIQUETAS`, `ajustarRotulos`,
   `colisoes`) e deixa o nome por conta da dica do disco no hover, do recorte e da lista do rail;
   × **atacar a largura do texto** (encurtar `CACHOEIRO` e esconder quem cobrir ≥ 2 discos), mantendo
   os seis nomes onde couberem; × aceitar a sobreposição atual.
   Consequências da remoção, para você pesar: no celular não há hover, então os três rótulos de hoje
   (`Vitória`, `Cariacica`, `Vila Velha` — `mapa.js:509`) sairiam e o mapa ficaria nomeado só pelo chip
   e pela folha; e a §6.1 do `plano-v3.md` cita "rótulos" entre as quatro coisas que fazem o mapa ser
   sistema — o que sobra (legenda, disco de densidade, dica no hover, estados) sustenta a linha, mas a
   frase do plano fica desatualizada se a decisão for remover.
2. **Celular — o que fazer com o selo e a legenda.** Contexto: hoje o telefone não mostra nem um nem
   outro (`.selo-demo` e `.legenda` são `display:none` abaixo de 768 px; o selo só aparece na gaveta).
   O ramal propõe os dois. **Só o selo** — recomendação: custo de minutos, não interativo, e a tela
   passa a dizer que o dado é fictício; × **selo + botão `ⓘ` da legenda** (44 px + cartão + fechar
   fora + amostras recalculadas com o fator 0,74, ~1 h); × nenhum dos dois.

## Decisões tomadas (17/11/2026)

- **Rótulos fixos: removidos.** `ETIQUETAS`, `PRIORIDADE_ROTULOS`, `rotulos`, `ajustarRotulos()` e
  `colisoes` saíram de `mapa.js`; o rótulo do município focado (`/municipio/{ibge}`) saiu junto, porque
  era o último rótulo fixo — a tela continua identificada pelo painel e pelo contorno tracejado de 2 px.
  Medido no render: 0 dicas fixas na home em 1440×900 e em 390×844, 69 discos, hover do disco devolve
  `Vitória 166`, `/municipio/3201159` com 1 contorno forte (`6 4`, 2 px) e 0 rótulos, console sem erro.
- **Disco no celular com dois toques**, a gramática dos pinos: o 1º abre a dica (`Cariacica` + `137`), o
  2º navega (`/comarca/MUN0004`), toque no mapa fecha. Exigiu `bubblingMouseEvents: false` no
  `circleMarker`: `Path` borbulha para o mapa por padrão e o `click` do mapa fechava a dica no mesmo toque.
- **Celular: só o selo.** `DEMONSTRAÇÃO · DADOS FICTÍCIOS` entra na barra em duas linhas de 9,5 px
  (104×31 px, antes do botão de tema), sem tocar na marca (que termina em x=172; o selo começa em 180) e
  sem rolagem horizontal (`scrollWidth − innerWidth = 0`). O selo da gaveta saiu, para não repetir.
- **Legenda no celular: adiada.** O botão `ⓘ` não entra agora; quando entrar, as amostras têm de usar
  `raioDe` (fator 0,74), não `raioBase`.

## Lote aplicado (18/11/2026)

Sete correções, todas medidas no render (Playwright, 1440×900 e 390×844, `pageerror` ligado):

| Correção | Medida depois |
|---|---|
| Crédito do IBGE à esquerda, ancorado em `html[data-folha]` | recolhida 730–748 (folha 752), meia 349–367 (folha 371), cheia 52–67 (folha 68); sem sobrepor o chip; `scrollWidth − innerWidth = 0` |
| `foco_ibge` na busca por município sem comarca | polígono 41×78 px com tracejado `6 4` a 2 px e **sem zoom** (na tela do município, 276×527) |
| Anel da legenda com tamanho | `<i>` 14×14, borda 2 px, fundo teal |
| `84 contatos na comarca` | linha de 394 px, `scrollWidth = clientWidth` |
| Losango numerado + `aprox` vazado | caixa de 31 px, número com `rotate(-45deg)`, 1 dos 5 pinos vazado |
| `.rolagem` + degradê no cartão do "perto" | `scrollbar-width: thin`, `mask-image` de 16 px, lista rolando |
| `panInside` no 1º toque, com o caso `cheia` tratado | meia: dica em 223–289, folha em 371; cheia: seleciona sem dica |

Dois achados que só aparecem no render e que mudaram a implementação: `Path` borbulha cliques
(`bubblingMouseEvents: false` no disco) e o navegador emite `mouseover`/`mouseout` logo depois do
`click` no toque, então o Leaflet fechava a dica no mesmo gesto — enquanto o elemento está
selecionado, a dica é religada como `permanent` e volta a ser hover quando a seleção termina.

Capturas desta rodada: `capturas-rodada-4/` (9 imagens + README com a tabela de medidas). PDF
regerado: `wireframe-v3-telas.pdf`, 16 páginas.

## Próximo passo

Com o lote fechado, o que sobra é produto: legenda no celular (`ⓘ`, com amostras em `raioDe`), o caso
"pino colado na borda inferior com folha `meia`" — o único do `panInside` que ainda não foi provado — e
a decisão sobre `cheia` (hoje o toque seleciona sem dica). A próxima revisão deve partir das capturas
de `capturas-rodada-4/` e do PDF regerado (`wireframe-v3-telas.pdf`, 16 páginas).
