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
| Comparação | `scripts/comparar-contrato.py` | diff entre dois fixtures, com classe de paridade por rota |

O fixture **não guarda telefone, e-mail nem nome de pessoa**. Nomes de comarca, fórum e
unidade são públicos (`dados/README.md`); a amostra de contatos usa só `nome`/`cargo` já
mascarados como `NN` no seed.

## Baseline medido (v2 em 12/11/2026, seed mascarado)

| Medida | Valor |
| :--- | --: |
| Locais na árvore (`/api/hierarquia`) | 70 (69 comarcas + Sede) |
| Fóruns | 72 |
| Nós na árvore exposta | 838 |
| Úteis | 11 |
| Contatos | 1.807 |
| Sede | 286 contatos · 22 unidades · 166 setores |
| Vitória | 166 contatos · 2 fóruns |
| Vila Velha | 92 contatos |

O `nos_total` de 838 é o número que o v3 usa no README. Bate — e é a primeira coisa que a
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
   separadas; a v3 tem uma árvore única de 838 nós. O mapeamento não é 1:1 e não está
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

Autoteste (fixture contra ele mesmo, tem de dar PASS em tudo):

```bash
python3 scripts/comparar-contrato.py docs/paridade/esperado.json docs/paridade/esperado.json
```

## O que este documento não cobre

- Rotas de escrita, autenticação, auditoria e administração. A paridade ali é de
  comportamento e já está especificada no `fase-3-arquitetura-e-adrs.md`.
- A divergência de 286 registros entre v1 e v2, que segue aberta e é outra conferência (v1
  contra v2, não v2 contra v3).
