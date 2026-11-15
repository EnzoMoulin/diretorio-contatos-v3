# Plano do v3 — arquitetura e direção visual

**Data:** 14/11/2026 · **Status:** base para o início da implementação do v3
**Substitui, onde houver conflito:** `docs/fase-3-arquitetura-e-adrs.md`, `docs/arquitetura-v3.md`,
`docs/v3-interface.md` e `docs/v3-arquitetura-preparacao.md`.
**Documento irmão:** `db/schema.sql` continua sendo a fonte do DDL; o ADR-010 (Anexo A) entra em
`docs/adr/`.

> Nota de escopo: este documento é de trabalho, com números medidos e comparações francas. A parte
> publicável daqui é o ADR-010 e o quadro técnico da seção 3. Nada aqui cita instituição, marca real,
> pessoa ou domínio institucional — e o produto também não pode citar.

---

## 1. Veredito executivo

| Pergunta | Resposta |
|---|---|
| Laravel sozinho ou Laravel + FastAPI? | **Laravel sozinho.** Confirmado com evidência (seção 3.1 e Anexo A). |
| A direção visual A continua? | **Sim.** Paleta e geometria fechadas; falta só acertar tokens de apoio e contraste. |
| Qual material visual é melhor? | **O wireframe navegável** é o melhor *sistema*; **as imagens do Claude** são o melhor *acabamento*. O v3 usa os dois, corrigidos (seção 6). |
| O nome "Ramal" serve? | **Confirmado em 14/11.** Com uma ressalva registrada: a palavra nomeia apps de softphone (SINCorp Ramal, Conectel Ramal), categoria vizinha mas não a mesma. Alternativas que sobreviveram à checagem: Prumo, Fio. |
| Os PDFs servem de base? | **Não literalmente.** A tese (Laravel/TALL, um serviço) está certa; a lista de pacotes e versões está defasada e propõe três coisas que o projeto não deve ter (seção 4). |
| O que começa o v3? | O marco `v3.0` da seção 9, na ordem ali, começando por esqueleto + migrações. |

---

## 2. O que existe hoje (ponto de partida real)

| Peça | Estado | Onde |
|---|---|---|
| Contrato de rotas, modelo e testes do v2 | Pronto, congelado | `~/Emprego/Projetos/diretorio-contatos-v2` (HEAD `a93aeb1`) |
| Seed mascarado | 1.807 contatos, 70 comarcas (69 com IBGE + Sede), 72 fóruns, 838 nós, 11 úteis | `seed_mascarado.json` |
| DDL PostgreSQL | 9 tabelas, extensões, funções e invariantes já escritas | `db/schema.sql` |
| Wireframe navegável | 7 estados, desktop e celular, dado real | `IDEIAS PARA O V3/wireframe/` (Laravel/TALL, descartável) |
| Mockups de referência | 1 direção visual + 3 telas | `IDEIAS PARA O V3/*.jpg, *.png` |
| Proposta escrita | arquitetura + direção visual do Claude | `IDEIAS PARA O V3/v3-proposta-para-hermes.md` |

Consequência prática: **a arquitetura não parte de zero**. O DDL já resolve boa parte do que os PDFs
chamam de "a definir", e o wireframe já provou os estados que as imagens estáticas não mostram.

---

## 3. Arquitetura decidida

### 3.1 Um aplicativo, dois módulos

Monólito modular em Laravel, com dois namespaces que não se importam mutuamente:

- `Directory` — comarcas, locais (fórum e Sede), nós (unidade/setor), contatos, telefones úteis, histórico.
- `Access` — usuários, escopo por nó, pedidos de acesso, notificações.

Regras:
1. `Directory` não depende de `Access`; `Access` referencia o nó pelo `codigo` lógico, nunca pelo `id`.
2. Interface (Livewire), admin (Filament) e API de leitura chamam **as mesmas classes de serviço**.
   Regra de negócio em componente de interface é dívida, não atalho.
3. `codigo` (`MUN0001`, `FOR0001`, `UNI0001`, `C000001`) é a chave lógica imutável do demo; `bigint` é
   detalhe interno. Ids de execução do v2 (`M1`, `F3`, `U12`) não entram.
4. Sem serviço Python. A API `/api/v1` é só de leitura e vive no mesmo processo.

**Por que sem FastAPI** (evidência, não preferência): não existe candidato de extração medido — as três
funções previstas (limpeza de texto, deduplicação, busca fonética) rodam em PostgreSQL (`unaccent`,
`pg_trgm`, `similarity`) sobre 1.807 linhas; duplicar regra de escopo e serialização em duas linguagens
criaria dois conjuntos de teste; e cada serviço extra no plano free do Render é outra partida a frio.

### 3.2 Versões (verificadas no Packagist em 14/11/2026)

| Pacote | Última estável | Exige PHP | Observação |
|---|---|---|---|
| `laravel/framework` | **13.42.0** (10/11) | `^8.3` | Laravel 12 já saiu da janela de correções |
| `livewire/livewire` | **4.4.8** (21/10) | `^8.1` | — |
| `filament/filament` | **5.11.3** (13/11) | `^8.2` | v5 é a linha corrente, não experimental |
| `pestphp/pest` | **5.3.6** (14/11) | `^8.4` | — |
| `spatie/laravel-activitylog` | 5.1.5 | `^8.4` | ver 3.4 (recomendo não usar) |
| `spatie/laravel-permission` | 8.5.0 | `^8.3` | ver 3.4 |

**PHP 8.4** é o piso real (Pest e activitylog exigem `^8.4`), não 8.3. Fixar `8.4` na imagem Docker e
declarar no `composer.json`. Tailwind 4 e Vite 7 vêm do scaffold do Filament 5 — confirmar no momento
de criar o esqueleto, não antes.

Os PDFs citam **Filament v3** e a proposta cita "v4 ou v5": a linha corrente é a **v5**, e não há plugin
listado neste plano que a bloqueie (porque os plugins que a bloqueariam saíram do escopo).

### 3.3 Pacotes: o que entra e o que sai

| Pacote | Decisão | Motivo (curto) |
|---|---|---|
| Laravel, Livewire, Alpine, Tailwind, Filament, Pest | **entra** | núcleo |
| Leaflet 1.9.4 (npm) | **entra** | mapa sem tiles |
| `spatie/laravel-permission` | **adia** | o v2 tem escopo **por nó com herança**, não papel global; isso é Policy própria (ver 3.4) |
| `bezhansalleh/filament-shield` | **sai** | gera permissão por recurso; a regra é por linha |
| `spatie/laravel-activitylog` | **sai** | haveria **dois** históricos (o do `schema.sql` e o do pacote); fica um só |
| Laravel Scout | **sai** | o driver `database` não dá tolerância a erro de digitação; `pg_trgm` resolve |
| `matanyadaev/laravel-eloquent-spatial` / PostGIS | **sai** | mapa é GeoJSON estático casado por IBGE; não há consulta espacial |
| `staudenmeir/laravel-adjacency-list` | **sai** | profundidade fixa (local → nó → contato); CTE recursiva não compensa |
| TallStackUI / MaryUI / WireUI | **sai** | a UI pública é própria; o admin é Filament |
| `spatie/laravel-backup`, `maatwebsite/excel` | **adia** | disco efêmero e scheduler no free; fora do `v3.0` |

### 3.4 Regras e dados que vêm do v2

- **Escopo por nó com herança** (`_escopo`, `_cadeia`, `_pais`, `_cobre` no `app.py`): vira Policy
  (`DirectoryPolicy`) + enum de papel (`ADMIN`, `GESTOR_COMARCA`, `EDITOR`). Papel decide *se entra no
  painel*; o escopo decide *quais linhas vê*. Duas perguntas diferentes, duas ferramentas diferentes.
- **Procedência entra no `v3.0`.** `fonte` e `vigente_desde` já existem na tabela `contatos` do v2 e
  **não existem no `seed_mascarado.json`** (o v2 as extrai da observação livre e grava no SQLite). O
  seed do v3 é exportado do banco do v2 (somente leitura) **trazendo os valores** — no wireframe deu
  329 contatos com documento: 43 do ofício 432/2024 e 286 do catálogo da Sede. O selo `fonte · desde`
  aparece na tela do contato — a parte que interessa ao visitante. A fila "Reconferir" (do documento mais
  antigo para o mais novo) é **tela de gestão interna**, não da consulta pública.
  Contato sem documento não ganha selo: a ausência de data nunca vira "atual".
- **631 contatos não têm `uo`**: o modelo e a tela aceitam contato pendurado direto no local.
- **`desc` não é rótulo de canal.** Medido: 843 vazios, "Geral" 93, "Institucional" 87, "Secretaria de
  Gestão do Foro" 47, "Contadoria" 34, "Oficiais de Justiça" 31, "Cartório" 30. São setores/descrições,
  não "Balcão"/"Chefia". **A interface mostra o tipo (Telefone, Ramal, WhatsApp, E-mail) e a `desc`
  quando existir; nunca inventa rótulo.**
- **Endereço**: 72/72 fóruns têm; 1/70 comarcas (a Sede); 18/838 nós. Logo "Copiar endereço" é um
  recurso **do local**, não de cada setor, com fallback para a comarca quando o local não tiver.
- **Contagens de canal**: 1.019 telefones, 444 ramais, 297 e-mails, 47 WhatsApp.
- **O que não portar**: FK declarada e não aplicada, `PUT` que não atualiza `atualizado_em`, estado de
  sessão em memória, HTML embutido no resultado de busca (devolver trechos/offsets).

### 3.5 Busca

`pg_trgm` + `unaccent` direto na consulta, sem Scout. O `db/schema.sql` já resolve o problema que
costuma travar isso: `unaccent()` é `STABLE`, então não serve em coluna gerada nem em índice; o arquivo
já define `norm_texto()` como invólucro `IMMUTABLE`. Regra: **a mesma função** no índice e na consulta,
senão o planejador ignora o índice. Ordenar por `similarity()` com piso, e devolver trecho + offsets
(nunca HTML).

### 3.6 Histórico e auditoria

Um único histórico (`historico` no `schema.sql`), escrito por observers/ações, com autor, data, antes e
depois. Filament não ganha pacote de auditoria paralelo. O que o visitante vê é a trilha desse histórico;
o que o admin vê é a mesma trilha com diff.

### 3.7 Deploy

Um serviço Docker no Render (free) + Postgres no Neon. Fila `sync` no demo. `/healthz` **sem tocar no
banco** (se o banco cair, o serviço não pode ser reiniciado em cascata). Seed idempotente na subida.
Leitura pública; escrita só autenticada.

---

## 4. Revisão do material recebido (o que fica, o que corrige, o que sai)

### 4.1 Os dois PDFs

| Afirmação | Veredito | Evidência |
|---|---|---|
| "Monólito Laravel + TALL, sem FastAPI" | ✅ | seção 3 e Anexo A |
| "Filament v3" | ❌ | a linha corrente é v5.11.3 |
| "Laravel Scout (driver database) para Ctrl+K tolerante a erro" | ❌ | o driver `database` não faz tolerância; `pg_trgm` faz |
| "CartoDB Dark Matter / Positron para o mapa" | ❌ | são tiles externos: dependência de terceiros, requisição externa e contraste fora do controle |
| "Eloquent Spatial + PostGIS" | ❌ | não há consulta espacial; o mapa é figura, não cálculo |
| "`staudenmeir/adjacency-list`" | ❌ | profundidade fixa e rasa |
| "Filament Shield" | ❌ | permissão por recurso ≠ escopo por linha |
| "TallStackUI" | ❌ | não é usado pela UI pública nem pelo admin |
| "Spatie permission + Shield para Admin/Gestor/Editor" | ⚠ | os papéis existem, mas a regra que importa é o escopo por nó; pacote só entra se sobrar necessidade |
| "Diretório Institucional (PJES)", "Poder Judiciário do ES", `@tjes.jus.br` | ❌ **bloqueante** | viola a regra de nenhuma marca institucional; precisa ser caçado em mockups, seed, capturas e código |
| "Backup automático no Render free" | ❌ | disco efêmero; backup não sobrevive ao restart |
| "Lista densa de contatos em linhas" | ✅ | é o que o wireframe já faz; adotado |
| "Painel expande a 60–70%" | ❌ | painel largo cobre o mapa e piora a leitura; largura fixa ~496 px |

### 4.2 A proposta escrita (`.md`)

Boa no geral: separa decisão de opinião, mede contraste, lista pacotes com veredito e pede verificação.
Onde ela erra ou exagera:

| Ponto | Veredito | Ajuste |
|---|---|---|
| "Filament v5 existe só para suportar Livewire 4, sem mudança funcional" | ⚠ | irrelevante para a decisão; v5 é a linha corrente e é ela que se usa |
| "Contraste do texto secundário `#8BA3B5` = 6,51/7,14" | ✅ | reconferi: 6,51 / 7,14. Meu token atual `#8CA3B6` dá 6,53 / 7,16 — equivalentes; **fica um só** (`#8CA3B6`, já documentado) |
| "Borda `#55708A` 3,31 · contorno `#5E7794` 3,13" | ✅ | reconferi os dois |
| "Favoritos só localStorage no demo" | ⚠ | aceitável, mas é escopo; se entrar, precisa de estado vazio e de remoção — decidir no `v3.0` |
| "Recorte ampliado Grande Vitória é opcional" | ✅ | entra, mas como **controle de navegação** (clique reenquadra o mapa), não como segundo mapa |
| "Chip de contagem 69 comarcas e estrutura central" | ✅ | evita a soma enganosa de 70 comarcas |
| "Mockup estático HTML" como entrega | ⚠ | já existe wireframe **navegável** em Laravel; o mockup estático seria regressão. O que falta é refinar o wireframe, não refazê-lo |
| "Certificar 7 estados" | ✅ | o wireframe já tem os 7; falta o estado "carregando" e a hachura de "sem comarca" |
| "Não afirmar acessível/WCAG" | ✅ | só contrastes medidos, e é assim que fica |

---

## 5. Sistema visual unificado

### 5.1 Princípios

1. O mapa é a entrada; o painel é onde se trabalha. Nenhum dos dois cobre o outro.
2. Estado nunca só por cor: selecionado = preenchimento + contorno claro; sem comarca = hachura + texto.
3. Número em mono tabular; rótulo em grotesca. Nada de número em fonte de texto.
4. Brilho só no polígono selecionado, discreto. Painel e cartões sem neon.
5. Movimento ≤ 150 ms e respeito a `prefers-reduced-motion`.
6. Toda tela declara o que é: selo "Demonstração · dados fictícios" sempre visível.
7. O que o dado não diz, a tela não diz.

### 5.2 Tokens

```css
:root, [data-theme="dark"] {
  --bg: #0B1220;            --surface: #111C2E;      --text: #EAFBFF;
  --accent: #2DD4BF;        --accent-hover: #5EEAD4; --on-accent: #0B1220;
  --text-muted: #8CA3B6;    --control-border: #55708A; --rule: #1E2D44;
  --map-fill: #1B2A3F;      --map-stroke: #5E7794;  --chip-bg: #16314A;
  --whatsapp: #34D399;      --danger: #FF8A80;
}
```

Contrastes medidos nesta data (fórmula WCAG, fundo `#0B1220`, painel `#111C2E`):

| Par | Razão | Veredito |
|---|---|---|
| Texto / fundo · / painel | 17,60 · 16,06 | passa |
| Accent `#2DD4BF` / fundo · / painel | 10,06 · 9,18 | passa |
| `#5EEAD4` / painel | 11,55 | passa |
| Tinta `#0B1220` sobre accent | 10,06 | passa |
| Texto secundário `#8CA3B6` / painel · / fundo | 6,53 · 7,16 | passa |
| Borda `#55708A` / painel | 3,31 | passa (componente ≥ 3) |
| Contorno mapa `#5E7794` / `#1B2A3F` | 3,13 | passa |
| Accent / `#1B2A3F` (selecionado) | 7,78 | passa |
| Texto / chip `#16314A` | 12,56 | passa |
| WhatsApp `#34D399` / painel | 8,89 | passa |
| Erro `#FF8A80` / painel | 7,48 | passa |
| Área do mapa / fundo | 1,29 | decorativo (a forma é do contorno) |
| Chip / painel | 1,28 | decorativo (a forma é da borda) |

Foco: contorno de 2 px em `--accent` com 2 px de afastamento. Alvo de toque: ≥ 44 px.

### 5.3 Geometria de desktop (1408×768 e 1280×800)

```
┌──────────────────────────────────────────────────────────────────────┐
│ topbar 56px: marca · busca central · tema · [Gestão]                 │
├────┬─────────────────────────────────────────────────────────────────┤
│rail│                                                                 │
│64  │                    MAPA (tela cheia, sem tiles)                │
│px  │   ES centralizado no espaço livre; selecionado em teal          │
│    │                                                                 │
│    │                             ┌─────────────────────────────────┐ │
│    │                             │ painel 496px ≥94% opaco         │ │
│    │  ┌──────────────┐           │ chips de migalha removíveis     │ │
│    │  │ Grande Vitória│          │ título + N setores              │ │
│    │  │ (inset 224px) │          │ endereço + copiar               │ │
│    │  └──────────────┘           │ cartões de setor e canais       │ │
│    │                             └─────────────────────────────────┘ │
└────┴─────────────────────────────────────────────────────────────────┘
```

- **Topbar** sempre visível: marca "Ramal" (wordmark + símbolo de um traço que se bifurca), busca
  centralizada (atalho `Ctrl+K`, nunca o único caminho), alternador de tema, botão "Gestão" só para
  autenticado, selo de demonstração à direita. Regra: **controle sem efeito não entra** — o alternador
  de tema só aparece quando o tema claro existir e estiver medido (ver 5.8).
- **Rail único**, 64 px recolhido / 240 px expandido *por cima* do mapa (não empurra): Comarcas, Sede,
  Telefones úteis, Configurações. "Favoritos" só se entrar no `v3.0` — e uma vez.
- **Painel** com largura fixa (clamp 460–540, padrão 496), opacidade ≥ 94%, borda de 1 px em
  `rgba(94,234,212,.14)`, cantos 12 px. Sem desfoque forte.
- **Inset "Grande Vitória"** no canto inferior esquerdo, 224 px, só quando o zoom é baixo e a viewport é
  ≥ 1280. Clicar nele **reencuadra o mapa principal** — não é um segundo estado de seleção.

### 5.4 Celular (390×844)

**Plataforma.** O produto é pensado e desenvolvido **primeiro para computador** — a consulta no balcão
e na secretaria é feita em tela grande, com mapa e lista lado a lado. O celular é **adaptação
responsiva**, para ampliar os modos de acesso: no telefone o ganho é o toque — ligar com um clique no
canal de telefone e abrir o WhatsApp do número — e não um layout próprio. Consequência prática: o
celular não define decisões de produto (densidade do mapa, quantidade de rótulos, prioridade das
colunas); ele herda a mesma informação em uma coluna. Quando as duas exigências brigarem, vence o
desktop.

- Mapa no topo, folha inferior de três alturas (recolhida / meia / cheia), alças de 44 px.
- Recolhida mostra busca + chip de contagem; meia mostra lista/painel; cheia mostra canais completos.
- Sem rolagem horizontal em nenhum estado; zoom do Leaflet fora da folha.
- Inset desligado; rótulos só nas três comarcas mais densas; discos com 74% do raio.

### 5.5 Componentes

| Componente | Regra |
|---|---|
| Linha de canal | ícone · tipo · valor em mono · botão copiar. No desktop em colunas alinhadas; no celular quebra em pílulas com `flex-wrap`. Nunca truncar valor. |
| Migalha | chip removível (Comarca › Local); remover volta um nível sem recarregar a tela. Setor não é nível |
| Selo de procedência | `fonte · desde mês/ano`, só quando declarado; ausência de data não vira "atual" |
| Seção de setor | cabeçalho clicável (nome, tipo e contagem) que recolhe e reabre as linhas de canal na própria página da unidade; âncora `#UNI0027`. Sem tela própria (5.9) |
| Voltar do contato | a tela de contato é a última: chip "voltar" no topo, com o destino no `title` e no `aria-label`. O destino segue **quem é o dono do contato**: unidade (`/local/{unidade}#{setor}`, quando é setor), fórum (`/local/{fórum}`) ou comarca (`/comarca/{chave}`; a Sede vira "estrutura central"). Link, não `history.back()`: precisa funcionar em link direto, favorito e histórico vazio. No celular, 44 px |
| Toast | "Copiado" após cópia, 2 s, `aria-live="polite"` |
| Navegação no celular | o rail (64 px de ícones) não cabe em 390 px e desaparece; no lugar entra um botão de menu de 44 px no topo que abre uma gaveta com os mesmos quatro destinos, **com rótulo escrito** e 44 px por item. Fixa no desktop, sob demanda no celular |
| Ação de canal | **copiar sempre**. Telefone/ramal: `tel:` no **celular apenas** — no desktop não há aparelho que disque, e um botão que não faz nada é ruído. WhatsApp: `wa.me`, que abre o app no celular e o WhatsApp Web no desktop, **só com número completo** (no demo o número vem mascarado e o botão aparece desabilitado). E-mail: `mailto:` |
| Compartilhar | botão em toda ficha de contato: usa `navigator.share` onde existe e, sem ele, copia o link canônico do contato com aviso "Link do contato copiado". O que se compartilha é a **URL do contato**, não o texto da tela: quem recebe cai na ficha certa |
| Endereço | o endereço do local (18 dos 838 nós) é link para o Google Maps: `https://www.google.com/maps/search/?api=1&query=` + endereço e CEP urlencoded, em nova aba, com sublinhado sólido discreto e ícone de saída. Vale na ficha do contato e no cartão da unidade |
| Sidebar | ordem fixa: **Comarcas · Sede · Úteis · Favoritos**. Sede é `MUN0000`, não é comarca e por isso tem item próprio. **Reconferir e Ajustes não entram na consulta pública**: são gestão interna, atrás do botão `Gestão` (no v3, o painel Filament) |
| Gestão | botão no topo direito abre a gestão interna. No protótipo só a fila **Reconferir** existe; `Ajustes` aparece desabilitado, com o destino escrito ("no painel do v3") |
| Zoom do mapa | 16 px à direita da sidebar no desktop — o mapa é full-bleed por baixo dela e o controle do Leaflet nasce em `left:0`, atrás do rail. No celular fica no canto superior direito |
| Telefones Úteis | cada número é `tel:`; o botão de copiar continua ao lado. A lista não entra no mapa nem no índice de favoritos |
| Alternador de tema | escuro é o padrão; o controle só aparece quando o tema claro estiver medido |
| Favorito | `localStorage` no demo; a tabela `favoritos` (com `usuario_id`) já nasce nas migrações |
| Estado vazio | diz o que falta e por quê, com o código IBGE à vista quando for o caso |

**Ordem das listas.** Comarca aparece na ordem original do catálogo (a ordem em que foi levantado,
Sede primeiro), não por contagem de contatos nem em ordem alfabética: a posição estável é o que deixa
o corredor achar "Vitória" onde ele sempre esteve. Contagem ordena disco no mapa, não lista.

### 5.6 Mapa

- Leaflet sem tiles; fundo `#081120` com grade discreta (já no wireframe).
- 78 polígonos da malha municipal do IBGE (UF 32), 69 casados por `ibge`, 9 sem comarca.
- **Discos de densidade** por comarca (área ∝ contatos) por cima do polígono: resolvem o alvo de toque em
  Vitória, Vila Velha, Cariacica e Serra, que são polígonos minúsculos. A lista continua sendo o caminho
  por teclado e a alternativa sem mouse.
- **Hachura** para município sem comarca (padrão SVG injetado no `defs` do próprio SVG do Leaflet — sem
  plugin novo), mais rótulo em texto. Cor sozinha não comunica.
- Etiquetas: desktop nas 6 comarcas mais densas, com lado definido à mão para não colidir; celular nas 3.
- Hover na lista acende o polígono; clique e lista levam ao mesmo estado.

### 5.7 Admin (Filament)

Adota a tela de gestão das referências, com quatro correções:

1. Listagem com filtros por comarca/local/tipo **antes** do formulário; a tela de edição é a segunda.
2. Excluir com confirmação (nome do que se apaga), nunca só ícone.
3. Salvar fixo no rodapé em formulários longos, além do topo.
4. Nada de nome real, badge inventado ou histórico fictício: autor é "demonstração", e a trilha sai do
   histórico real do seed.

Grupos de navegação: Estrutura (comarcas, locais, nós), Contatos (canais, úteis), Controle (histórico,
usuários, papéis), e "Voltar para a consulta".

### 5.8 Tema e favoritos

**Tema.** Escuro é o padrão e não muda (decisão de 11/11). O claro é derivado dos mesmos tokens com as
mesmas medições — não é "inverter as cores". Regras:

1. O par `prefers-color-scheme` **não** decide sozinho: o padrão do produto é escuro mesmo em sistema claro.
2. A escolha do usuário ganha do padrão e persiste em `localStorage` (`ramal:tema`).
3. O controle só existe se o tema claro existir. Botão que não faz nada é pior que botão ausente.
4. O claro entra no `v3.0` (item 5) porque a topbar já nasce com o controle; medir os pares claros é
   parte do trabalho do componente, não um projeto à parte.

**Favoritos.** Híbrido, nesta ordem:

1. Visitante e demo: `localStorage` (`ramal:favoritos`), sem conta, sem escrita no banco. É o que faz o
   recurso existir no demo somente leitura. A lista guarda só o **código**; o nome, o tipo, a contagem e
   o destino vêm de um **índice do servidor** que cobre comarca, fórum e nó (979 entradas hoje, 155 KB
   crus / 21 KB gzip). O índice manda no nome: nome guardado no navegador fica velho depois de uma
   edição. No v3 isso vira um endpoint por código, não o índice inteiro.
2. Pessoal autenticado: sincroniza com a tabela `favoritos` (`usuario_id`, `codigo` do nó, criado_em).
   A tabela nasce nas migrações do `v3.0`; a sincronização entra depois.
3. Ao entrar, o que estava no `localStorage` é oferecido para importar — nunca mesclado em silêncio.
4. Favorito é sempre de **nó** (local, unidade ou setor), nunca de contato: contato muda, nó permanece.

### 5.9 A unidade é o último nível (15/11, já no protótipo)

O setor **não tem tela**. A navegação termina na unidade, que mostra todos os seus setores e os
contatos de cada um na mesma página. Motivo medido no seed: 202 unidades têm filhos, com **mediana de
1,5 setor** por unidade e **mediana de 1 contato** por nó — clicar num setor quase sempre abre uma
tela com uma linha.

1. A página do local lista, nesta ordem: contatos pendurados direto no local (631 no seed) e depois os
   setores na ordem `ordem`, cada um como **seção com âncora** (`#UNI0027`).
2. Setor sem contato continua visível, com "sem canal cadastrado": ausência é informação e mostra onde
   falta levantar dado.
3. Unidade com mais de 8 seções abre com os **três primeiros** grupos e **um** controle "ver todos" —
   nunca um clique por setor. O caso extremo é "Desembargadores", com 30 gabinetes (23 unidades passam
   de 6 seções).
4. Busca, favorito e link direto apontam para `/local/{unidade}#{setor}`; a seção chega destacada e a
   página rola até ela. O payload da busca leva o `pai` do nó (já no protótipo, para o subtítulo).
5. A rota `/local/{setor}` não serve tela: responde **302** para `/local/{unidade}#{setor}`, então link antigo, favorito e resultado de busca antigo continuam caindo no lugar certo. Na migalha, o setor é texto, não nível.
6. **Todo cabeçalho de setor recolhe e reabre o próprio conteúdo** (15/11). O padrão é aberto: quem só
   quer ler não clica; o clique serve para compactar uma unidade grande, não como porta de entrada.
   O cabeçalho é alvo de toque inteiro (44 px, medido no celular), com `aria-expanded` e seta que gira.
   Recolher não esconde o setor nem a contagem — esconde só as linhas de contato.
7. Endereço e "copiar" são do local: só 18 dos 838 nós têm endereço próprio.

---

## 6. Comparação: wireframe × imagens de referência

### 6.1 O que cada família faz melhor

| Critério | Wireframe navegável | Imagens de referência |
|---|---|---|
| Modelo de navegação | mapa + lista + drill-down no mesmo painel | mesma ideia, mas com 3 blocos competindo (rail, sidebar, painel) |
| Mapa como sistema | rótulos, legenda, disco de densidade, estado de falha | mapa como fundo decorativo; sem rótulo, legenda ou zoom |
| Densidade real do dado | 1.807 contatos, Vitória 166, Cariacica 137 | contagens inventadas e exemplos plausíveis |
| Celular | folha de três alturas, alvos de 44 px | previsto no texto, não desenhado |
| Estados | 7, incluindo mapa fora do ar e município sem comarca | só o estado "selecionado" |
| Acabamento de produto | cru: sem topbar, sem marca | topbar, marca, busca, tema, "Gestão", toast |
| Painel | coluna simples, legível | cartões ricos, endereço, `+N canais`, favoritar/histórico |
| Administração | não existia | tela Filament completa |

### 6.2 O que entra no v3, de cada um

| Elemento | Origem | Decisão |
|---|---|---|
| Mapa protagonista, sem tiles, malha IBGE | wireframe | **entra** |
| Discos de densidade + legenda | wireframe | **entra** (com toggle) |
| Rótulos de comarca com lado manual | wireframe | **entra** |
| Folha inferior de três alturas | wireframe | **entra** |
| Estado "município sem comarca" + nota de ausência | wireframe | **entra**, com hachura |
| Estado de falha do mapa com lista utilizável | wireframe | **entra** |
| Tokens medidos, fontes OFL locais | wireframe | **entra** |
| Topbar com marca, busca central, tema, Gestão, selo | referências | **entra** |
| Rail único com rótulos | referências | **entra** (descartada a variante de rail duplo) |
| Migalhas em chips removíveis | referências | **entra** |
| Endereço do local + copiar | referências | **entra** (só onde há dado: 72/72 fóruns) |
| Linhas de canal com tipo + valor + copiar | as duas | **entra**, unificando: colunas no desktop, pílulas no celular |
| Toast "Copiado" | referências | **entra** |
| Inset "Grande Vitória" | referências | **entra** como navegação |
| Painel ≥94% opaco, largura fixa | referências + proposta | **entra** |
| Tela de gestão Filament | referências | **entra**, com as correções da 5.7 |
| Sidebar contextual ao lado do rail | referências | **sai** (duplica atalhos e espreme o mapa) |
| Glassmorphism/desfoque forte | referências | **sai** (legibilidade) |
| Rótulos "Balcão"/"Chefia" | referências | **sai** (não existem no dado) |
| Domínio/marca institucional, nome de pessoa, badge "3" | referências | **sai** (regra de identidade e de dado) |
| Favoritar/Histórico por setor | referências | **adia** (escopo, não visual) |

### 6.3 Veredito

O wireframe é o melhor **produto**; as imagens são o melhor **acabamento**. Nenhum dos dois serve
inteiro: o wireframe sem topbar e sem painel rico parece protótipo; as imagens sem estados, sem rótulos
e com dados inventados parecem apresentação. O que vale é a fusão da tabela 6.2 — e é ela que o `v3.0`
implementa.

---

## 7. Conteúdo e semântica de dado

| Situação | Regra de tela |
|---|---|
| Comarca com polígono | seleção por preenchimento + contorno |
| Município sem comarca | hachura + "sem comarca declarada" + código IBGE; nunca dizer qual comarca atende |
| Sede | entrada fixa na lista, sem polígono, marcada como estrutura central |
| Contato sem nó | aparece no local, não some |
| Contato sem data de vigência | sem selo; nunca "atualizado hoje" |
| Endereço ausente | linha não aparece (não vira "—") |
| `desc` preenchida | mostrada como descrição do canal; sem inventar "Balcão" |
| Contagem total | "69 comarcas e a estrutura central · 1.807 contatos" (nunca somar 70) |

---

## 8. Acessibilidade, desempenho e limites

- Só contrastes medidos (seção 5.2). Não afirmar "acessível" nem "WCAG AA" como selo.
- Navegação por teclado: lista e busca são caminho completo, sem depender do mapa.
- `prefers-reduced-motion` respeitado; transições ≤ 150 ms; sem parallax.
- Orçamento: primeira carga do mapa ≤ 250 KB (GeoJSON atual 154 KB), sem requisição externa nenhuma.
- Limite conhecido: sem tiles não há busca por endereço geográfico nem zoom de rua — o mapa é de
  comarcas, não de logradouros. Está dito na interface? Não; fica no README e no ADR.

---

## 9. Roteiro

### `v3.0` — o marco que fecha

| # | Entrega | Critério de pronto |
|---|---|---|
| 1 | Esqueleto Laravel 13 + PHP 8.4, Docker, Pint, Pest, CI (lint + testes) | `pest` verde no CI |
| 2 | Migrações do `db/schema.sql` (FKs e CHECK reais) + carga do seed do v2 (com `fonte`/`vigente_desde`) | seed idempotente, contagens conferidas |
| 3 | Policies de escopo por nó + testes portados da tabela do v2 | casos do `test_regras` cobertos |
| 4 | API `/api/v1` só leitura + testes de contrato | rotas do contrato respondendo |
| 5 | Consulta pública (Livewire): topbar, rail, mapa, painel, 7 estados, celular, tema escuro e claro | estados 1–7 navegáveis com dado real; os dois temas medidos |
| 6 | Admin Filament: CRUD, pedidos, aprovação, histórico | editar contato muda o histórico |
| 7 | Deploy Render + Neon, README com quadro de estado, ADR-010 | URL viva e `/healthz` sem banco |

Depois do `v3.0`: exportação CSV (fila `sync`), favoritos com conta (sincronização do `localStorage`),
notificações.

### Ordem de trabalho nas primeiras sessões

1. Esqueleto + migrações + seed (itens 1–2): é onde o plano pode falhar rápido.
2. Policies e API (itens 3–4): fecha o contrato antes de desenhar tela.
3. UI pública (item 5): só então o wireframe vira implementação.
4. Admin e deploy (itens 6–7).

---

## 10. Riscos

| Risco | Efeito | Mitigação |
|---|---|---|
| Versões novas (Laravel 13, Filament 5) com plugin incompatível | retrabalho no item 1 | nenhum plugin fora do núcleo; travar versões no `composer.lock` |
| Seed sem `fonte`/`vigente_desde` | procedência some do demo | exportar do banco do v2, não do JSON |
| Mapa sem tiles não é mapa de rua | expectativa errada do visitante | texto curto na interface e no README |
| Partida a frio (Render + Neon) | 30–60 s no primeiro acesso | estado "carregando" desenhado; nada de keep-alive |
| Nome "Ramal" é palavra usada por softphones | confusão de categoria | nome e wordmark são nossos; INPI antes do item 5; alternativas registradas (Prumo, Fio) |
| Material de referência com marca institucional | problema de identidade e de imagem | varredura no seed, nas capturas e no código antes do deploy |
| Escopo crescente (favoritos, exportação, notificações) | `v3.0` não fecha | eles estão fora do marco, de propósito |

---

## 11. Decisões confirmadas em 14 e 15/11

| # | Pergunta | Decisão | Efeito no plano |
|---|---|---|---|
| 1 | Nome | **Ramal**, com a ressalva de colisão em softphones e as alternativas registradas | topbar e wordmark seguem "Ramal"; checagem de INPI antes do item 5 |
| 2 | Procedência no `v3.0` | **entra** (confirmado em 15/11): selo no contato e fila "Reconferir" | item 2 exporta `fonte`/`vigente_desde`; item 5 desenha o selo e a fila |
| 3 | Favoritos | **híbrido**: `localStorage` no demo, tabela `favoritos` já migrada, sincronização depois | seção 5.8 |
| 4 | Tema | **escuro é o padrão**, claro com alternador de verdade | item 5 mede os dois temas |

Ainda em aberto (nada disso bloqueia o começo):

1. **INPI e domínio do nome** — checar antes do item 5. Só DNS e presença de categoria foram verificados.
2. **Nome do repositório público** — `diretorio-contatos-v3` (série) ou `ramal` (produto). Recomendação
   na resposta de 15/11: manter a série no repositório e usar "Ramal" como nome do produto.

## 12. Decisões adiadas para o início do v3 (15/11)

O plano fecha o que basta para começar — itens 1, 2 e 5 têm decisão e critério de pronto. O que segue
fica **deliberadamente aberto** e só se decide quando o v3 começar de fato: a prioridade agora é o
deploy do v2.

| # | Pendência | Por que não agora | Encaminhamento já pensado |
|---|---|---|---|
| D-1 | **Login do admin (Filament)**: e-mail/senha, OAuth ou usuário semeado; e o que o visitante vê ao clicar em "Gestão" | trava o item 6, não os itens 1–2 | e-mail/senha do Filament com dois papéis: `demo` (vê tudo, não escreve; credenciais no README) e `admin` (senha só no painel do Render) |
| D-2 | **Gate de marca institucional**: teste que falhe ao achar domínio, sigla do tribunal ou nome de pessoa | é execução, não decisão de arquitetura | Pest (ou `ramal:auditar`) varrendo seed, `resources/views`, `README` e `docs/` |
| D-3 | **Contrato do seed**: script versionado que exporta do `contatos.db` do v2 e confere números | depende do v2 estar congelado depois do deploy | 1.807 contatos · 69 comarcas + estrutura central · 444 ramais · 329 com procedência (43 + 286) · 78 municípios · 9 sem comarca |
| D-4 | **Neon free**: conexão *pooled* e suspensão por inatividade | só morde no item 7 | usar a pooled no `DB_URL`; o estado "carregando" cobre o cold start duplo |
| D-5 | **Seed × edição**: no Neon o banco persiste, ao contrário do Render free do v2 | só morde depois do primeiro deploy | seed roda só com base vazia; o que for editado no admin sobrevive ao deploy |

---

## Anexo A — ADR-010 (pronto para `docs/adr/adr-010-backend-unico-em-laravel.md`)

```markdown
# ADR-010: Backend único em Laravel, sem serviço FastAPI

- Status: Aceito
- Data: 2026-11-14
- Substitui: ADR-008 (na parte do serviço FastAPI) e o marco "v4"

## Contexto
A versão anterior (Python, biblioteca padrão e SQLite) validou o contrato de rotas, o modelo de dados e
a suíte de testes. Havia um plano de extrair, depois do Laravel, um segundo serviço em FastAPI para
importação com limpeza de texto, deduplicação e busca fonética. O diretório tem cerca de 1,8 mil
contatos, poucas escritas, um único mantenedor e hospedagem gratuita com partida a frio.

## Decisão
Um único aplicativo Laravel (monólito modular, módulos `Directory` e `Access`). Não há serviço Python
nesta versão. Uma API JSON de leitura (`/api/v1`) é exposta pelo próprio Laravel sobre as mesmas classes
de serviço usadas pela interface.

## Motivos
- Sem candidato de extração medido: as três funções previstas rodam em PostgreSQL e PHP nesta escala.
- Regras de escopo, validação e serialização não ficam duplicadas em duas linguagens.
- Cada serviço adicional em hospedagem gratuita soma outra partida a frio.
- Um repositório, um pipeline de testes, uma camada de autenticação e de auditoria.

## Consequências
- Positivas: menos peças móveis, deploy simples, regras num lugar só.
- Negativas: sem o ecossistema Python para limpeza de texto e similaridade; se importação em lote com
  correção de codificação virar requisito, avaliar equivalentes em PHP ou uma ferramenta de linha de
  comando separada.

## Quando revisitar
Só diante de um requisito medido que PHP e PostgreSQL não atendam, capaz de rodar fora do ciclo de
requisição. As fronteiras entre `Directory` e `Access` e o uso de `codigo` como chave lógica mantêm uma
extração possível.
```

## Anexo B — documentos que precisam de ajuste quando o v3 começar

| Arquivo | Ajuste |
|---|---|
| `docs/adr-008-v3-um-servico-e-fastapi-na-v4.md` | marcar como substituído pelo ADR-010 na parte do FastAPI |
| `docs/adr-009-um-repo-para-v3-e-v4.md` | registrar que a v4 fica adiada/indefinida, sem segundo deployável |
| `db/schema.sql` (cabeçalho) | remover a menção à v4/FastAPI como motivo do DDL |
| `docs/identidade-visual.md` | acrescentar topbar, rail e painel fixo; manter paleta |
| `docs/v3-interface.md` | substituir pela seção 5 deste plano |
| `docs/backlog-ideias.md` | mover para "adiado" o que a seção 9 deixa fora do `v3.0` |
| `IDEIAS PARA O V3/` | ficar **fora** do repositório público: contém PDFs e imagens com marca institucional |

## Anexo C — o wireframe como especificação viva (revisão 15/11)

O protótipo em `IDEIAS PARA O V3/wireframe/` **não é descartável**. Ele já está na stack do v3
(Laravel + Blade + Alpine + Vite + Leaflet), então o que foi validado ali vira código, não só
referência. A partilha é:

| Peça | Destino | Regra |
|---|---|---|
| `resources/css/app.css` | **aproveitado** | tokens `--color-*` e `--mapa-*`, `.topbar`, `.rail`, `.painel`, `.folha`, `.selo`, estados de foco. Copiar como base do tema do v3 e só então componentizar |
| Fontsource (`Space Grotesk`, `JetBrains Mono`, OFL 1.1) | **aproveitado** | mesma configuração do Vite; nenhuma fonte de CDN |
| `resources/js/mapa.js` | **aproveitado** | Leaflet sem tiles, cores por CSS var, hachura de sem-comarca, discos de densidade, inset da Grande Vitória e `enquadrar()`. Depende só do payload `cfg` (`geo_url`, `comarcas`, `selecionada`, `foco_ibge`, `modo`) — a v3 emite o mesmo formato |
| `layouts/app.blade.php` | **aproveitado com adaptação** | a moldura (topbar, rail, inset, `data-theme`) vira o layout autenticado; `<title>`, `aria-*` e o trilha ficam |
| `x-icone`, `x-selo`, `x-busca` | **aproveitado com adaptação** | viram componentes do v3; `x-selo` passa a receber `fonte`/`vigente_desde` do banco |
| telas `painel/*.blade.php` | **referência de estrutura** | a marcação orienta a implementação; não é copiada porque os dados vêm de Eloquent com paginação |
| `app/Support/Diretorio.php` | **descartado** | lê `seed.json` em memória; vira repositório Eloquent sobre `comarcas`/`locais`/`nos`/`contatos` |
| `app/Http/Controllers/PainelController.php` | **descartado** | arrays estáticos; vira controller com queries e paginação |
| `data/seed.json` | **descartado como fonte** | o seed do v3 é exportado do SQLite do v2 com `fonte` + `vigente_desde` |
| botão "Gestão", atalho `Ctrl K`, avisos de "fora do protótipo" | **descartado** | placeholders de demonstração |

Régua de enquadramento medida (para o v3 não regredir): o mapa enquadra no **retângulo livre** — rail
(64 px) + 28 px à esquerda, painel + 28 px à direita, 28 px em cima e embaixo —, nunca no container
inteiro; zoom é o maior múltiplo de 0,25 que cabe, aplicado **sem animação** no primeiro quadro
(animar o `setView` inicial fazia o mapa ficar no zoom 7 e saltar depois); legenda e inset, que ocupam
o canto inferior esquerdo, entram como **obstáculo**: o recorte desliza para a direita até 12 px de
folga e nunca cobre o sudoeste do estado. Resultado medido: home **8,25** (estado com 479 × 783 px,
margens 198/152 px), comarca **12,5**, celular **6,75**; município com extensão < 0,6° ganha
`pad(0.14)` para não perder o contexto dos vizinhos.

Duas regras de tela que o protótipo fixou: `/municipio/{ibge}` **redireciona** para a comarca quando
ela existe — "sem comarca" é só para os 9 municípios que realmente não têm registro (Brejetuba,
Divino de São Lourenço, Governador Lindenberg, Irupi, Ponto Belo, São Roque do Canaã, Sooretama,
Vila Pavão, Vila Valério); e o município focado mantém a **hachura** mesmo quando selecionado, com
contorno tracejado em accent — cor sólida de seleção apagava justamente o estado que a tela explica.

Hierarquia de conteúdo: na tela de **contato** o título é a **unidade** (com link), o rótulo é
`tipo · setor` e só então o valor em destaque — o valor é o que se copia, não quem é; e-mail usa
1,0625 rem (cabe em uma linha nos 390 px) enquanto telefone fica em 1,25 rem. Na **busca**, o
subtítulo do resultado é `unidade de origem · comarca` — repetir "Cartório" em 84 linhas não
distingue nada, e o contexto é o que a pessoa usa para escolher.

Capturas: `IDEIAS PARA O V3/wireframe-hermes-inicio.png` (versão anterior) e as atuais em `/tmp/v3n-*.png`.
