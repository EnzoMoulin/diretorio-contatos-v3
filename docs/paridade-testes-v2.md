# Paridade dos testes do v2 — o que vai para cada item do v3.0

Base medida: `diretorio-contatos-v2` em `bb9b62b` (26/11/2026). São **36 casos**: 20 em
`test_regras.py`, 8 em `test_seguranca_demo.py`, 5 em `test_conferencia.py` e 3 em
`test_somente_leitura.py`.

Regra de leitura: o item 3 porta **só os casos de Policy**, sem HTTP. O que depende de rota, sessão,
Filament, pedido ou resposta JSON fica no item 6 (admin) ou no item 4 (API), e não vira critério de
pronto do item 3. Nenhum teste do v2 é copiado literalmente: o v3 testa a mesma regra na camada que
existe.

## `test_regras.py` — 20 casos

| Caso | O que prova | Destino |
|---|---|---|
| `TestIdsGlobais::test_prefixos` | formato das chaves lógicas (MUN/FOR/UNI/C/UTL) | item 2 (DDL/CHECK); contrato no item 4 |
| `TestIdsGlobais::test_sem_orfaos` | nenhuma FK aponta para linha inexistente | item 2 (seed + conferência) |
| `TestPedido::test_sem_sessao_401` | pedido sem sessão é recusado no v2 | item 6 — **rever**: no v3 o pedido é anônimo (nome/e-mail) |
| `TestPedido::test_sem_nome_400` | pedido exige nome | item 6 |
| `TestPedido::test_admin_pede_admin_400` | pedir papel ADMIN pelo corpo não escala | item 3 (`EscopoTest`: mass assignment) + item 6 |
| `TestPedido::test_cadeia_completa_com_limpeza` | pedido → aprovação → expurgo | item 6 |
| `TestHeranca::test_unidade_cobre_setor_sem_vazar` | acesso na unidade cobre o setor e não o vizinho | **item 3** |
| `TestHerancaSede::test_cadeia_do_setor_sem_forum_vem_da_unidade` | setor da Sede herda da unidade | **item 3** |
| `TestHerancaSede::test_acesso_na_unidade_sem_forum_cobre_o_setor` | acesso na unidade sem fórum cobre o setor | **item 3** |
| `TestContagens::test_lista_plana_soma_a_arvore_e_o_banco` | contagem da lista plana bate com a árvore | item 2/4 |
| `TestContagens::test_lista_plana_sem_contato_sem_comarca` | contato sem comarca não some da lista | item 2/4 |
| `TestContagens::test_comarcas_bate_com_a_lista` | contagem de comarcas bate | item 2/4 |
| `TestRecusa::test_recusa_exige_motivo` | recusa sem motivo é inválida | item 6 — CHECK `pedidos_recusa_motivo` já no item 2 |
| `TestRecusa::test_recusa_com_motivo` | recusa com motivo é aceita | item 6 |
| `TestMeusPedidos::test_meus_pedidos_mostra_recusa_com_motivo` | o autor vê o motivo da recusa | item 6 |
| `TestBuscaBackup::test_busca_fts_com_highlight` | busca com trecho e destaque, sem HTML | item 4 |
| `TestBuscaBackup::test_backup_gera_arquivo` | backup gera arquivo baixável | item 6/7 |
| `TestBuscaBackup::test_login_faz_upgrade_para_pbkdf2` | login atualiza o hash antigo | item 6 — no v3 o hash é do Laravel desde o início |
| `TestUsuarios::test_lista_e_ativo` | lista de usuários e ativo | item 6 |
| `TestHistoricoAutor::test_autor_registrado_na_criacao` | autor entra no histórico na criação | item 6 |

## `test_seguranca_demo.py` — 8 casos

| Caso | O que prova | Destino |
|---|---|---|
| `TestAdminPorAllowlist::test_admin_fora_da_allowlist_fica_pendente` | e-mail fora da allowlist não vira admin | item 6 |
| `TestAdminPorAllowlist::test_admin_na_allowlist_recebe_admin` | e-mail da allowlist vira admin | item 6 |
| `TestGestaoFechada::test_visitante_barrado_nas_rotas_de_gestao` | visitante não entra na gestão | item 6 |
| `TestGestaoFechada::test_visitante_nao_aprova_nem_recusa` | visitante não decide pedido | item 6 |
| `TestGestaoFechada::test_visitante_nao_baixa_backup` | visitante não baixa backup | item 6/7 |
| `TestGestaoFechada::test_visitante_segue_com_as_proprias_rotas` | visitante continua na consulta pública | item 4/5 |
| `TestGestaoFechada::test_admin_continua_lendo_tudo` | admin lê tudo, mesmo sem escopo | **item 3** (`EscopoTest`: admin) |
| `TestNomeDeUsuarioNaoEscala::test_usuario_do_corpo_e_ignorado` | `usuario` no corpo não troca a identidade | **item 3** (`EscopoTest`: mass assignment) |

## `test_conferencia.py` — 5 casos

| Caso | O que prova | Destino |
|---|---|---|
| `TestConferir::test_sem_sessao_403` | conferir exige sessão | item 6 |
| `TestConferir::test_contato_inexistente_404` | contato inexistente não confere | item 6 |
| `TestConferir::test_sem_escopo_403` | conferir fora do escopo é negado | **item 3** (Policy) + item 6 |
| `TestConferir::test_admin_confere_e_aparece_na_hierarquia` | admin confere e o resultado aparece | item 6 |
| `TestConferir::test_historico_registra_a_conferencia` | conferência entra no histórico | item 6 |

## `test_somente_leitura.py` — 3 casos

| Caso | O que prova | Destino |
|---|---|---|
| `TestSomenteLeitura::test_pedido_de_comarca_fica_pendente` | pedido de comarca não é autoaprovado | item 6 |
| `TestSomenteLeitura::test_visitante_nao_edita_contato` | visitante não edita contato | **item 3** (`EscopoTest`: demo) + item 6 |
| `TestSomenteLeitura::test_me_declara_aprovacao_automatica_desligada` | `me` não declara aprovação automática | item 6 |

## O que o item 3 cobre de fato

- herança comarca → local → nó (incluindo Sede `MUN0000` como local);
- `ADMIN` edita tudo; `GESTOR_COMARCA`/`EDITOR` editam dentro do escopo; `DEMO` lê tudo e não escreve;
- `MUN0000` recusada como comarca, aceita como local; chave órfã recusada;
- `Contato::editaveisPor()` e `visiveisPor()` com a mesma resolução da Policy;
- `papel`/`ativo` fora do mass assignment, com Actions explícitas e auditoria.

Os casos de pedido, recusa, usuários e backup e os 5 de conferência com HTTP continuam no item 6.
