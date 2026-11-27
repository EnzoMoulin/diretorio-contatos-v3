# Plano do v3 — arquitetura e direção visual

**Data:** 14/11/2026 · **Status:** planejamento encerrado em 20/11/2026 — base para o início da implementação do v3
**Substitui, onde houver conflito:** `docs/fase-3-arquitetura-e-adrs.md`, `docs/arquitetura-v3.md`,
`docs/v3-interface.md` e `docs/v3-arquitetura-preparacao.md`.
**Documento irmão:** `db/schema.sql` continua sendo a fonte do DDL; o ADR-010 (Anexo A) entra em
`docs/adr/`.

> **Status em 25/11/2026 — item 1 do `v3.0` entregue.** Laravel 13 + PHP 8.4 em `apps/web/` (PR #35,
> squash `aa9a0c9`; issue #4 fechada), com a primeira leitura (`GET /` e `GET /api/v1/hierarquia`) lendo o
> Postgres e os quatro jobs do `laravel.yml` verdes e **required** no ruleset `protege-default`: `Pint (PSR-12)`,
> `Larastan (análise estática)`, `Pest (Feature)` e `Blade e rotas (sem servidor)`, somados aos dois que já
> eram exigidos. As migrations do item 2 também estão versionadas (PR #34). **Risco registrado:** o build da
> imagem Docker **não** foi exercitado — esta máquina não tem Docker; é risco do primeiro deploy do v3, não do v2.

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
| Contrato de rotas, modelo e testes do v2 | Pronto, congelado | `~/Emprego/Projetos/diretorio-contatos-v2` (HEAD `bb9b62b`; `a93aeb1` e `fcdb719` morreram nas limpezas de 20/11 e 26/11) |
| Seed mascarado | 1.807 contatos, 70 comarcas (69 com IBGE + Sede), 72 fóruns, 856 nós, 11 úteis | `seed_mascarado.json` |
| DDL PostgreSQL | 7 tabelas, extensões, funções e invariantes já escritas | `db/schema.sql` |
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
1. `Directory` não depende de `Access`; `Access` referencia o nó pela `chave` lógica, nunca pelo `id`.
2. Interface (Livewire), admin (Filament) e API de leitura chamam **as mesmas classes de serviço**.
   Regra de negócio em componente de interface é dívida, não atalho.
3. `chave` (`MUN0001`, `FOR0001`, `UNI0001`, `C000001`) é a chave lógica imutável do demo; `bigint` é
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

- **Escopo por nó com herança** (`_escopo`, `_cadeia`, `_pais`, `_cobre` no `app.py`): vira
  `EscopoResolvido` (uma leitura de `acessos` por requisição, chaves → ids) + `DiretorioPolicy`. O papel
  decide *se entra no painel*; o escopo decide *o que pode editar*. Leitura pública não é escopada.
  `ADMIN` edita tudo; `GESTOR_COMARCA` e `EDITOR` editam dentro do escopo; `DEMO` lê tudo e não escreve
  (decisão de 26/11). Pedido de acesso é anônimo (nome/e-mail) e pode ser ligado a `usuarios.id` quando
  houver sessão; a aprovação do admin é a prova de identidade — não há OAuth no `v3.0`.
- **Procedência entra no `v3.0`.** `fonte` e `vigente_desde` já existem na tabela `contatos` do v2 e
  **não existem no `seed_mascarado.json`** (o v2 as extrai da observação livre e grava no SQLite). O
  seed do v3 é exportado do banco do v2 (somente leitura) **trazendo os valores** — no wireframe deu
  329 contatos com documento: 43 do ofício de março/2024 e 286 do catálogo da Sede. O selo `fonte · desde`
  aparece na tela do contato — a parte que interessa ao visitante. A fila "Reconferir" (do documento mais
  antigo para o mais novo) é **tela de gestão interna**, não da consulta pública.
  Contato sem documento não ganha selo: a ausência de data nunca vira "atual".
- **572 contatos não têm `uo`**: o modelo e a tela aceitam contato pendurado direto no local.
- **`desc` não é rótulo de canal.** Medido: 843 vazios, "Geral" 93, "Institucional" 87, "Secretaria de
  Gestão do Foro" 47, "Contadoria" 34, "Oficiais de Justiça" 31, "Cartório" 30. São setores/descrições,
  não "Balcão"/"Chefia". **A interface mostra o tipo (Telefone, Ramal, WhatsApp, E-mail) e a `desc`
  quando existir; nunca inventa rótulo.**
- **Endereço**: 72/72 fóruns têm; 1/70 comarcas (a Sede); 18/856 nós. Logo "Copiar endereço" é um
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

Um único histórico (`historico` no `schema.sql`), escrito por observers/ações, com autor, data e o
retrato **antes/depois em JSONB** (`antes`, `depois`), ancorado pela chave lógica `entidade_chave`.
Filament não ganha pacote de auditoria paralelo. O que o visitante vê é a trilha desse histórico;
o que o admin vê é a mesma trilha com diff. O histórico é do **dado** (`contato`, `no`, `local`,
`comarca`, `util`); `usuario`, `acesso` e `pedido` são tabelas do app, com auditoria própria na tabela
`auditoria` (ator, ação, entidade, antes/depois em JSONB), e não entram nessa tabela nem no expurgo de
180 dias. Revogar acesso apaga a linha de `acessos`, mas deixa o retrato em `auditoria`.

### 3.7 Deploy

Um serviço Docker no Render (free) + Postgres no Neon. Fila `sync` no demo. `/up` — a rota de health do
Laravel, que é o `healthCheckPath` do `render.yaml` — **sem tocar no banco** (se o banco cair, o serviço
não pode ser reiniciado em cascata). Seed idempotente na subida.
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
| "Diretório Institucional", sigla do tribunal e domínio institucional | ❌ **bloqueante** | viola a regra de nenhuma marca institucional; precisa ser caçado em mockups, seed, capturas e código |
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
| "Recorte ampliado Grande Vitória é opcional" | ✅ | entra, mas como **atalho de seleção** (clique abre a comarca), não como segundo mapa |
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
- **Inset "Grande Vitória"** (144 × 208 px, alto e estreito porque a região é alongada no sentido norte-sul) com os **sete** municípios da região metropolitana; o enquadramento reserva a faixa do título (`paddingTopLeft`) e usa zoom fracionário, senão o ajuste cai um nível inteiro — Vitória, Vila Velha, Cariacica, Serra, Viana, Guarapari e Fundão —, no canto inferior esquerdo, 224 px, só quando o zoom é baixo e a viewport é
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
- Inset desligado; sem rótulos fixos — o nome vem da dica do disco (1º toque nomeia, 2º abre); discos com 74% do raio.

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
| Endereço | o endereço do local (18 dos 856 nós) é link para o Google Maps: `https://www.google.com/maps/search/?api=1&query=` + endereço e CEP urlencoded, em nova aba, com sublinhado sólido discreto e ícone de saída. Vale na ficha do contato e no cartão da unidade |
| Sidebar | ordem fixa: **Comarcas · Sede · Telefones · Favoritos** (F-11, 19/11). Sede é `MUN0000`, não é comarca e por isso tem item próprio. **Reconferir não entra na consulta pública**: é gestão interna, atrás do item `Gestão` (no v3, o painel Filament) |
| Gestão | no **pé do rail**, separada por um fio e empurrada para baixo por `margin-top: auto`: é modo, não destino, e não disputa a ordem da navegação. **Sem sessão é porta, não menu** (24/11): o item é link direto para `/entrar`, porque um popover de um item só custa um clique e não oferece escolha; com sessão abre Conferência e Sair, com a identidade no cabeçalho e Sair como ação de conta. **Sem item "Ajustes"**: em v3.0 os ajustes reais vivem no Filament, em `/admin`. No celular, o mesmo lugar no pé da gaveta. Saiu do topo: a mesma ação em dois cantos da tela divide a atenção e a topbar fica para busca e tema |
| Impressão | toda unidade tem **ficha imprimível** (`window.print()` no cabeçalho) e a folha é uma **peça própria**, não o painel: `.ficha-impressao` fica oculta na tela e, no print, `.painel-corpo > *:not(.ficha-impressao)` sai de cena. A4 retrato, duas colunas de setores, número em mono à esquerda e tipo à direita; quando o setor é homogêneo o tipo sobe para o título (`CARTÓRIO · TELEFONE · 7`) e não se repete em cada linha. E-mail e endereço ganham a linha inteira, a unidade pequena imprime em uma coluna com corpo maior, e a nota de dados fictícios fica `fixed` no pé de toda página. Setor recolhido na tela sai impresso: a ficha lê o dado, não o `x-show`. É o modo balcão: o papel não rola nem clica |
| Zoom do mapa | à direita, alinhado com a borda esquerda do painel (`right: calc(1.25rem + 26.5rem + 1rem)`), porque a coluna esquerda é do recorte. `left: auto !important` vence o `left: 0` do Leaflet. No celular fica no canto superior direito |
| Telefones Úteis | cada número é `tel:`; o botão de copiar continua ao lado. A lista não entra no mapa nem no índice de favoritos. No fim do painel é **atalho** para a tela, não acordeão: a miniatura repetia nome e número sem os canais de ação; o contador fica porque informa quanto há do outro lado (24/11) |
| Lista de comarcas | ordem do catálogo com grupos declarados ("Grande Vitória · 7 comarcas", "Interior · 62 comarcas") e a Sede num bloco próprio acima; **sem cabeçalho de coluna** (24/11): "comarcas" era redundante e "contatos" repetia a linha de totais ("69 comarcas e a estrutura central · 1.807 contatos"), criando uma faixa vazia — a lista começa direto no primeiro item |
| Cartão de fórum | nome, endereço, **linha de métricas** ("15 unidades · 81 contatos"), e-mail e ações. A contagem não flutua no canto: na linha, ela compara entre cards e não disputa com o nome (24/11) |
| Favorito de setor | mesmo mecanismo do nó (código, índice do servidor, `localStorage`), com a estrela **ao lado** do cabeçalho recolhível, nunca dentro dele — o toque na estrela não recolhe a seção; o destino é a unidade, na âncora do setor (24/11) |
| Fórum no mapa | losango de 13 px num **alvo de 44 px**, a partir do zoom de comarca, vazado quando a posição é aproximada. **Fórum e losango são o mesmo objeto visto duas vezes**: hover (ou foco de teclado) no cartão acende o losango e abre a dica dele; hover no losango acende o cartão — estado de interface derivado da mesma `chave`, sem dado nem rota novos (24/11) |
| Alternador de tema | escuro é o padrão; o controle só aparece quando o tema claro estiver medido |
| Favorito | `localStorage` no demo; a tabela `favoritos` entra com a sincronização, depois do `v3.0` |
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
  plugin novo), mais rótulo em texto. Cor sozinha não comunica. A hachura é estado permanente: sobrevive
  ao hover, ao foco e à troca de tema — só o contorno muda.
- **Tema claro revisado (15/11)**: o claro anterior era o escuro com as cores trocadas — mapa lavado,
  malha e discos da cor do fundo, hachura quase invisível, rail e painel sem camada. O claro agora tem
  branco puro nas superfícies flutuantes, base mais fria, malha municipal mais escura que o oceano,
  disco com halo branco, hachura mais fechada e texto secundário escurecido. O mapa continua sob os
  painéis; o que muda é a separação entre figura e fundo.
- **Amostra da legenda é a mesma linguagem do mapa**: "sem comarca" aparece como quadradinho listrado,
  nunca como disco teal; os discos 5/60/166 continuam sendo a escala de densidade. Legenda e mapa
  dizendo coisas diferentes é pior do que legenda nenhuma.
- **Recorte e legenda ocupam lados opostos da base do mapa**: o recorte à esquerda, a escala à direita,
  alinhados pelo mesmo pé. Separados, cada um guarda o seu tamanho — o recorte é alto e estreito, a
  legenda é baixa e larga; juntos num cartão só, um dos dois sobrava. O crédito da malha desce para
  debaixo do recorte e os controles de zoom vão para o alto da direita, deixando a coluna esquerda só
  com o recorte.
- **O recorte fala a mesma língua do mapa grande**: tooltip com nome e quantidade de contatos, e o
  destaque é um só — passar o mouse num município do recorte acende o polígono no mapa principal, o disco,
  a linha do painel e vice-versa, nos quatro sentidos.
- **O tooltip do recorte mora fora da caixa**: a caixa de 144 px é menor que o balão e cortava o texto.
  O balão é um elemento do mapa (não do recorte), posicionado ao lado da caixa na altura do município,
  com limites na área do mapa — nunca é cortado e nunca cobre o desenho do recorte.
- **Sem rótulos fixos (17/11):** o nome da comarca vem da dica do disco no hover, do recorte e do rail; no celular, do 1º toque no disco (o 2º abre a comarca). `ETIQUETAS`, `ajustarRotulos` e `colisoes` saíram de `mapa.js`.
- Hover na lista acende o polígono; clique e lista levam ao mesmo estado. No município sem comarca o
  hover realça só o contorno: a hachura nunca sai.
- **Inset é atalho, não lupa:** clicar num município faz o mesmo que clicar no mapa ou na linha do
  painel — abre a comarca. O recorte existe porque, no mapa grande, a região metropolitana é um
  aglomerado de polígonos pequenos demais para acertar; se o clique só aproximasse, o usuário ainda
  teria de clicar de novo no polígono, e o recorte resolveria metade do problema. O gesto é único em
  todo o produto: achar e clicar é abrir. O zoom acontece como consequência, na página da comarca.
- **Cada município do recorte é botão:** recebe foco por teclado, `role="button"`, rótulo com nome e
  contagem ("Viana, 37 contatos — abrir a comarca") e responde a Enter e Espaço. O contêiner é
  `role="group"`; a dica de hover saiu, porque o ponteiro e o próprio tooltip já dizem o que o alvo é.

### 5.7 Admin (Filament)

Adota a tela de gestão das referências, com quatro correções:

1. Listagem com filtros por comarca/local/tipo **antes** do formulário; a tela de edição é a segunda.
2. Excluir com confirmação (nome do que se apaga), nunca só ícone.
3. Salvar fixo no rodapé em formulários longos, além do topo.
4. Nada de nome real, badge inventado ou histórico fictício: autor é "demonstração", e a trilha sai do
   histórico real do seed.

Grupos de navegação: Estrutura (comarcas, locais, nós), Contatos (canais, úteis), Controle (histórico,
usuários, papéis), e "Voltar para a consulta".

A **Conferência** mostra a fila inteira ao admin; a ação `conferir` só habilita dentro do escopo
(decisão de 26/11). O `v3.0` não tem `POST /entrar`: o login é `/admin` do Filament; o teste do demo é
"sem `APP_DEMO` o usuário demo não existe ou não entra".

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
2. Pessoal autenticado: sincroniza com a tabela `favoritos` (`usuario_id`, `chave` do nó, criado_em).
   A tabela e a sincronização entram depois do `v3.0`, junto com o favorito de conta; o demo não
   escreve no banco.
3. Ao entrar, o que estava no `localStorage` é oferecido para importar — nunca mesclado em silêncio.
4. Favorito é sempre de **nó** (local, unidade ou setor), nunca de contato: contato muda, nó permanece.

### 5.9 A unidade é o último nível (15/11, já no protótipo)

A unidade também é a **unidade de impressão**: a ficha sai inteira, com todos os setores abertos, mesmo
os que estavam recolhidos na tela. Setor recolhido é economia de rolagem; no papel, esconder dado é
defeito — quem imprime quer a lista completa para pendurar no balcão.

O setor **não tem tela**. A navegação termina na unidade, que mostra todos os seus setores e os
contatos de cada um na mesma página. Motivo medido no seed: 202 unidades têm filhos, com **mediana de
1,5 setor** por unidade e **mediana de 1 contato** por nó — clicar num setor quase sempre abre uma
tela com uma linha.

1. A página do local lista, nesta ordem: contatos pendurados direto no local (572 no seed) e depois os
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
7. Endereço e "copiar" são do local: só 18 dos 856 nós têm endereço próprio.

---

## 6. Comparação: wireframe × imagens de referência

### 6.1 O que cada família faz melhor

| Critério | Wireframe navegável | Imagens de referência |
|---|---|---|
| Modelo de navegação | mapa + lista + drill-down no mesmo painel | mesma ideia, mas com 3 blocos competindo (rail, sidebar, painel) |
| Mapa como sistema | legenda, disco de densidade, dica com nome e contagem, estado de falha | mapa como fundo decorativo; sem chave, sem dica e sem zoom |
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
| Rótulos de comarca | wireframe | **sai** (17/11: 5 dos 6 rótulos tocavam disco alheio; o nome vem da dica do disco) |
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
| 1 | ~~Esqueleto Laravel 13 + PHP 8.4, Docker, Pint, Pest, CI (lint + testes)~~ — **entregue em 25/11/2026** (PR #35, `aa9a0c9`) | `pest` verde no CI — **cumprido**: quatro jobs verdes e required no ruleset (Docker versionado, build não exercitado) |
| 2 | Migrações do `db/schema.sql` (FKs e CHECK reais) + carga do seed do v2 (com `fonte`/`vigente_desde`) | seed idempotente, contagens conferidas |
| 3 | Policies de escopo por nó + testes de Policy sem HTTP | paridade dos 36 casos do v2 em `docs/paridade-testes-v2.md`; `ADMIN` ∨ escopo (`GESTOR_COMARCA`/`EDITOR`), `DEMO` só lê; nenhum teste depende de rota do Filament |
| 4 | API `/api/v1` só leitura + testes de contrato | rotas do contrato respondendo |
| 5 | Consulta pública (Livewire): topbar, rail, mapa, painel, 7 estados, celular, tema escuro e claro | estados 1–7 navegáveis com dado real; os dois temas medidos |
| 6 | Admin Filament: CRUD, pedidos, aprovação, histórico | editar contato muda o histórico |
| 7 | Deploy Render + Neon, README com quadro de estado, ADR-010 | URL viva e `/up` sem banco |

O item 3 não porta o `test_regras` inteiro: dos 20 casos, 3 são de escopo, 7 são do fluxo de pedidos
por HTTP (item 6) e 3 exigem `motivo_recusa`; a tabela de paridade dos 36 casos está em
`docs/paridade-testes-v2.md`.

Depois do `v3.0`: exportação CSV (fila `sync`), favoritos com conta (sincronização do `localStorage`),
notificações.

### Ordem de trabalho nas primeiras sessões

1. Esqueleto + migrações + seed (itens 1–2): é onde o plano pode falhar rápido.
2. Policies e API (itens 3–4): fecha o contrato antes de desenhar tela.
3. UI pública (item 5): só então a direção do wireframe é implementada no `apps/web` — o protótipo é
   régua, não código, e o markup dele não é promovido.
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
| 3 | Favoritos | **híbrido**: `localStorage` no demo; a tabela `favoritos` entra com a sincronização, depois do `v3.0` | seção 5.8 |
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

Decisões de **interface** herdadas da revisão externa do wireframe (16/11) não entram nesta tabela: elas
dependem de ter a tela na frente, então estão na §13 como **F-5 a F-12**. Nenhuma delas muda arquitetura.

## 13. Fila de retomada do v3 (anotada em 15/11)

Ideias aprovadas para depois do deploy do v2 e do gate do v2. Nenhuma entra agora; a ordem abaixo é a
de execução sugerida, não a de valor.

| # | Ideia | Por que | Custo |
|---|---|---|---|
| F-1 | **Índice reverso de ramais** (`/ramais`) | o produto é organizado por lugar, mas a pergunta do balcão é "quem é o ramal 1234?". Lista dos 444 ramais em ordem numérica, com comarca · unidade · setor ao lado, imprimível. **Tela nova**: entra na §5.5 e no escopo do v3, não é acabamento | baixo: uma rota, uma view, item no índice — **aplicado em 25/11/2026** (issue #7): `ListarRamais`, view própria, 6 testes de feature, 17 páginas A4 |
| F-2 | **Selo de procedência no contato** | "ofício de março/2024 · desde 03/2024" ao lado do canal: o diferencial do v2 que o wireframe perdeu, porque o `seed_mascarado.json` não tem `fonte`/`vigente_desde`. Depende do **export do seed** (D-3) | baixo depois do seed |
| F-3 | **`Ctrl K` como paleta de verdade** | hoje o atalho só foca o campo. Paleta com resultados agrupados (comarcas, unidades, setores, contatos), ↑↓ e Enter, destino já com âncora | médio (~150 linhas de JS) |
| F-4 | **Página "v1 → v2 → v3"** | uma captura por versão, os números de cada uma e a decisão que a definiu. Serve à candidatura: mostra arco, não três repositórios soltos | baixo, e vive nos docs |
| F-5 | **Largura do painel com a lista aberta** (424 × 520 px) | a revisão externa de 16/11 propôs 520 px a partir da comarca. A medição diz que o mapa é limitado pela altura, então 520 **não** encolhe o mapa — mas estica as linhas de leitura. Com o valor de contato quebrando em duas linhas (feito no bloco A), o corte que motivava a proposta sumiu | baixo — **decidido em 20/11/2026: mantém 424 px** (520 não encolhe o mapa, que é limitado pela altura, e só alonga a linha de leitura) |
| F-6 | **Busca multitermo** | hoje `Diretorio::busca()` compara o termo inteiro (`str_contains`): “1 vara civel vitoria” e “cartorio vitoria” não acham nada, porque a frase não existe. Tokens com AND, um `ILIKE` por palavra sobre `busca_texto`. É a única pendência que muda **comportamento**, não aparência — entra junto com a busca da v3, não como polimento | baixo no protótipo, médio no SQL/índice |
| F-7 | **Cabeçalhos “Grande Vitória” e “Interior”** | a ordem da lista não é acidente: são as 7 da RMGV e depois o interior alfabético, herdadas do catálogo. O problema é não estar declarado. Dois cabeçalhos fixos + cabeçalho de colunas fixo; a ordem não muda (decisão de 15/11) | baixo — **aplicado em 19/11/2026** (rodada 6) |
| F-8 | **Formato único de telefone na exibição** | o seed tem três grafias (`(27) 3145 XXXX`, `(27) 3232. XXXX`, `(27) 3145-XXXX`). Normalizar separador **não** completa dígito (o mascaramento fica), mas toca a regra “mostra o valor como está” — decidir junto da normalização do seed | baixo |
| F-9 | **Anatomia da linha de contato: rótulo antes do valor** | a pessoa procura “Recepção”, não “(27) 3145-XXXX”, e hoje o rótulo está embaixo, em 11 px. Inverte a linha de `valor / tipo · desc` para `rótulo / valor`, e tira o tile de ícone (o tipo já está no formato). Mesma anatomia da lista de Úteis | baixo — **descartado em 20/11/2026** por medição: 843 de 1.807 contatos não têm `desc`, então valor-primeiro fica |
| F-10 | **Reconferência por local, com progresso** | agrupar por local, contador “12 de 329 conferidos”, aba “sem documento · 1.478” e “✓ Conferi” por local. No v3 isso é **Filament**, não Blade — não investir no protótipo | médio (2–3 dias com escrita) |
| F-11 | **“Úteis” → “Telefones” no rail** | “Úteis” é adjetivo sem substantivo; “Telefones” nomeia. Um minuto de mudança, mas mexe em nome de destino decidido em 15/11 — por isso é decisão, não acabamento | mínimo — **aplicado em 19/11/2026** (“Telefones”, 43,61 px em 51) |
| F-12 | **Conferir no seed um e-mail em dois níveis** | a revisão de 16/11 apontou o mesmo endereço como contato direto da comarca (pág. 3) e dentro do 3º Juizado (pág. 5): “uma das duas mente”. Se for o mesmo registro contado em dois níveis, é erro de dado — e a consulta pública herda | baixo, e é conferência de dado |

**Status em 20/11/2026:** F-7 e F-11 foram aplicados na rodada 6 (19/11), F-9 foi descartado por
medição (valor-primeiro vence: 843 de 1.807 contatos não têm `desc`) e **F-5 foi decidido: o painel
fica em 424 px**. **Em 25/11/2026 a F-1 foi aplicada** (issue #7: `/ramais`, 444 ramais, busca por
prefixo e folha A4). Nenhuma decisão de interface da §13 segue aberta; F-6, F-8 e F-12 entram com a v3
e F-10 pertence ao Filament. F-2…F-4 continuam na fila de retomada, depois do deploy do v2.

### Testes a escrever quando a primeira rota Laravel existir (anotado em 17/11)

Nada disso se escreve antes da primeira rota: quando a nota foi feita (17/11), o v3 tinha 45
invariantes de schema, 20 de carga, 5 de plano e 33 casos de autoteste do scanner, e nenhuma linha de
aplicação além das migrations de banco. Hoje a primeira rota e a suíte Pest já existem (19 casos em
25/11), então estes testes passam a entrar por issue, não por bloco. A ordem abaixo é de custo, não
de valor.

| # | Teste | Por que | Custo |
|---|---|---|---|
| F-13 | **Payload por rota** (`/`, `/comarca/{chave}`, `/local/{chave}`, busca) | afirmar o shape contra `docs/paridade/esperado.json` — o fixture é o contrato congelado e é o que impede mudar a leitura que o usuário conhece. Assertar JSON, nunca HTML | baixo: um por rota |
| F-14 | **Escrita contra Postgres no CI** | constraint e `norm_texto` só valem se o app passar por elas: Eloquent grava `fonte` livre e o teste de DDL não vê. Feature test de escrita no *service container* `postgres:16` do job `ddl`, **nunca SQLite** | baixo–médio: mexer no job |
| F-15 | **Portar os 9 testes de procedência do v2** | `fonte`/`vigente_desde`, edição manual e herança — o v2 já pagou o custo de descobrir os casos | baixo: porta direta |
| F-16 | **Smoke de console (Playwright)** | `pageerror === 0` nas rotas principais em 1440×900 e 390×844: é a sonda que expôs o `@js` em atributo e o único teste de UI que paga antes de a UI estabilizar | baixo |
| F-17 | **RBAC e escopo** | Reconfirmar por papel, export autorizado, escopo por local — quando o Filament entrar | médio |

Não valem: unitário de helper sem regra, snapshot de HTML (quebra a cada classe) e qualquer teste do
protótipo em `IDEIAS PARA O V3/` (não versionado, camada de dados descartada).

---

## 14. Critérios para encerrar o planejamento

O planejamento se encerra quando os quatro artefatos existirem e não se contradisserem. Estado em
20/11/2026:

| Artefato | Onde | Estado |
|---|---|---|
| Arquitetura: stack, serviço único, Neon, ADRs 008/009/010, DDL | `arquitetura-v3.md`, `adr-*.md`, §3 deste plano | **fechado** (10–14/11) |
| Sistema visual: tokens, geometria de desktop e celular, componentes, mapa, admin | §5, `identidade-visual.md` | **fechado** (14–15/11) |
| Direção visual escolhida | Direção A — mapa protagonista | **fechado** (16/11) |
| Protótipo: 15 telas em Laravel + Blade/Alpine/Leaflet (desktop, celular e a ficha A4) | `"IDEIAS PARA O V3"/wireframe`, PDF `wireframe-v3-telas.pdf` | **fechado em conteúdo** (20/11: a rodada 6 aplicou a revisão da 5 e os três pedidos do dono — F-7 e F-11 junto; a rodada 7 varreu os sete estados sem mudar uma linha de código; PDF de 16 páginas) |

O único item do protótipo sem prova é Safari/iOS: o WebKit não abre neste host (faltam
`libgstreamer-plugins-bad1.0-0` e `libavif16`), então é pendência de ambiente, não de produto.

**Planejamento declarado encerrado em 20/11/2026, por decisão do Enzo.** A ordem a partir daqui é
terminar e publicar o v2, e só então começar o `v3.0` pelo item 1 (esqueleto) — a UI é o item 5 e não
bloqueia os quatro primeiros.

O que **não** é pendência de planejamento, e sim trabalho do `v3.0` item 5:

- legenda no celular (adiada por decisão; quando entrar, as amostras usam `raioDe`);
- toque com folha `cheia` (hoje seleciona sem dica) e o caso do pino colado na borda com `meia`;
- medir os pares do tema claro — o controle já nasce na topbar, a medição é do componente.

Regra de saída: **o planejamento pode ser declarado encerrado quando a rodada 4 de revisão voltar sem
achado estrutural** (arquitetura, vocabulário, hierarquia de tela). Achado de detalhe — espaçamento,
texto, estado — se resolve dentro do item 5, com o protótipo como referência, e não reabre o
planejamento. **A condição foi cumprida em 18/11**: a rodada 4 voltou sem achado estrutural — o que
sobrava era a dica do 1º toque saindo da tela pela esquerda, resolvida no mesmo dia — e as rodadas 5–7
só produziram detalhe e evidência (realce de hover preso no toque, alvo do toque entre discos, os sete
estados). Os itens 1–4 do `v3.0` (esqueleto, migrações, policies, API) podem começar: nenhum deles
depende de UI.

## Anexo A — ADR-010 (registrado em `docs/adr-010-backend-unico-em-laravel.md`; o texto abaixo é cópia)

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
requisição. As fronteiras entre `Directory` e `Access` e o uso de `chave` como chave lógica mantêm uma
extração possível.
```

## Anexo B — documentos que precisam de ajuste quando o v3 começar

| Arquivo | Ajuste |
|---|---|
| `docs/adr-008-v3-um-servico-e-fastapi-na-v4.md` | marcar como substituído pelo ADR-010 na parte do FastAPI — **feito em 25/11/2026** |
| `docs/adr-009-um-repo-para-v3-e-v4.md` | registrar que a v4 fica adiada/indefinida, sem segundo deployável — **feito em 25/11/2026** |
| `db/schema.sql` (cabeçalho) | remover a menção à v4/FastAPI como motivo do DDL — **feito em 25/11/2026** |
| `README.md`, `docs/arquitetura-v3.md`, `db/README.md` | notas de status: um deployável, v4 adiada — **feito em 25/11/2026** |
| `docs/identidade-visual.md` | acrescentar topbar, rail e painel fixo; manter paleta — **item 5** |
| `docs/v3-interface.md` | nota de status no topo apontando a §5; corpo fica como registro de 11–12/11 — **feito em 25/11/2026** |
| `docs/backlog-ideias.md` | mover para "adiado" o que a seção 9 deixa fora do `v3.0` — **nota de status em 25/11; itens v4 ficam congelados** |
| `IDEIAS PARA O V3/` | ficar **fora** do repositório público: contém PDFs e imagens com marca institucional — **feito** (`.gitignore`) |

## Anexo C — o wireframe como especificação viva (revisão 27/11)

O protótipo em `IDEIAS PARA O V3/wireframe/` **não é descartável como referência**: ele já está na
stack do v3 (Laravel + Blade + Alpine + Vite + Leaflet), então o que foi validado ali vira **regra de
implementação**, não código. A **direção** é implementada no `apps/web`; o markup não é promovido
(decisão de 26/11, `d4f7155`) — o que se copia é a medida, não o arquivo. A partilha é:

| Peça | Destino | Regra |
|---|---|---|
| `resources/css/app.css` | **referência** | tokens `--color-*` e `--mapa-*`, `.topbar`, `.rail`, `.painel`, `.folha`, `.selo`, estados de foco: a régua do tema. O CSS do v3 é escrito no `apps/web` contra esses valores, sem copiar a folha |
| Fontsource (`Space Grotesk`, `JetBrains Mono`, OFL 1.1) | **referência** | mesmas famílias e versões no build do `apps/web`; nenhuma fonte de CDN |
| `resources/js/mapa.js` | **referência de comportamento** | Leaflet sem tiles, cores por CSS var, hachura de sem-comarca, discos de densidade, inset da Grande Vitória e `enquadrar()` são regras a reproduzir e medir; o arquivo não é promovido |
| `layouts/app.blade.php` | **referência de estrutura** | a moldura (topbar, rail, inset, `data-theme`) orienta o layout do v3; a marcação é reescrita no `apps/web` |
| `x-icone`, `x-selo`, `x-busca` | **referência de estrutura** | os componentes do v3 têm o mesmo papel; `x-selo` recebe `fonte`/`vigente_desde` do banco. Marcação reescrita, não copiada |
| telas `painel/*.blade.php` | **referência de estrutura** | a marcação orienta a implementação; não é copiada porque os dados vêm de Eloquent com paginação |
| `app/Support/Diretorio.php` | **descartado** | lê `seed.json` em memória; vira repositório Eloquent sobre `comarcas`/`locais`/`nos`/`contatos` |
| `app/Http/Controllers/PainelController.php` | **descartado** | arrays estáticos; vira controller com queries e paginação |
| `data/seed.json` | **descartado como fonte** | o seed do v3 é exportado do SQLite do v2 com `fonte` + `vigente_desde` |
| botão "Gestão", atalho `Ctrl K`, avisos de "fora do protótipo" | **descartado** | placeholders de demonstração |

**Revisão de 27/11/2026:** a coluna dizia "aproveitado" para CSS, fontes, `mapa.js`, layout e
componentes, o que lia como autorização para copiar arquivo — o oposto da decisão de 26/11. A
partilha agora é de **medida e comportamento**; nenhum arquivo do protótipo é promovido ao `apps/web`.

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
