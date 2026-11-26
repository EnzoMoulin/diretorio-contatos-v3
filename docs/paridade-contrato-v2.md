# Contrato de leitura — paridade v2 × v3

Registro de 12/11/2026. Fecha a ideia **06** do `backlog-ideias.md`: antes de escrever
tela, o contrato de leitura da v3 fica travado contra o do v2, que já está em uso.

O v2 tem 94 casos de teste, mas nenhum deles diz o que a **API** devolve: eles testam o v2
contra si mesmo. Este documento é a outra metade — o que a v3 tem de devolver para ser
sucessora e não reescrita com defeito novo.

## O que está congelado aqui

| Peça | Arquivo | Papel |
| :--- | :--- | :--- |
| Fixture de referência | `docs/paridade/esperado.json` | 28 KB, capturado do v2 local: nomes, ordem e contagens por rota |
| Captura | `scripts/capturar-contrato.py` | gera o fixture a partir de um servidor v2 no ar |
| Comparação | `scripts/comparar-contrato.py` | diff entre dois fixtures **ou** captura ao vivo da v3 (`--v3`), com classe de paridade por rota |

O fixture **não guarda telefone, e-mail nem nome de pessoa**. Nomes de comarca, fórum e
unidade são públicos (`dados/README.md`); a amostra de contatos usa só `nome`/`cargo` já
mascarados como `NN` no seed.

## Baseline medido (v2 em 12/11/2026, seed mascarado)

| Medida | Valor |
| :--- | --: |
| Locais na árvore (`/api/hierarquia`) | 70 (69 comarcas + Sede) |
| Fóruns | 72 |
| Nós na árvore exposta | 856 |
| Úteis | 11 |
| Contatos | 1.807 |
| Sede | 286 contatos · 22 unidades · 166 setores |
| Vitória | 166 contatos · 2 fóruns |
| Vila Velha | 92 contatos |

O `nos_total` de 856 é o número que o v3 usa no README. Bate — e é a primeira coisa que a
comparação confirma.

## Rotas e classe de paridade

| Rota do v2 | Equivalente na v3 | Classe |
| :--- | :--- | :--- |
| `GET /api/hierarquia` | árvore de `/api/v1/locais` + nós | **estrita** |
| `GET /api/comarcas` | `/api/v1/locais` (tipo/counts) | **estrita** |
| `GET /api/foruns?comarca_id=` | `/api/v1/locais?comarca=` | **estrita** |
| `GET /api/unidades?forum_id=` | `/api/v1/locais/{chave}/nos` | **estrita** |
| `GET /api/contatos?{comarca,forum,unidade,setor}_id=&q=` | `/api/v1/contatos` | **estrita** (contagem) |
| `GET /api/busca?q=&limite=` | `/api/v1/busca` | **resultado** (recall muda por decisão) |
| `GET /api/setores?unidade_id=` | — | **sem equivalente** |
| `/api/me`, `/api/resumo`, `/api/usuarios`, `/api/pedidos`, `/api/historico*` | fora de escopo | autenticadas/admin |

**Estrita** = tem de bater exatamente (nomes, ordem, contagens). **Resultado** = a v3 pode
devolver conjunto diferente; a diferença é medida e registrada, não consertada. **Sem
equivalente** = o modelo mudou de propósito.

## As quatro armadilhas que a captura já revelou

1. **`id` não serve para comparar.** O v2 gera identificadores internos e descartáveis
   (`M1`, `F1`); a v3 usa a chave lógica do seed (`MUN0001`, `FOR0001`). Comparar por `id`
   entre versões não diz nada — por isso o fixture usa **nome + ordem** na estrutura e
   **contagem** nos contatos.
2. **`unidades` + `setores` ≠ `nos`.** O v2 tem 299 unidades e 886 setores em tabelas
   separadas; a v3 tem uma árvore única de 856 nós. O mapeamento não é 1:1 e não está
   demonstrado — quem escrever a migration do diretório precisa provar a equivalência antes
   de declarar paridade de estrutura. O fixture guarda as duas contagens para que a diferença
   apareça em vez de passar calada.
3. **O `LIKE` do v2 é sensível a acento.** Medido: `/api/contatos?q=cartorio` → **0**;
   `q=cartório` → **209**. A v3 normaliza (`norm_texto`) e vai achar as duas grafias. É
   divergência **esperada e desejada** — mas é mudança de comportamento, não detalhe, e por
   isso essa rota é classe *resultado*, não *estrita*.
4. **A busca satura no limite.** `/api/busca` corta em 20 (padrão 8, teto 20), então
   qualquer termo comum devolve 20 e o número não mede recall. Para medir recall de verdade
   é preciso comparar o **conjunto de chaves** com o limite alto, não o total — ajuste a
   fazer no comparador quando a rota da v3 existir.

Bônus medido: `q=273134` devolve 0 nas duas rotas do v2, apesar de o prefixo existir nos
telefones — o mascaramento quebra a busca por prefixo. A v3 tem `busca_digitos` justamente
para isso; a diferença também é classe *resultado*.

## Números por consulta (v2, 12/11/2026)

`/api/contatos?q=` (LIKE, sem limite) e `/api/busca?q=` (FTS, teto 20):

| Termo | contatos | busca |
| :--- | --: | --: |
| *(vazio)* | 1.807 | — |
| `cartório` | 209 | 20 |
| `cartorio` | 0 | 20 |
| `vitória` | 166 | 20 |
| `vitoria` | 34 | 20 |
| `sede` | 286 | 1 |
| `atendimento` | 781 | 18 |
| `juizado` | 127 | 11 |
| `protocolo` | 37 | 20 |
| `civel` | 19 | 20 |
| `distribuicao` | 4 | 9 |
| `vara do trabalho` | 0 | 20 |
| `cartorio de registro` | 0 | 20 |
| `firma` | 0 | 0 |
| `imobiliario` | 0 | 0 |
| `273134` | 0 | 0 |

A linha `cartorio` × `cartório` e a coluna `busca` saturada são o retrato da armadilha 3 e 4.

## Critério de aceite

1. `comparar-contrato.py` sem nenhuma linha **DIFERENTE** nas rotas de paridade estrita.
2. Toda diferença em rota *resultado* tem uma linha de justificativa neste documento, com
   número medido (não "melhorou").
3. `setores` não entra no aceite: o modelo mudou.
4. Contagem de contatos por comarca, fórum e unidade idêntica — é o que o usuário conta na
   tela.

## Como rodar

```bash
# 1. subir o v2 (na raiz do repositório do v2)
SIMULACAO_AUTO_APROVAR=1 ADMIN_EMAILS=dono@exemplo.jus.br PORT=8011 python3 app.py

# 2. capturar o fixture (neste repositório)
python3 scripts/capturar-contrato.py http://127.0.0.1:8011 docs/paridade/esperado.json

# 3. quando a v3 responder, capturar de novo e comparar
python3 scripts/capturar-contrato.py http://127.0.0.1:8000 /tmp/esperado-v3.json
python3 scripts/comparar-contrato.py docs/paridade/esperado.json /tmp/esperado-v3.json
```

Modo v3 (captura ao vivo, sem fixture intermediário):

```bash
# a v3 no ar, com o seed mascarado do v2
python3 scripts/comparar-contrato.py --v3 http://127.0.0.1:8000

# quando todas as rotas de leitura existirem, rota pendente também reprova
python3 scripts/comparar-contrato.py --v3 http://127.0.0.1:8000 --exigir-completo
```

Autoteste (fixture contra ele mesmo, tem de dar PASS em tudo):

```bash
python3 scripts/comparar-contrato.py docs/paridade/esperado.json docs/paridade/esperado.json
```

## Lado da v3 — modo `--v3` (issue #8)

O comparador passou a falar com a v3 no ar. Ele captura o que a v3 **já expõe**, no mesmo
formato do fixture, e compara só os campos cobertos — o que falta vira `PENDENTE`, não
diferença escondida:

| Rota | Estado na v3 | O que é comparado |
| :--- | :--- | :--- |
| `hierarquia` | **completa** | `foruns_total`, `locais`, `nos_total`, `ordem`, `uteis` |
| `comarcas` | **parcial** | `total` e `ordem`, derivados de `/api/v1/hierarquia`; `por_comarca` ainda não tem rota |
| `busca` | **parcial** | `total` de cada uma das 16 consultas do fixture |
| `foruns` | **pendente** | falta `/api/v1/locais?comarca=` |
| `unidades` | **pendente** | falta `/api/v1/locais/{chave}/nos` |
| `contatos` / `contatos_q` | **pendente** | falta `/api/v1/contatos` |
| `setores` | **sem equivalente** | árvore única de nós no v3 |

O recorte é declarado em `COBERTURA_V3`, ao lado de `CLASSES`: o comparador não pode
anunciar paridade que a v3 não tem.

### Recall da busca, medido em 25/11/2026

O fixture do v2 guarda o `total` **saturado no limite de 20** (armadilha 4). A v3 conta
todas as ocorrências; por isso a coluna v3 quase nunca bate com a v2, e isso é esperado:

| Termo | v2 | v3 | Por quê |
| :--- | --: | --: | :--- |
| `cartorio` / `cartório` | 20 | 154 | v2 satura em 20; v3 conta tudo e normaliza acento |
| `atendimento` | 18 | 119 | idem |
| `civel` | 20 | 125 | idem |
| `juizado` | 11 | 148 | idem |
| `protocolo` | 20 | 37 | idem |
| `distribuicao` | 9 | 21 | idem |
| `vitoria` / `vitória` | 20 | 71 | idem |
| `273134` | 0 | 17 | v2 mascara o telefone e perde o prefixo; v3 busca nos dígitos |
| `a` | 20 | 0 | v3 descarta palavra com menos de 3 letras (`termos()`), então a consulta fica vazia; v2 casa tudo |
| `cartorio de registro` | 20 | 1 | v2 é OR; v3 exige todos os termos |
| `vara do trabalho` | 20 | 9 | idem |
| `sede` | 1 | 8 | v3 indexa nós e unidades, não só contatos |
| `firma` / `imobiliario` | 0 | 0 | iguais |

Essas diferenças são **decisão**, não regressão: por isso `busca` é classe *resultado*.
Quando o `limite` alto for comparado por conjunto de chaves — e não por total — a linha
`cartorio` ainda vai divergir por saturação do v2, e a justificativa continua esta.

### Prova do critério de aceite

Com a v3 local (seed real, 1.807 contatos):

```text
$ python3 scripts/comparar-contrato.py --v3 http://127.0.0.1:8013
hierarquia     PASS       estrita (completa)
comarcas       PASS       estrita (parcial 2/2 campos)
busca          RECALL     resultado (total por consulta) — 14 diferença(s)
foruns         PENDENTE   estrita — rota /api/v1/locais?comarca= ainda não existe
...
RESULTADO: ok          # exit 0
```

Diferença plantada de propósito (`nos_total` 856 → 857 num servidor falso):

```text
hierarquia     DIFERENTE  estrita (completa) — 1 diferença(s)
                hierarquia.nos_total: 856 × 857
RESULTADO: falhou      # exit 1
```

### CI

O job `contrato` vive no `testes.yml` e roda **a pedido** (`workflow_dispatch`), como o
`seed`: clona o v2, sobe a v3 com o seed real e compara. Ele ainda **não bloqueia** —
falta a v3 expor `locais`, `nos` e `contatos`. No dia em que as rotas existirem, o
`--exigir-completo` vira o padrão e o job entra no push/PR.

## O que este documento não cobre

- Rotas de escrita, autenticação, auditoria e administração. A paridade ali é de
  comportamento e já está especificada no `fase-3-arquitetura-e-adrs.md`.
- A diferença de 286 registros entre v1 e v2: **caso fechado em 12/11/2026**, e não por
  comparação com o v1. Os 286 são o bloco da estrutura central (Sede), que o v2 passou a
  incluir em `/api/contatos` por decisão registrada (opção C, `939311e`); o catálogo de
  ramais da Sede de 28/08/2026 tem exatamente **286 contatos em 179 nós**, e a
  reconciliação de 12/11/2026 confirmou 177 deles por documento (1 ausente, 1 parcial, por
  diferença de rótulo). Ou seja: 1.521 das comarcas + 286 da Sede = 1.807, conforme a fonte
  oficial, não conforme o v1. Ver `docs/reconciliacao-2026-11-12.md`.
