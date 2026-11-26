# Pré-mortem do item 3 — resolução (26/11/2026)

**Material enviado sobre:** `c290fc5` (tip do PR #47, fora da main) e `fcdb719` (morto no v2) —
pedido de 26/11/2026.
Base: `diretorio-contatos-v3` em `724e28e`/`a99256f` (main) e `diretorio-contatos-v2` em `bb9b62b`.
O texto do pré-mortem de 26/11 foi escrito sobre `c290fc5` e `fcdb719`; a moldura foi corrigida, o
miolo não.

## Riscos

1. **Critério do item 3 apontava para testes do item 6.** Resolvido no `docs/plano-v3.md` §9: o item 3
   entrega Policy + testes sem HTTP; a paridade dos 36 casos do v2 está em
   `docs/paridade-testes-v2.md`, com o item de destino de cada um.
2. **Uma regra, dois usos.** `EscopoResolvido` é resolvido uma vez por requisição (`app->scoped`),
   transforma chaves em ids e é consumido pela `DiretorioPolicy` e por
   `Contato::editaveisPor()`/`visiveisPor()`. Teste de contagem de consultas em `EscopoTest`.
3. **Escalada por mass assignment.** `Usuario::$fillable` só tem `nome`, `email` e `senha`;
   `papel` e `ativo` só mudam por `CriarUsuario`, `AlterarPapel` e `DefinirAtivo`; o
   `AppServiceProvider` liga `Model::preventSilentlyDiscardingAttributes()`; teste em `EscopoTest`.
4. **Sede e chave órfã.** `ConcederAcesso` valida a existência da chave na tabela do nível:
   `MUN0000` é aceita como `local` (Sede) e recusada como `comarca`; `FOR9999`/`UNI9999` falham
   fechado. Coberto em `EscopoTest` (mesma regra do `TestHerancaSede` do v2).
5. **Prova de identidade sem OAuth.** O login do `v3.0` é e-mail/senha no `/admin` do Filament; o
   pedido é anônimo (nome/e-mail) e a aprovação do admin é a prova. O SMTP entrou no `render.yaml`
   com `sync: false`; sem host/credenciais o envio falha alto, não finge que mandou.

## Contradições

- **§3.4 "vê" × D-1 "demo vê tudo" × `_cobre` "pode editar".** Vence o v2: o escopo decide o que
  **edita**; a leitura pública não é escopada. §3.4 reescrito.
- **`GESTOR_COMARCA` sem regra.** Decisão do dono (26/11): pode editar, além do admin, limitado por
  escopo. Mesma regra de `EDITOR`; o que muda é o papel, não a autorização.
- **§9 critério × título do item.** Vence o título: Policy e testes sem HTTP; pedidos e Filament no
  item 6.
- **§5.5/§11 "favoritos já migrada" × migrations.** Vencem as migrations: a tabela não existe; o
  favorito do demo é `localStorage`, e a tabela entra com a sincronização, depois do `v3.0`.
- **COMMENT de `historico` × auditoria inexistente.** Criada a tabela `auditoria` (ator, ação,
  entidade, antes/depois em JSONB), fora do expurgo de 180 dias; `RevogarAcesso` deixa o retrato.
- **Senha/OAuth × D-1 e #30.** Vence D-1: sem OAuth no `v3.0`; o comentário da migration foi
  corrigido para "hash do Laravel; login e-mail/senha no Filament".
- **R5 da rodada anterior.** O `DiretorioSeeder` usa `updateOrCreate` por linha **de propósito**
  (recarga idempotente do seed); o risco está no código entregue, não no diagnóstico.

## O que fica fora do item 3

Pedidos, recusa, aprovação, usuários, backup e as telas do Filament são item 6. A ordem de retomada
continua: `GET /api/v1/locais` → comparador `contatos`/`contatos_q` → fechar a issue #43; o item 3
não bloqueia essa fila.
