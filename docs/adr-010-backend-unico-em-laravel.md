# ADR-010 — backend único em Laravel, sem serviço FastAPI

- **Status:** aceito em 14/11/2026; registrado como arquivo em 25/11/2026 (antes, existia só como Anexo A do `plano-v3.md`).
- **Substitui:** o ADR-008 na parte do serviço FastAPI e o marco "v4".
- **Plano vigente:** `plano-v3.md`.

## Contexto

A versão anterior (Python, biblioteca padrão e SQLite) validou o contrato de rotas, o modelo de dados e
a suíte de testes. Havia um plano de extrair, depois do Laravel, um segundo serviço em FastAPI para
importação com limpeza de texto, deduplicação e busca fonética. O diretório tem cerca de 1,8 mil
contatos, poucas escritas, um único mantenedor e hospedagem gratuita com partida a frio.

## Decisão

Um único aplicativo Laravel (monólito modular; no código, as fronteiras são `app/Escopo`, `app/Actions` e `app/Policies`, antes descritas como módulos `Directory` e `Access`). Não há serviço Python
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
requisição. As fronteiras entre escopo e acesso, e o uso de `chave` como chave lógica, mantêm uma
extração possível.

## Consequências no DDL (25/11/2026)

O `db/schema.sql` foi escrito para dois serviços e carregava três coisas que só existiam por causa
disso; todas saíram ou foram corrigidas antes de o arquivo virar baseline das migrations:

- `referencias_externas` — tabela-espelho para o motor saber o que o Laravel referenciava. Com um
  banco e um dono, o expurgo consulta o app direto: não há JOIN entre serviços a evitar.
- `idempotencia` — `Idempotency-Key` de escrita da API do motor. A `/api/v1` da v3 é de leitura.
- seção de papéis: `diretorio_motor` + `diretorio_espelho` viram um papel só, `diretorio_app`.
- os comentários de `historico` deixam de dizer que `usuario`/`escopo_acesso`/`pedido` "vivem em
  `db_admin`": essas tabelas são do app, criadas por migrations do Laravel no **mesmo** banco, e a
  auditoria de permissão não é histórico de dado (não expira aos 180 dias).

O baseline fica com sete tabelas — seis de dado (`comarcas`, `locais`, `nos`, `contatos`,
`telefones_uteis`, `historico`) e o agregado do expurgo (`historico_mensal`).
`db/verificar-schema.sql` acompanha: 65 `PASS` e `OK` em `db/testar-schema.sh` com o seed do v2
(45 invariantes estruturais + 20 de carga + 5 de plano).
