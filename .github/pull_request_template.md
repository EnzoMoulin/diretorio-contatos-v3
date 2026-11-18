<!-- Preencha todas as seções; o que não se aplica, escreva "n/a" — não apague o título. -->

## Resumo

<!-- O que este PR resolve, em 1–3 frases, no presente do indicativo:
     "A rota /api/v1/locais passa a devolver lat/lon aproximado por CEP." -->

## Issue

Closes #

<!-- `Closes #12` fecha a issue quando o merge entra; `Refs #12` só referencia.
     PR sem issue: explique aqui por que não havia problema a resolver. -->

## Alterações principais

-

## Por que assim (trade-offs)

<!-- A parte que a revisão de código não mostra: qual alternativa foi descartada e por quê,
     o que fica pior com esta escolha, o que ficou fora do escopo de propósito.
     Duas frases honestas valem mais que um parágrafo de justificativa. -->

## Validação

<!-- O CI roda o mesmo; aqui vai o que foi rodado na mão e o resultado medido. -->

- [ ] `python3 scripts/varrer_privacidade.py` —
- [ ] `db/testar-schema.sh` (com `SEED_JSON` quando a mudança toca carga) —
- [ ] `vendor/bin/pint --test` — *(quando `apps/web` existir)*
- [ ] `vendor/bin/phpstan analyse` — *(quando `apps/web` existir)*
- [ ] `php artisan test` — *(quando `apps/web` existir)*
- [ ] Diff completo lido (não só o que a suíte cobre)
