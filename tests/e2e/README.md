# Fumaça e2e — diretório de contatos

Um comando, sete verificações, saída em JSON. É a herdeira das sondas que ficaram soltas em
`IDEIAS PARA O V3/capturas-rodada-8/` e `capturas-rodada-9/`: o que provou correção de bug
virou teste, com o número no lugar do adjetivo.

```bash
# alvo: o wireframe Laravel de "IDEIAS PARA O V3/wireframe" (pasta fora do repositório)
cd "IDEIAS PARA O V3/wireframe" && php artisan serve --port=8123      # volte ao repo depois
BASE_URL=http://127.0.0.1:8123 node tests/e2e/fumaca.js          # legível
BASE_URL=http://127.0.0.1:8123 node tests/e2e/fumaca.js --json   # uma linha de JSON
```

**Não roda no CI, e é deliberado:** o alvo é o protótipo, que vive em `.gitignore` — num clone
limpo não há página para medir. O arquivo está versionado para não se perder com a pasta do
protótipo e para virar o e2e da v3.0, quando o alvo passar a ser a aplicação (só o `BASE_URL`
muda). Quem rodar precisa do protótipo em disco e do Playwright.

Sai com `0` se os sete passarem, `1` se algum falhar (o JSON traz `resultados`, `detalhes` e
`falhas`). Depende de Playwright com Chromium; sem `node_modules` local, aponte o `NODE_PATH`
para uma instalação existente:

```bash
NODE_PATH=~/Emprego/Projetos/diretorio-contatos-v2/tests/fumaca/node_modules \
  node tests/e2e/fumaca.js
```

## O que cobre

| Caso | O que mede | Rodada |
|---|---|---|
| `1-controles` | a dica do pino não cruza a união dos controles do canto (celular 390 × 844, pinos a 364 px e y de 220 a 340) | 9, item 1 |
| `2-caso-i` | caixa = dica ∪ alvo: com o pino a 9 px do topo da folha `meia`, o alvo de 44 px sobe para 8 px acima dela | 9, item 2 |
| `3-desktop` | F2 e canto superior direito em 1440 × 900: dica dentro da área segura e vista parada no hover | 9, item 3 |
| `4-chip` | duas contagens: uma linha com `·` acima de 640 px, duas linhas sem `·` abaixo | 9, item 4 |
| `5-pinos` | todo alvo de pino mede 44 × 44 e nenhum par sobreposto deixa pino sem saída | 9, item 6 |
| `6-foco` | clique de mouse não pinta `:focus-visible` no polígono; `focus()` sem mouse pinta | 9, item 7 |
| `7-teclado` | 30 Tabs não param em `<path>` e nenhum path do mapa tem `tabindex >= 0` | 9, estados 6–7 |

Cada caso abre as páginas que precisa e fecha; a ordem não importa. Nenhum caso usa arrasto:
o pino é levado ao ponto por `__mapa.panBy`, então o `_moved` do Leaflet não entra na conta.

## O que fica para os testes do v3.0

- **Safari/iOS** (estado 5 da varredura de 20/11): não há aparelho neste host; o teste real é
  no WebKit do Playwright, que ainda não está instalado.
- **Teclado no Firefox** (estado 6): aqui roda Chromium. O teste de Firefox é o mesmo roteiro,
  outro browser.
- **Estados 2 e 4** da varredura continuam sem prova: no zoom do ES nenhum polígono hachurado
  aceita toque (o halo de 22 px do disco vence) e não há água a menos de 21 px do disco de
  Vitória nesta largura. Ver `capturas-rodada-9/README.md`.
- **Hover em todos os cantos e nas duas larguras**: o e2e cobre dois casos (rail e canto
  superior direito); a varredura completa está em `sonda-f2.js` (16 casos).
- **Cartão “perto de mim” aberto**: a união dos controles com o cartão não foi medida.
- **Impressão da ficha A4** (`UNI0013`): página 16 do PDF, com Type 3 no texto — ver
  `LEIA-ME-pdf-dividido.md`.
