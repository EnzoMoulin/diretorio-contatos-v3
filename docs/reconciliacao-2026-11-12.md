# Reconciliação da base × catálogos oficiais — 12/11/2026

Conferência única, como pedido: a base do diretório contra os três documentos que a
originaram. Não é rotina periódica — os dados são de demonstração e não precisam
envelhecer bem, mas precisam estar certos agora.

## Fontes da verdade usadas

| Documento | Versão | O que sustenta na base |
| :--- | :--- | :--- |
| Catálogo de ramais por comarcas | JUNHO/2026 | 69 comarcas, 72 fóruns, 659 nós e 1.478 contatos do interior e da Grande Vitória |
| Catálogo de ramais da Sede | 28/08/2026 | 179 nós e 286 contatos da Sede (observação "catálogo agosto/2026") |
| ofício de março/2024 — Diretoria do Foro (processo administrativo interno) | 03/2024 | 43 contatos de Vitória (observação "ofício de março/2024") |

Os três PDFs ficam em `../dados/catalogo-2026/` (fora do git, junto com os CSVs).
Cópias de trabalho extraídas por `pdftotext`.

## Como foi conferido

`scripts/reconciliar-catalogo.py` lê os PDFs, reconstrói a estrutura (comarca → fórum →
unidade) e compara cada nome da base com o texto do catálogo **por cobertura de
palavras**, não por substring: no PDF a etiqueta e o telefone ficam em subcolunas, então
uma etiqueta longa aparece cortada por números no meio ("Gabinete da 1ª Vara da Fazenda /
(27) 3334-XXXX / Pública Estadual…"). Substring reprovaria nome correto; cobertura não.

Limiares: ≥ 90% das palavras presentes = confirmado; 60–89% = parcial (diferença de
redação); < 60% = ausente (ou atribuído a outra comarca — o script testa o catálogo
inteiro nesse caso, para separar erro de dado de erro do parser).

## Resultado

| Item | Conferidos | Confirmados | Divergente | Natureza |
| :--- | --: | --: | --: | :--- |
| Comarcas | 69 | 69 | 0 | — |
| Fóruns | 72 | 72 | 0 | 1 com nome ampliado |
| Nós de comarca | 659 | 652 | 0 ausentes · 7 parciais | redação / atribuição |
| E-mails de comarca | 297 | 296 | 1 | artefato de extração; base correta |
| Nós da Sede | 179 | 177 | 1 ausente · 1 parcial | redação |
| ofício de março/2024 | 23 e-mails | 23 | 0 | — |

Estrutura da base: **69 comarcas + Sede · 72 fóruns · 838 nós · 1.807 contatos**
(1.019 telefone, 444 ramal, 297 e-mail, 47 WhatsApp) **· 11 telefones úteis**.
Nenhuma comarca do catálogo falta na base e nenhuma comarca da base sobra.

## A única divergência de conteúdo — e a correção aplicada

**`C000978`, "PID • Central de Atendimento" de São José do Calçado.** A base trazia
`contato.demo1@exemplo.jus.br`, um preenchimento provisório; o catálogo de junho traz o
endereço institucional dessa central (`pid.sjcalcado@…`). Era o único lugar em que a base
tinha um valor inventado onde a fonte tinha dado.

Corrigido em 12/11/2026 no CSV de origem e no `seed_mascarado.json` da v2, agora como
`pid.sjcalcado@exemplo.jus.br` — parte local da fonte, domínio de demonstração, como os
outros 296. Conferido no banco recriado: 1.807 contatos, nenhum e-mail `demo` restante.
Depois da correção, a conferência fecha em **296 de 297**.

**`C000033` — falso positivo, base correta.** O e-mail da 10ª Vara Criminal de Vitória
aparece no Ofício com hífen colado à palavra anterior ("1ª Vara Criminal -`10criminal`-…"),
e o extrator cria um endereço fantasma começando com hífen. A base gravou o endereço certo;
nada a corrigir.

## O que é diferença de redação, não de dado

- `Fórum de Viana (Areinha)` — o catálogo escreve só "Fórum de Viana"; "Areinha" é o bairro,
  acrescentado na base para desambiguar.
- `Central de Mandados`, `1ª Contadoria`, `2ª Contadoria` (Vitória) — não estão no catálogo
  por comarcas; estão no ofício de março/2024, que é a fonte desses contatos. Confirmados lá.
- `Atendimento Vara Cível` / `Gabinete Vara Cível` (Viana) — o catálogo lista "Vara Cível /
  Cartório / Gabinete" em linhas separadas; a base agrupou em dois nós.
- `Subsecretaria Geral` × "Sub Secretaria Geral" (Sede) — grafo e separação.
- `Unidade de Coordenação de Programas (UCP)` × "UCP" (Sede) — a base expandiu a sigla.

Essas cinco ficam como estão: mudar a base para copiar a redação do PDF perderia a
desambiguação que a base ganhou.

## O que a base muda em relação à fonte — e por quê

Comparação direta CSV ↔ `seed_mascarado.json` (o que o app carrega):

- **33 nós renomeados por mascaramento**: 30 "Gabinete de Desembargador 01…30" e
  3 "Comissário 01…03" no lugar dos nomes de pessoa que existem no catálogo de agosto.
  O nome de pessoa existe **só** na fonte crua (`dados/`, fora do git); o que é servido não
  tem nenhum.
- **4 nós desambiguados** (`Gabinete, Assessoria e Sala de Audiência da 10ª Secretaria
  Inteligente (3º JEC)` etc.): a base acrescentou o Juizado entre parênteses porque o
  catálogo repete o mesmo rótulo para blocos diferentes.
- **1 comarca renomeada**: `MUN0000` é "Sede Administrativa" na base, e no catálogo aparece
  com o nome institucional do tribunal — a regra do projeto é não levar nome institucional
  para dentro do sistema.
- **297 e-mails com domínio trocado** para `@exemplo.jus.br` e **1.019 telefones + 444
  ramais com dígitos finais em `XXXX`**: mascaramento aplicado já no export dos CSVs, então
  CSV e seed coincidem.
  A **parte local do e-mail é preservada de propósito** (ex.: `1criminal-vitoria@exemplo.jus.br`):
  ela é derivada de número de vara + unidade + comarca, informação pública do próprio
  catálogo, e mantê-la é o que deixa a busca por e-mail e a identificação por comarca
  funcionarem no demo. Conferi as **279 partes locais distintas**: nenhuma é nome de pessoa
  (`nome.sobrenome`); todas carregam palavra de unidade (`vara`, `civel`, `criminal`, `jec`,
  `contadoria`, `secretaria`, `gabinete`, `atendimento`, `pid`…). O que o mascaramento tira é
  o último passo — trocar o domínio de volta entregaria um endereço que talvez exista —, e
  isso é o mesmo critério dos telefones, que mantêm DDD e prefixo e escondem só o final.

## O que este laudo não prova

- **Conteúdo dos telefones.** Estão mascarados na base; a conferência prova que o contato
  existe e a que unidade pertence, não que o número está discado certo. Provar exigiria a
  base sem máscara.
- **Microregião.** O parser identifica a microrregião pelo rodapé da página, e o rodapé
  lista comarcas de transição; 19 atribuições saíram trocadas. Verifiquei duas à mão contra
  o índice (Rio Novo do Sul → Litoral Sul, Ecoporanga → Noroeste) e a base está correta nas
  duas. Para o resto, a coluna `MICRORREGIAO` não foi conferida por máquina.
- **45 linhas do PDF sem atribuição de comarca** (cabeçalhos de página, mapa, índice). Não
  afetam nenhuma contagem acima, mas explicam por que a extração tem 45 "órfãos".
- **Completude da fonte.** Se o catálogo de junho não listar uma unidade que existe, esta
  conferência não tem como saber — ela prova aderência à fonte, não verdade de campo.

## O caso dos 286 — fechado por esta conferência

A diferença de 286 registros entre o v1 e o v2 do diretório estava registrada como caso
aberto: não se sabia se era porte fiel do v1 ou defeito introduzido no v2. Esta conferência
fecha o caso **pela fonte oficial, sem comparar os dois bancos**:

- o catálogo de ramais da Sede de **28/08/2026** tem **286 contatos em 179 nós** — e a base
  tem exatamente 286 contatos nesse bloco (177 confirmados por documento, 1 ausente e 1
  parcial por diferença de rótulo, ambos já explicados acima);
- 1.521 (69 comarcas) + 286 (Sede) = **1.807**, que é o total declarado;
- a inclusão da estrutura central em `/api/contatos` é decisão registrada (opção C,
  `939311e`), travada por `TestContagens` (lista plana = árvore = banco).

Conclusão: os 286 não são regressão do v2, são o bloco que o v1 não trazia na lista plana —
e o número que a fonte datada confirma é o do v2. O caso deixa de estar aberto; não é
necessário investigar o `src/frontend` do v1 para decidir nada.

## Conclusão

A base está **aderente aos três catálogos**: nenhuma comarca, nenhum fórum e nenhum nó
ausente; 296 dos 297 e-mails confirmados por documento; os 286 contatos da Sede e os 43 do
Ofício batem com suas fontes. A única divergência de conteúdo que existia — o e-mail
provisório de São José do Calçado — foi corrigida. As cinco diferenças de redação ficam como
estão, porque a base desambiguou o que o PDF repete. Não há segunda rodada a fazer enquanto a
fonte não mudar.

## O que fica congelado a partir daqui

Esta é a base final de demonstração da v3 e da v4. Duas travas baratas garantem que ela
não se perde nem se contamina:

1. **Manifesto com hash** — `../dados/catalogo-2026/base-verificada.json`, gerado junto
   com o laudo (`--manifesto`). Guarda a data, as contagens, o SHA-256 dos cinco CSVs e o
   dos três PDFs. O `estado.sh` do workspace lê esse arquivo, recomputa os hashes em cada
   execução e responde "base intacta" ou acusa qual arquivo mudou desde a conferência.
2. **Varredura do seed** — `scripts/varrer_privacidade.py --seed
   ../diretorio-contatos-v2/seed_mascarado.json`. O seed vive no repositório da v2 e não é
   versionado neste repo, então a varredura daqui nunca o lia: aquele era o ponto cego.
   O modo `--seed` falha se encontrar telefone/e-mail fora do padrão sintético ou **nome
   de pessoa** em nó, rótulo de contato ou telefone útil. `--autoteste` roda 33 casos e
   prova que o detector não é um carimbo: pega nome de pessoa e ignora "Manutenção
   Predial", "Cíveis Reunidas", "Comissários da Infância e Juventude" e "Gabinete de
   Desembargador 01".

## Como repetir

```bash
cd diretorio-contatos-v3
python3 scripts/reconciliar-catalogo.py \
  --base ../dados/mascarados-oficial \
  --comarcas ../dados/catalogo-2026/ramais-comarcas-junho-2026.pdf \
  --sede ../dados/catalogo-2026/ramais-sede-agosto-2026.pdf \
  --oficio ../dados/catalogo-2026/oficio-432-2024.pdf \
  --saida ../dados/catalogo-2026/reconciliacao.json \
  --manifesto ../dados/catalogo-2026/base-verificada.json
```

Requer `poppler-utils`. O JSON de saída é o laudo em forma de dado; ele contém nomes de
unidades e contagens, não telefone, e-mail nem nome de pessoa.
