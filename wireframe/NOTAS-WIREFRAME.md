# Wireframe do v3 — notas

Protótipo descartável para validar a **direção A** (mapa protagonista + paleta escura) antes de
qualquer implementação real. Não é o v3: nada aqui é reaproveitado por `apps/web`.

## Como rodar

```bash
cd wireframe
npm install && npm run build
cp .env.example .env && php artisan key:generate   # se ainda não houver .env
php artisan serve --host=127.0.0.1 --port=8123
```

## Rotas

| Rota | Estado mostrado |
|---|---|
| `/` | mapa + lista de comarcas (Sede primeiro) |
| `/comarca/{chave}` | comarca ampliada, polígono destacado, métricas e fóruns |
| `/local/{chave}` | fórum ou unidade com seus nós e contatos |
| `/contato/{chave}` | valor grande, copiar, `tel:`/`mailto:` |
| `/busca?q=` | busca com contexto do resultado |
| `/municipio/{ibge}` | município sem comarca declarada |
| `/?mapa=falha` · `/?mapa=lento` | mapa fora do ar · carregamento lento |

## Dados

- `data/seed.json` — cópia mascarada de `seed_mascarado.json` do v2 (1.807 contatos, 69 comarcas).
- `data/es-municipios.geojson` — malha municipal do IBGE (UF 32, qualidade intermediária).
- `data/municipios.json` — nomes oficiais dos 78 municípios (IBGE localidades). A malha traz só
  `codarea`; os nomes vêm desta lista e **não** de dedução.
- Sem banco: `App\Support\Diretorio` lê o seed e indexa em memória a cada requisição.

## Regras que o wireframe prova

- **Não inventar dado:** município sem comarca mostra o nome, o código do IBGE e o que falta —
  nunca uma comarca deduzida.
- **Procedência é estrutura**, não rodapé: o selo de vigência só aparece quando o seed declara a data.
- **Celular primeiro:** folha inferior de três alturas (recolhida/meia/cheia), alvo de toque ≥ 44 px,
  mapa reenquadrando a cada mudança de altura.
- **Disco de densidade** por comarca (área ∝ contatos) sobre o polígono, que fica como silhueta.

## Descartável, de propósito

- Índices em memória, sem paginação e sem cache.
- Transições e animações simplificadas; nenhuma regra de autorização.
- `window.__mapa` existe só como gancho de inspeção das capturas.
