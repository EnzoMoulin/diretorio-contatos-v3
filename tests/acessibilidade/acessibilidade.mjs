#!/usr/bin/env node
/**
 * Gate de acessibilidade do Ramal — WCAG 2.1 AA medido em navegador real.
 *
 * A régua é executável, não relatório: `axe-core` roda as regras A/AA nas telas
 * públicas e três medições que o axe não cobre sozinho:
 *
 *   1. foco por teclado: Tab de verdade, comparando o estilo do elemento (e do
 *      pai, para o anel de `:focus-within`) com o estilo de repouso;
 *   2. alvo de toque ≥ 44 px num viewport de 390 px — o desenho é desktop-first,
 *      mas o celular é o aparelho de quem está no balcão;
 *   3. mapa com alternativa textual: quando a tela tiver mapa, ele precisa de
 *      `role="img"` + nome acessível ou de texto equivalente. Hoje nenhuma tela
 *      tem mapa; a regra fica armada para a tela que tiver.
 *
 * Qualquer violação sai com código 1. O relatório JSON (violações, paradas de
 * Tab e alvos pequenos) é gravado em `relatorio/` para virar artefato do CI.
 *
 * Uso:
 *   node acessibilidade.mjs [http://127.0.0.1:8000] [pasta-do-relatorio]
 *   PW_HEADED=1  mostra a janela
 */

import { chromium } from 'playwright';
import { AxeBuilder } from '@axe-core/playwright';
import { mkdirSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';

const BASE = (process.argv[2] || 'http://127.0.0.1:8000').replace(/\/$/, '');
const SAIDA = process.argv[3] || 'relatorio';
const PAGINAS = [
  { rota: '/', nome: 'capa' },
  { rota: '/ramais', nome: 'ramais' },
];

const falhas = [];
const relatorio = { base: BASE, gerado_em: new Date().toISOString(), paginas: [] };

function falha(mensagem) {
  falhas.push(mensagem);
  console.log('  FALHA ' + mensagem);
}

function ok(mensagem) {
  console.log('  ok    ' + mensagem);
}

function conferir(condicao, mensagem) {
  condicao ? ok(mensagem) : falha(mensagem);
}

/** Medidores injetados uma vez por página. Devolvem fatos, não veredito. */
const MEDIDORES = () => {
  const SELETOR = 'a[href], button, input:not([type="hidden"]), select, textarea, summary, [role="button"], [tabindex="0"]';

  const visivel = (el) => {
    const r = el.getBoundingClientRect();
    const cs = getComputedStyle(el);
    return r.width > 0 && r.height > 0 && cs.visibility !== 'hidden' && cs.display !== 'none';
  };

  const assinatura = (el) => {
    const partes = [];
    for (let no = el, nivel = 0; no && nivel < 3; no = no.parentElement, nivel += 1) {
      const cs = getComputedStyle(no);
      partes.push([cs.outlineWidth, cs.outlineStyle, cs.outlineColor, cs.boxShadow,
        cs.borderTopColor, cs.backgroundColor, cs.color, cs.transform].join('|'));
    }
    return partes.join('||');
  };

  return {
    /** Foto do estilo de repouso, com id estável no próprio DOM. */
    instantaneos() {
      let total = 0;
      for (const el of document.querySelectorAll(SELETOR)) {
        if (!visivel(el) || el.disabled) continue;
        el.dataset.a11yId = String(total);
        el.dataset.a11yRepouso = assinatura(el);
        total += 1;
      }
      return total;
    },

    focoAtual() {
      const el = document.activeElement;
      if (!el || el === document.body || el === document.documentElement) return { vazio: true };
      const cs = getComputedStyle(el);
      const texto = (el.innerText || el.textContent || el.getAttribute('aria-label') || '')
        .replace(/\s+/g, ' ').trim().slice(0, 40);
      return {
        id: el.dataset.a11yId ?? null,
        descricao: el.tagName.toLowerCase() + (el.id ? '#' + el.id : '')
          + (texto ? ' "' + texto + '"' : ''),
        focoVisivel: el.matches(':focus-visible'),
        mudou: el.dataset.a11yRepouso ? el.dataset.a11yRepouso !== assinatura(el) : null,
        largura: Math.round(cs.outlineWidth ? parseFloat(cs.outlineWidth) : 0),
      };
    },

    /**
     * Alvos de toque. Link no meio de um parágrafo ou de uma célula é texto,
     * não botão — a régua de 44 px vale para o que se toca para agir.
     */
    alvosPequenos(minimo) {
      const pequenos = [];
      let total = 0;
      for (const el of document.querySelectorAll(SELETOR)) {
        if (!visivel(el) || el.disabled) continue;
        if (el.tagName === 'A' && el.closest('p, li, td, th, figcaption')) continue;
        const r = el.getBoundingClientRect();
        total += 1;
        if (r.width < minimo || r.height < minimo) {
          const texto = (el.innerText || el.textContent || el.getAttribute('aria-label') || '')
            .replace(/\s+/g, ' ').trim().slice(0, 30);
          pequenos.push(`${el.tagName.toLowerCase()}${el.className ? '.' + String(el.className).split(' ')[0] : ''}`
            + ` ${Math.round(r.width)}×${Math.round(r.height)} "${texto}"`);
        }
      }
      return { total, pequenos };
    },

    mapas() {
      const mapas = [...document.querySelectorAll('[data-mapa], .mapa, #mapa, .leaflet-container')];
      return mapas.map((el) => {
        const rotulo = el.getAttribute('aria-label') || '';
        const papel = el.getAttribute('role') || '';
        const descrito = el.getAttribute('aria-describedby') || '';
        const alternativa = el.querySelector('figcaption, .alternativa, .sr-apenas');
        return {
          papel,
          rotulo: rotulo.trim(),
          descrito: descrito.trim(),
          alternativa: alternativa ? (alternativa.textContent || '').trim().slice(0, 60) : '',
        };
      });
    },
  };
};

async function medirAxe(pagina, nome, tema) {
  await pagina.emulateMedia({ colorScheme: tema });
  await pagina.waitForTimeout(150);

  console.log(`\n${nome} [${tema}] · axe-core (WCAG 2.1 A/AA)`);
  const axe = await new AxeBuilder({ page: pagina })
    .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
    .analyze();

  const recorte = {
    violacoes: axe.violations.map((v) => ({
      id: v.id,
      impacto: v.impact,
      ajuda: v.help,
      url: v.helpUrl,
      nos: v.nodes.map((n) => ({
        alvo: n.target.join(' '),
        resumo: (n.failureSummary || '').replace(/\s+/g, ' ').slice(0, 300),
      })),
    })),
    incompletos: axe.incomplete.map((v) => v.id),
    aprovadas: axe.passes.length,
  };

  if (axe.violations.length === 0) {
    ok(`nenhuma violação A/AA (${axe.passes.length} regras aprovadas)`);
  } else {
    for (const v of axe.violations) {
      falha(`[${tema}] ${v.id} (${v.impact}) — ${v.help}; `
        + v.nodes.slice(0, 3).map((n) => n.target.join(' ')).join(' | ')
        + (v.nodes.length > 3 ? ` … +${v.nodes.length - 3}` : ''));
    }
  }
  if (axe.incomplete.length) {
    console.log('  nota  incompletos (revisão manual): ' + axe.incomplete.map((v) => v.id).join(', '));
  }
  return recorte;
}

async function medirPagina(pagina, nome) {
  const resultado = { nome, url: pagina.url(), axe: {}, foco: {}, toque: {}, mapas: [] };

  // Dois temas, como no v2. A v3 ainda não tem CSS escuro; quando tiver, o gate
  // já está medindo a preferência escura em vez de descobrir depois do deploy.
  for (const tema of ['light', 'dark']) {
    resultado.axe[tema] = await medirAxe(pagina, nome, tema);
  }
  await pagina.emulateMedia({ colorScheme: 'light' });

  // Foco: a foto de repouso precisa ser tirada com o foco fora de qualquer controle.
  await pagina.evaluate(() => document.activeElement?.blur?.());
  await pagina.waitForTimeout(250);
  await pagina.evaluate(fonte => { window.__a11y = (0, eval)('(' + fonte + ')')(); }, MEDIDORES.toString());
  const quantos = await pagina.evaluate(() => window.__a11y.instantaneos());
  await pagina.evaluate(() => document.body.focus());

  const paradas = [];
  const semSinal = [];
  const semReferencia = [];
  const vistos = new Set();
  for (let i = 0; i < 80; i += 1) {
    await pagina.keyboard.press('Tab');
    await pagina.waitForTimeout(120);
    const atual = await pagina.evaluate(() => window.__a11y.focoAtual());
    if (atual.vazio) continue;
    const chave = atual.id || atual.descricao;
    if (vistos.has(chave)) break;
    vistos.add(chave);
    paradas.push(atual.descricao);
    if (atual.mudou === false) semSinal.push(atual.descricao);
    if (atual.mudou === null) semReferencia.push(atual.descricao);
  }

  resultado.foco = { paradas, semSinal, semReferencia };
  console.log(`${nome} · foco pelo teclado`);
  conferir(paradas.length > 0, `${paradas.length} paradas de Tab medidas de ${quantos} controles`);
  conferir(semSinal.length === 0,
    'todo controle focado mostra sinal visível'
    + (semSinal.length ? ' — sem sinal: ' + semSinal.slice(0, 5).join(' | ') : ''));
  if (semReferencia.length) {
    console.log(`  nota  ${semReferencia.length} controle(s) sem comparação de repouso`);
  }

  // Alvo de toque: mesma página, viewport de celular.
  await pagina.setViewportSize({ width: 390, height: 844 });
  await pagina.waitForTimeout(200);
  const toque = await pagina.evaluate(() => window.__a11y.alvosPequenos(44));
  resultado.toque = toque;
  console.log(`${nome} · alvo de toque (390 px)`);
  conferir(toque.pequenos.length === 0,
    `${toque.total} controles medidos; ${toque.pequenos.length} abaixo de 44 px`
    + (toque.pequenos.length ? ' — pequenos: ' + toque.pequenos.slice(0, 5).join(' | ') : ''));
  await pagina.setViewportSize({ width: 1360, height: 900 });

  // Mapa com alternativa textual — armado para a tela que tiver mapa.
  const mapas = await pagina.evaluate(() => window.__a11y.mapas());
  resultado.mapas = mapas;
  console.log(`${nome} · mapa`);
  if (mapas.length === 0) {
    ok('sem mapa nesta tela (regra de alternativa textual não se aplica)');
  } else {
    for (const mapa of mapas) {
      const temNome = mapa.papel === 'img' && mapa.rotulo.length > 0;
      const temTexto = mapa.descrito.length > 0 || mapa.alternativa.length > 0;
      conferir(temNome || temTexto,
        'mapa com alternativa textual (role="img" + nome acessível ou texto equivalente)');
    }
  }

  return resultado;
}

async function main() {
  mkdirSync(SAIDA, { recursive: true });
  let navegador;
  try {
    navegador = await chromium.launch({ headless: !process.env.PW_HEADED });
    const contexto = await navegador.newContext({ viewport: { width: 1360, height: 900 } });
    const pagina = await contexto.newPage();

    for (const alvo of PAGINAS) {
      const resposta = await pagina.goto(BASE + alvo.rota, { waitUntil: 'networkidle', timeout: 30000 });
      if (!resposta || !resposta.ok()) {
        falha(`${alvo.rota} respondeu ${resposta ? resposta.status() : 'sem resposta'}`);
        continue;
      }
      relatorio.paginas.push(await medirPagina(pagina, alvo.nome));
    }

    // Uma página com erro de servidor pode renderizar 200 com stack trace: o
    // teste de fumaça anterior não distingue. Aqui o conteúdo é conferido.
    if (relatorio.paginas.length !== PAGINAS.length) {
      falha('nem todas as páginas puderam ser medidas');
    }
  } catch (e) {
    falha('exceção: ' + (e && e.message ? e.message : String(e)));
  } finally {
    if (navegador) await navegador.close().catch(() => {});
  }

  writeFileSync(join(SAIDA, 'acessibilidade.json'),
    JSON.stringify({ ...relatorio, falhas }, null, 2) + '\n', 'utf8');
  console.log(`\nrelatório: ${join(SAIDA, 'acessibilidade.json')}`);
  console.log('\n' + (falhas.length === 0 ? 'ACESSIBILIDADE OK' : `ACESSIBILIDADE FALHOU (${falhas.length})`));
  process.exit(falhas.length === 0 ? 0 : 1);
}

main();
