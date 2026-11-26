# Acessibilidade — gate WCAG 2.1 AA

Registro de 25/11/2026. Fecha a ideia **12** do `backlog-ideias.md` e a issue **#9**:
acessibilidade deixa de ser relatório e vira régua executável no CI. A régua nasceu no v2
(`tests/fumaca/acessibilidade.mjs`); aqui ela é menor, mas é **gate**: qualquer violação
sai com código 1 e o PR não mergeia.

## O que o gate mede

| Verificação | Como | Onde |
| :--- | :--- | :--- |
| Regras A/AA do axe-core | `@axe-core/playwright` com as tags `wcag2a`, `wcag2aa`, `wcag21a`, `wcag21aa` | `/` e `/ramais` |
| Contraste AA | a regra `color-contrast` do axe, nas preferências **clara e escura** | idem |
| Nome acessível | regras do axe (`link-name`, `button-name`, `label`, `aria-*`) e `label-content-name-mismatch` | idem |
| Foco por teclado | Tab de verdade (até 80 paradas), comparando o estilo do elemento **e do pai** com o repouso — pega `:focus-within` | idem |
| Alvo de toque ≥ 44 px | viewport de 390 × 844; links no meio de texto (dentro de `p`, `li`, `td`) ficam de fora, porque são texto, não botão | idem |
| Mapa com alternativa textual | `role="img"` + nome acessível, ou `aria-describedby`/texto equivalente | armado; nenhuma tela tem mapa hoje |

O axe roda com **duas preferências de cor**. A v3 ainda não tem CSS escuro; quando tiver, o
gate já mede a preferência escura sem mudar uma linha. As regras de *best practice* ficam
fora de propósito: o gate é AA, não um placar de estilo.

## O que ele já corrigiu

A primeira execução reprovou quatro coisas reais, todas corrigidas antes do gate fechar:

- **contraste do dourado**: `#b98a3c` sobre `#f6f3ec` dava **2,8:1** (mínimo 4,5:1 para
  texto normal). O texto pequeno passou a usar `--dourado-escuro: #7d5a22` (**5,64:1**); o
  dourado claro ficou só em filete decorativo;
- **nome acessível do link da marca**: `aria-label="Ramal — voltar à capa"` não continha o
  texto visível ("Ramal Diretório de contatos"). O `aria-label` saiu e a dica "voltar à
  capa" virou texto `sr-apenas` dentro do próprio link;
- **`aria-label` proibido no "—" de setor vazio**: virou `aria-hidden` + "sem setor" em
  `sr-apenas`, que é o que o leitor de tela precisa ouvir;
- **alvo de toque do botão Imprimir**: 40 px de altura → **44 px** (mínimo AA de toque).

## Como rodar

Com a v3 no ar (o servidor do README, porta 8000):

```bash
cd tests/acessibilidade
npm ci
node acessibilidade.mjs http://127.0.0.1:8000 relatorio
```

O segundo argumento é a pasta do relatório (padrão `relatorio/`). `PW_HEADED=1` mostra a
janela. O JSON traz, por página e por tema, as violações com seletor e resumo, as paradas de
Tab e os alvos pequenos.

`npm ci` e `npx playwright install chromium` são os únicos pré-requisitos; o resto é Node 22.

## No CI

Job `Acessibilidade (WCAG 2.1 AA)` no `laravel.yml`, em todo push para `main` e todo PR:

1. sobe Postgres 16, migrate + seed (o mesmo caminho do README);
2. sobe `php artisan serve` em `127.0.0.1:8000` e espera `/up`;
3. instala o harness e o Chromium;
4. roda `node acessibilidade.mjs`;
5. anexa `relatorio/` como artefato **mesmo em falha**, com retenção de 14 dias.

O job é **required status check** do ruleset `protege-default`: sem ele verde, o botão de
merge não aparece. A violação plantada de propósito (um `aria-label` que não contém o texto
visível) reprova com `label-content-name-mismatch` nos dois temas — foi assim que a régua
foi provada antes de entrar no ruleset.

## Limites

- O axe cobre parte dos critérios (a estimativa usual é 30–40%); o gate não substitui
  revisão manual nem teste com leitor de tela. Ele impede regressão barata, que é o que
  mais acontece.
- A tela de mapa ainda não existe; a regra de alternativa textual está armada e passa a
  valer no primeiro mapa.
- O gate mede as telas públicas (`/` e `/ramais`). Tela nova entra em `PAGINAS`, no topo do
  `acessibilidade.mjs`, e no mesmo commit que a cria.
