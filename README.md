# Diretório de Contatos — v3 (Laravel + TALL)

> Terceira fase do mesmo produto das versões [v1](https://github.com/EnzoMoulin/diretorio-contatos)
> (Apps Script) e [v2](https://github.com/EnzoMoulin/diretorio-contatos-v2) (FastAPI): consulta
> pública de contatos de fóruns e unidades, com backoffice. Projeto de portfólio;
> **todos os dados são fictícios** — ver `LICENSE`.

## Status

**Fase de fundação, não de código.** O que existe aqui é o que se prova antes da primeira
linha de Laravel: o modelo físico do banco, o desenho de deploy, a direção visual e as
decisões registradas. O esqueleto da aplicação (migrations, models, Livewire, Filament)
ainda não começou — quando começar, entra por este README.

| Já feito | Onde |
| :--- | :--- |
| Decisão de stack: **Laravel 13 + TALL + Filament + Postgres (Neon), um serviço Docker no Render free** | `docs/arquitetura-v3.md` |
| DDL completo e idempotente do banco `db_diretorio`, com invariantes e expurgo LGPD | `db/schema.sql`, `db/README.md` |
| Direção visual escolhida: **A — mapa protagonista**, paleta slate + turquesa | `docs/identidade-visual.md` |
| Blueprint de deploy e imagem Docker (nginx + php-fpm, migrations no entrypoint) | `render.yaml`, `Dockerfile`, `docker/` |
| CI: varredura de privacidade + DDL em Postgres 16 real | `.github/workflows/testes.yml` |

## Stack

- **Laravel 13 / PHP 8.4** serve HTML e JSON do mesmo processo (Blade + Livewire).
- **Tailwind + Alpine** no front; **Filament** no backoffice — é o que fecha a lacuna de
  Laravel no portfólio sem construir dois backends.
- **Postgres no Neon free**, fora do Render: o Postgres free do Render expira em 30 dias e o
  demo quebraria sozinho.
- **FastAPI como satélite** só na v3.1, quando houver medição que justifique.

## Rodar o que existe

```bash
# estrutura do banco (cria <base>_teste, aplica o DDL duas vezes, roda as invariantes)
db/testar-schema.sh

# com o seed da v2 ao lado (1.807 contatos, 838 nós) — os repos são irmãos por desenho
SEED_JSON=../diretorio-contatos-v2/seed_mascarado.json db/testar-schema.sh

# imagem
docker build -t diretorio-contatos-v3 .
```

O script só age em banco terminado em `_teste`, então produção nunca é atingida por acidente.
Detalhe de cada arquivo de `db/`, pegadinhas já pagas e a medição que fixou o limiar de
similaridade em 0,45: `db/README.md`.

## Deploy

Blueprint do Render em `render.yaml`; segredos (`APP_BASE_URL`, `GOOGLE_CLIENT_JSON`,
`ADMIN_EMAILS`) só no painel, nunca no repositório. Runbook completo em
`docs/fase-3-arquitetura-e-adrs.md`. Ainda não deployado.

## Documentos

| Documento | Conteúdo |
| :--- | :--- |
| `docs/arquitetura-v3.md` | Decisão de 10/11/2026: por que Laravel é a casca, por que o FastAPI fica para depois, o que acontece com o v2. |
| `docs/fase-3-arquitetura-e-adrs.md` | O documento longo: vocabulário normativo, modelo físico, ciclo de vida do dado (LGPD), protocolo de integração e os ADRs. |
| `docs/identidade-visual.md` | Direção A, mapa protagonista. Inclui o que **não** fazer: `direcoes-visuais.html` é instrumento de decisão, não markup. |
| `db/README.md` | O banco lógico: tabelas, invariantes, medições e as regras que ficam na aplicação. |

## Relação com as versões anteriores

O v2 não é tocado: continua público e congelado como evidência do arco v1→v2. O que se escreve
do zero é esta versão — e um dos critérios é não herdar código: a similaridade com o v1 fica
em ≤10%. A comparação lado a lado dos três está no README do v1 e do v2.

## Licença

Direitos reservados. Ver `LICENSE`.
