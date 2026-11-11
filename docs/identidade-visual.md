# v3 — Identidade visual e arquitetura de interface (Fase 1)

> **Status: DIREÇÃO ESCOLHIDA (11/11/2026) — implementação fica para o início do v3.**
> O Enzo confirmou a **Direção A — Mapa protagonista** como direção do v3, a ser
> implementada em **Laravel (Blade + TALL)**, e não em Python/JS.
>
> O que se preserva é a **direção** (paleta slate + turquesa, tipografia, mapa
> como porta de entrada, painel flutuante, contato como unidade de leitura),
> não o código de `direcoes-visuais.html`: aquele arquivo é instrumento de
> decisão e **não pode** virar markup do v3 — promovê-lo reproduziria o porte
> que o v3 existe para consertar (o v2 herdou 42% das linhas do
> `ForumNavigationJS` do v1) e queimaria o critério de ≤10% de similaridade.
> Um protótipo descartável em `http.server` é permitido para ver a direção com
> dado real, desde que marcado como descartável e nunca promovido.

> Documento de decisão. Nada aqui é código ainda: é o que o v3 precisa
> satisfazer para ser **evolução** (mesmo produto, mesma modelagem, mesmo arco)
> **e independente** (implementação e interface próprias, nada copiado do órgão).

## Por que esta fase existe

O v2 herdou a interface do MVP: `public.js` tem 42% de linhas idênticas ao
`ForumNavigationJS` do v1 — porte de estrutura, não cópia literal, mas porte.
Por isso o v2 **não** pode ser chamado de independente.

O v3 só pode usar a palavra se a diferença for medível:

- similaridade de linhas contra `public.js` (v2) e `ForumNavigationJS` (v1) **≤ 10%**;
- zero classes `fc-*` reaproveitadas;
- modelo de navegação diferente (não é só recolorir a cascata atual);
- fluxo de telas redesenhado, não herdado.

## Sugestões registradas (boas ideias para aplicar)

1. **Paleta própria** — sair do azul institucional (#2563eb / #1d4ed8) e do
   navy da sidebar. O azul é a cor do órgão; reaproveitá-la mantém a semelhança
   visual mesmo com código novo.
2. **Tipografia** — trocar a pilha atual por uma dupla com personalidade:
   uma grotesk para texto e uma mono para dados (telefone, ramal, e-mail,
   contadores). Números tabulares ajudam a ler tabela de contatos.
3. **Modelo de navegação diferente** — duas alternativas fortes:
   - **mapa em tela cheia com painel flutuante** sobreposto, em vez de duas
     colunas mapa + painel;
   - **lista com filtros no topo** (chips) em vez de cascata de selects
     Comarca → Fórum → Unidade → Setor.
4. **Contato como unidade de leitura**, não o nível hierárquico: a pessoa que
   usa o diretório quer o telefone, não a árvore. A hierarquia vira filtro,
   não caminho obrigatório.

## Direções visuais propostas

### Direção A — Mapa protagonista
- **Ideia:** o mapa é o app. Painel de vidro flutuante por cima, busca no topo.
- **Paleta:** slate profundo (`#0B1220`, `#111C2E`) + accent turquesa (`#2DD4BF`).
- **Tipografia:** grotesk geométrica + mono para valores.
- **Navegação:** clique no município → painel troca de conteúdo no lugar
  (sem trocar de tela); breadcrumb em pílula flutuante; busca global sempre visível.
- **Prós:** visualmente distante do v1/v2; mapa ganha o destaque que merece.
- **Contras:** no celular, painel flutuante exige cuidado (vira bottom sheet).

### Direção B — Command-first (busca e teclado)
- **Ideia:** quem usa quer achar rápido; a tela é uma lista filtrável.
- **Paleta:** branco quente (`#FAFAF8`) + tinta (`#18181B`) + accent violeta (`#7C3AED`).
- **Tipografia:** Inter/similar com tracking apertado + mono nos números.
- **Navegação:** chips de filtro no topo (Comarca, Fórum, Unidade, Tipo),
  lista vertical com contadores tabulares, atalho `⌘K` para busca global;
  mapa vira cartão secundário, menor, à direita.
- **Prós:** o mais distante do original em fluxo; escala bem para 1.800 contatos;
  ótimo para leitor de tela e teclado (público do Enzo).
- **Contras:** perde o impacto visual do mapa como porta de entrada.

### Direção C — Editorial / catálogo
- **Ideia:** o diretório lido como um índice impresso, com hierarquia tipográfica forte.
- **Paleta:** papel (`#F7F4EF`) + tinta (`#141210`) + accent terracota (`#B4532A`).
- **Tipografia:** display serifada nos títulos + sans neutra no corpo + mono nos dados.
- **Navegação:** lista de comarcas numerada (01, 02, 03…), réguas finas,
  detalhe em página própria; mapa como ilustração lateral.
- **Prós:** elegante e memorável; nada parecido com o v1/v2.
- **Contras:** menos "sistema", mais "publicação"; busca precisa de destaque extra.

## O que continua igual (de propósito)
- Modelagem: comarca > fórum > unidade > setor > contato.
- Regras: escopo por nível, pedido com aprovação, histórico, backup.
- Dados fictícios, sem nome, marca ou domínio do órgão.

## Aceite da Fase 1
- [x] Direção escolhida: **A — Mapa protagonista** (11/11/2026), implementação em Laravel.
- [ ] Paleta final com contrastes AA medidos.
- [ ] Dupla tipográfica definida.
- [ ] Wireframe das 4 telas: início/busca, resultado, detalhe do local, contato.
- [ ] Checklist de "o que não pode ser copiado" (classes, markup, fluxo de selects).
