// Fumaça e2e da rodada 9 — um comando, saída em JSON.
//
// Cobre, contra o wireframe rodando, o que as rodadas 8 e 9 provaram com sonda solta:
//   1. controles do canto × dica do pino (item 1)
//   2. caixa = dica ∪ alvo do pino na folha meia (item 2, caso I)
//   3. F2 e os cantos no desktop (item 3)
//   4. chip do início em duas larguras (item 4)
//   5. pino de 44 px e pares sobrepostos (item 6)
//   6. anel de foco: clique de mouse × focus() (item 7)
//   7. teclado: 30 Tabs sem parar em `<path>` (estado 7)
//
// Uso:  BASE_URL=http://127.0.0.1:8123 node tests/e2e/fumaca.js
// Alvo: o wireframe Laravel em "IDEIAS PARA O V3/wireframe" (fora do repositório, em
// .gitignore). Quando a v3.0 existir, o alvo passa a ser ela e só o BASE_URL muda.
// Requer Playwright (`npm i -D playwright` aqui ou `NODE_PATH` apontando para um install).
// Fora do CI de propósito: sem o protótipo no clone, não há o que medir.
// Sai com 0 se tudo passar; com 1 se algum caso falhar (e imprime o JSON completo).

const { chromium } = require('playwright');

const BASE = process.env.BASE_URL || 'http://127.0.0.1:8123';
const soJSON = process.argv.includes('--json');
const r1 = (n) => Math.round(n * 10) / 10;

const casos = [];
const registrar = (id, nome, fn) => casos.push({ id, nome, fn });

// helpers de página (injetados via evaluate, não existem no Node)
const medirDica = async (page) => page.evaluate(() => {
  const r1 = (n) => Math.round(n * 10) / 10;
  const visiveis = [...document.querySelectorAll('.leaflet-tooltip.pino-dica')].filter((e) => e.offsetParent);
  const dica = visiveis[visiveis.length - 1];
  const seguros = [...document.querySelectorAll('.leaflet-top .leaflet-control')].filter((e) => e.offsetParent).map((e) => e.getBoundingClientRect());
  const uniao = seguros.length
    ? { l: Math.min(...seguros.map((r) => r.left)), t: Math.min(...seguros.map((r) => r.top)), r: Math.max(...seguros.map((r) => r.right)), b: Math.max(...seguros.map((r) => r.bottom)) }
    : null;
  const barra = document.querySelector('.topbar')?.getBoundingClientRect();
  const painel = document.querySelector('.painel')?.getBoundingClientRect();
  const rail = document.querySelector('.rail');
  // igual ao `caixaSegura()`: no desktop o rail fecha a esquerda e o painel a direita; no
  // celular o painel é folha de baixo (left = 0) e só a base é que fecha
  const desktop = !!rail?.offsetParent;
  const area = {
    esq: desktop ? rail.getBoundingClientRect().right + 8 : 8,
    dir: desktop && painel ? painel.left - 8 : innerWidth - 8,
    topo: (barra?.bottom ?? 0) + 8,
    base: innerHeight - 8,
  };
  if (!dica) return { dica: null, uniao, area, centro: `${window.__mapa.getCenter().lat},${window.__mapa.getCenter().lng}` };
  const d = dica.getBoundingClientRect();
  const cruza = uniao && d.left < uniao.r && d.right > uniao.l && d.top < uniao.b && d.bottom > uniao.t;
  const folga = uniao ? Math.min(Math.abs(d.right - uniao.l), Math.abs(d.left - uniao.r), Math.abs(d.bottom - uniao.t), Math.abs(d.top - uniao.b)) : null;
  return {
    dica: { l: r1(d.left), t: r1(d.top), r: r1(d.right), b: r1(d.bottom), w: r1(d.width), h: r1(d.height) },
    uniao: uniao && { l: r1(uniao.l), t: r1(uniao.t), r: r1(uniao.r), b: r1(uniao.b) },
    area: { esq: r1(area.esq), dir: r1(area.dir), topo: r1(area.topo), base: r1(area.base) },
    cruzaUniao: !!cruza, folga: folga === null ? null : r1(folga),
    centro: `${window.__mapa.getCenter().lat},${window.__mapa.getCenter().lng}`,
  };
});

const porPonto = async (page, rotulo, x, y) => page.evaluate(({ rotulo, x, y }) => {
  const mapa = window.__mapa;
  const pino = document.querySelector(`.pino-alvo[aria-label^="${rotulo} —"]`);
  if (!pino) return { erro: 'pino não encontrado' };
  const r = pino.getBoundingClientRect();
  const dx = Math.round(r.left + r.width / 2 - x), dy = Math.round(r.top + r.height / 2 - y);
  mapa.panBy([dx, dy], { animate: false });
  const d = pino.getBoundingClientRect();
  return { x: Math.round(d.left + d.width / 2), y: Math.round(d.top + d.height / 2) };
}, { rotulo, x, y });

// ─────────────────────────────────────────────────────────── 1. controles × dica
registrar('1-controles', 'dica do pino não cruza a união dos controles (celular 390)', async () => {
  const ctx = await chromium.launch();
  const page = await (await ctx.newContext({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true, deviceScaleFactor: 2 })).newPage();
  const medidas = [];
  // uma página nova por posição: o estado da seleção anterior (dica permanente, pan pendente
  // no rAF) muda o resultado da próxima, e cada posição tem de valer por si
  for (const y of [220, 260, 300, 340]) {
    await page.goto(BASE + '/comarca/MUN0001', { waitUntil: 'networkidle' });
    await page.waitForTimeout(800);
    await porPonto(page, 'Fórum Criminal', 364, y);
    await page.touchscreen.tap(364, y);
    await page.waitForTimeout(450);
    medidas.push({ y, ...(await medirDica(page)) });
  }
  await ctx.close();
  const abertas = medidas.filter((m) => m.dica);
  const piores = abertas.filter((m) => m.cruzaUniao || m.dica.l < m.area.esq - 0.5 || m.dica.r > m.area.dir + 0.5 || m.dica.t < m.area.topo - 0.5);
  return {
    pass: abertas.length === medidas.length && piores.length === 0,
    numero: `${abertas.length} de ${medidas.length} dicas abertas, ${piores.length} cruzando a união ou a área segura`,
    medidas,
  };
});

// ─────────────────────────────────────────────────────────── 2. caso I
registrar('2-caso-i', 'caixa = dica ∪ alvo: alvo inteiro acima da folha meia (celular)', async () => {
  const ctx = await chromium.launch();
  const page = await (await ctx.newContext({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true, deviceScaleFactor: 2 })).newPage();
  await page.goto(BASE + '/comarca/MUN0001?folha=meia', { waitUntil: 'networkidle' });
  await page.waitForTimeout(900);
  const folhaTopo = await page.evaluate(() => document.querySelector('.painel').getBoundingClientRect().top);
  const antes = await page.evaluate(() => {
    const folha = document.querySelector('.painel').getBoundingClientRect();
    const pino = document.querySelector('.pino-alvo[aria-label^="Fórum Cível —"]');
    const mapa = window.__mapa;
    if (!pino) return { erro: 'pino' };
    const r = pino.getBoundingClientRect();
    const cx = r.left + r.width / 2;
    mapa.panBy([Math.round(cx - 195), Math.round(r.top + r.height / 2 - (folha.top - 9))], { animate: false });
    const d = pino.getBoundingClientRect();
    return { alvo: { l: Math.round(d.left), t: Math.round(d.top), r: Math.round(d.right), b: Math.round(d.bottom) }, centro: [Math.round(d.left + d.width / 2), Math.round(d.top + d.height / 2)] };
  });
  if (antes.erro) { await ctx.close(); return { pass: false, numero: 'sem pino', erro: antes.erro }; }
  const sobAntes = r1(antes.alvo.b - folhaTopo);
  await page.touchscreen.tap(antes.centro[0], antes.centro[1]);
  await page.waitForTimeout(450);
  const depois = await page.evaluate(() => {
    const folha = document.querySelector('.painel').getBoundingClientRect();
    const pino = document.querySelector('.pino-alvo[aria-label^="Fórum Cível —"]');
    const d = pino.getBoundingClientRect();
    return { alvo: { l: Math.round(d.left), t: Math.round(d.top), r: Math.round(d.right), b: Math.round(d.bottom) }, folhaTopo: Math.round(folha.top) };
  });
  await ctx.close();
  const sobDepois = r1(depois.alvo.b - depois.folhaTopo);
  return { pass: sobDepois <= -8 || sobDepois <= 0, numero: `alvo ${sobAntes} px sob a folha → ${sobDepois} px`, sobAntes, sobDepois, alvoAntes: antes.alvo, alvoDepois: depois.alvo };
});

// ─────────────────────────────────────────────────────────── 3. F2 e cantos
registrar('3-desktop', 'F2 e cantos: dica dentro da área segura e vista parada (1440)', async () => {
  const ctx = await chromium.launch();
  const page = await (await ctx.newContext({ viewport: { width: 1440, height: 900 } })).newPage();
  await page.goto(BASE + '/comarca/MUN0001', { waitUntil: 'networkidle' });
  await page.waitForTimeout(1000);
  const area = await page.evaluate(() => {
    const r1 = (n) => Math.round(n * 10) / 10;
    const barra = document.querySelector('.topbar').getBoundingClientRect();
    const rail = document.querySelector('.rail').getBoundingClientRect();
    const painel = document.querySelector('.painel').getBoundingClientRect();
    const seguros = [...document.querySelectorAll('.leaflet-top .leaflet-control')].filter((e) => e.offsetParent).map((e) => e.getBoundingClientRect());
    return {
      esq: r1(rail.right + 8), dir: r1(painel.left - 8), topo: r1(barra.bottom + 8), base: r1(innerHeight - 8),
      uniaoCentroX: r1((Math.min(...seguros.map((r) => r.left)) + Math.max(...seguros.map((r) => r.right))) / 2),
    };
  });
  const casosLocais = [];
  for (const ponto of [{ id: 'rail', x: area.esq - 8, y: 420 }, { id: 'canto-sup-dir', x: area.uniaoCentroX, y: 70 }]) {
    await porPonto(page, 'Fórum Cível', ponto.x, ponto.y);
    const centroAntes = await page.evaluate(() => { const c = window.__mapa.getCenter(); return `${c.lat},${c.lng}`; });
    const alvo = await page.evaluate(() => {
      const p = document.querySelector('.pino-alvo[aria-label^="Fórum Cível —"]');
      const r = p.getBoundingClientRect();
      for (let dy = 0.15; dy <= 0.85; dy += 0.1) {
        for (let dx = 0.15; dx <= 0.85; dx += 0.1) {
          const x = r.left + r.width * dx, y = r.top + r.height * dy;
          if (document.elementFromPoint(x, y)?.closest('.pino-alvo') === p) return { x, y };
        }
      }
      return null;
    });
    if (!alvo) { casosLocais.push({ id: ponto.id, ok: false, motivo: 'pino coberto' }); continue; }
    await page.mouse.move(alvo.x, alvo.y);
    await page.waitForTimeout(450);
    const m = await medirDica(page);
    const fora = m.dica ? {
      esq: r1(Math.max(0, m.area.esq - m.dica.l)), dir: r1(Math.max(0, m.dica.r - m.area.dir)),
      topo: r1(Math.max(0, m.area.topo - m.dica.t)), base: r1(Math.max(0, m.dica.b - m.area.base)),
    } : null;
    const pior = fora ? Math.max(fora.esq, fora.dir, fora.topo, fora.base) : null;
    casosLocais.push({ id: ponto.id, ok: !!m.dica && pior <= 0.6 && m.centro === centroAntes, pior, dica: m.dica, vistaAndou: m.centro !== centroAntes });
    await page.mouse.move(300, 500);
    await page.waitForTimeout(200);
  }
  await ctx.close();
  const falhas = casosLocais.filter((c) => !c.ok);
  return { pass: falhas.length === 0, numero: `${casosLocais.length - falhas.length} de ${casosLocais.length} casos dentro da área segura`, casos: casosLocais };
});

// ─────────────────────────────────────────────────────────── 4. chip
registrar('4-chip', 'chip: dois blocos abaixo de 640 px, uma linha com "·" acima', async () => {
  const ctx = await chromium.launch();
  const medidos = [];
  for (const [w, h, movel] of [[390, 844, true], [1440, 900, false]]) {
    const page = await (await ctx.newContext({ viewport: { width: w, height: h }, isMobile: movel, hasTouch: movel })).newPage();
    await page.goto(BASE + '/', { waitUntil: 'networkidle' });
    await page.waitForTimeout(500);
    medidos.push(await page.evaluate(({ w }) => {
      const p = [...document.querySelectorAll('p.rotulo')].find((e) => e.querySelector('.whitespace-nowrap'));
      const blocos = [...p.querySelectorAll('.whitespace-nowrap')].map((e) => e.getBoundingClientRect());
      const ponto = p.querySelector('[aria-hidden="true"]');
      const visivel = !!ponto && getComputedStyle(ponto).display !== 'none' && ponto.getBoundingClientRect().width > 0;
      return { w, linhas: new Set(blocos.map((b) => Math.round(b.top))).size, ponto: visivel, mesmoLeft: Math.abs(blocos[0].left - blocos[1].left) <= 1 };
    }, { w }));
  }
  await ctx.close();
  const ok = medidos.every((m) => m.w < 640 ? (m.linhas === 2 && !m.ponto) : (m.linhas === 1 && m.ponto));
  return { pass: ok, numero: medidos.map((m) => `${m.w}: ${m.linhas} linha(s), ponto ${m.ponto ? 'sim' : 'não'}`).join(' · '), medidos };
});

// ─────────────────────────────────────────────────────────── 5. pinos
registrar('5-pinos', 'alvo de 44 × 44 em todos os pinos e pares sobrepostos contados', async () => {
  const ctx = await chromium.launch();
  const page = await (await ctx.newContext({ viewport: { width: 1440, height: 900 } })).newPage();
  await page.goto(BASE + '/comarca/MUN0001', { waitUntil: 'networkidle' });
  await page.waitForTimeout(900);
  const m = await page.evaluate(() => {
    const painel = document.querySelector('.painel').getBoundingClientRect();
    const pinos = [...document.querySelectorAll('.pino-alvo')].map((e) => {
      const r = e.getBoundingClientRect();
      return { el: e, l: r.left, t: r.top, r: r.right, b: r.bottom, cx: r.left + r.width / 2, cy: r.top + r.height / 2, w: r.width, h: r.height, visivel: r.left >= 0 && r.top >= 0 && r.right <= innerWidth && r.bottom <= innerHeight && r.left < painel.left };
    });
    const alcancaveis = pinos.filter((p) => p.visivel);
    let pares = 0, semSaida = 0;
    for (let i = 0; i < alcancaveis.length; i++)
      for (let j = i + 1; j < alcancaveis.length; j++) {
        const a = alcancaveis[i], b = alcancaveis[j];
        if (!(a.l < b.r && b.l < a.r && a.t < b.b && b.t < a.b)) continue;
        pares++;
        const no = (x, y) => document.elementFromPoint(x, y)?.closest('.pino-alvo');
        if (no(a.cx, a.cy) !== a.el || no(b.cx, b.cy) !== b.el) semSaida++;
      }
    return { total: pinos.length, alcancaveis: alcancaveis.length, quarentaEQuatro: pinos.every((p) => Math.abs(p.w - 44) < 0.6 && Math.abs(p.h - 44) < 0.6), pares, semSaida };
  });
  await ctx.close();
  return { pass: m.quarentaEQuatro && m.semSaida === 0, numero: `${m.total} pinos, todos 44 × 44: ${m.quarentaEQuatro}; ${m.alcancaveis} alcançáveis, ${m.pares} pares sobrepostos, ${m.semSaida} sem saída`, ...m };
});

// ─────────────────────────────────────────────────────────── 6. foco
registrar('6-foco', 'anel de foco: clique de mouse não pinta, focus() sem mouse pinta', async () => {
  const ctx = await chromium.launch();
  const page = await (await ctx.newContext({ viewport: { width: 1440, height: 900 } })).newPage();
  await page.addInitScript(() => {
    document.addEventListener('click', (e) => { if (e.target?.tagName === 'path') { e.stopPropagation(); e.preventDefault(); } }, true);
  });
  await page.goto(BASE + '/comarca/MUN0001', { waitUntil: 'networkidle' });
  await page.waitForTimeout(900);
  const achar = () => {
    const c = [...document.querySelectorAll('#mapa path.leaflet-interactive')].find((p) => { const r = p.getBoundingClientRect(); return r.width > 60 && r.height > 60; });
    const r = c.getBoundingClientRect();
    return { x: Math.round(r.left + r.width / 2), y: Math.round(r.top + r.height / 2) };
  };
  const ponto = await page.evaluate(achar);
  await page.mouse.click(ponto.x, ponto.y);
  await page.waitForTimeout(300);
  const comMouse = await page.evaluate(() => {
    const c = [...document.querySelectorAll('#mapa path.leaflet-interactive')].find((p) => { const r = p.getBoundingClientRect(); return r.width > 60 && r.height > 60; });
    return c.matches(':focus-visible');
  });
  const limpa = await (await ctx.newContext({ viewport: { width: 1440, height: 900 } })).newPage();
  await limpa.goto(BASE + '/comarca/MUN0001', { waitUntil: 'networkidle' });
  await limpa.waitForTimeout(900);
  const semMouse = await limpa.evaluate(() => {
    const c = [...document.querySelectorAll('#mapa path.leaflet-interactive')].find((p) => { const r = p.getBoundingClientRect(); return r.width > 60 && r.height > 60; });
    c.focus();
    return c.matches(':focus-visible');
  });
  await ctx.close();
  return { pass: comMouse === false && semMouse === true, numero: `clique de mouse: focus-visible ${comMouse}; focus() sem mouse: ${semMouse}`, comMouse, semMouse };
});

// ─────────────────────────────────────────────────────────── 7. teclado
registrar('7-teclado', '30 Tabs não param em polígono do mapa', async () => {
  const ctx = await chromium.launch();
  const page = await (await ctx.newContext({ viewport: { width: 1440, height: 900 } })).newPage();
  await page.goto(BASE + '/comarca/MUN0001', { waitUntil: 'networkidle' });
  await page.waitForTimeout(900);
  const paradas = [];
  for (let i = 0; i < 30; i++) {
    await page.keyboard.press('Tab');
    paradas.push(await page.evaluate(() => ({ tag: document.activeElement?.tagName, noMapa: !!document.activeElement?.closest?.('#mapa') })));
  }
  const noPath = paradas.filter((p) => p.tag === 'path').length;
  const tabulaveis = await page.evaluate(() => [...document.querySelectorAll('#mapa path')].filter((p) => (p.getAttribute('tabindex') || '') >= '0').length);
  await ctx.close();
  return { pass: noPath === 0 && tabulaveis === 0, numero: `${paradas.length - noPath} de ${paradas.length} paradas sem path · ${tabulaveis} path com tabindex ≥ 0`, noPath, tabulaveis };
});

(async () => {
  for (const c of casos) {
    try {
      const r = await c.fn();
      casos[casos.indexOf(c)] = { id: c.id, nome: c.nome, resultado: r.pass ? 'PASS' : 'FALHA', ...r };
    } catch (e) {
      casos[casos.indexOf(c)] = { id: c.id, nome: c.nome, resultado: 'ERRO', erro: String(e) };
    }
  }
  const d = casos.filter((c) => c.resultado === 'PASS').length;
  const saida = {
    quando: new Date().toISOString(), base: BASE, casos: casos.length, pass: d,
    resultados: casos.map((c) => ({ id: c.id, resultado: c.resultado, numero: c.numero, erro: c.erro })),
    detalhes: casos.filter((c) => c.medidas || c.casos || c.medidos).map((c) => ({ id: c.id, medidas: c.medidas, casos: c.casos, medidos: c.medidos })),
    falhas: casos.filter((c) => c.resultado !== 'PASS').map((c) => ({ id: c.id, erro: c.erro || c.numero })),
  };
  if (soJSON) console.log(JSON.stringify(saida));
  else {
    console.log(`\n${d}/${casos.length} PASS · ${BASE}\n`);
    for (const c of casos) console.log(` ${c.resultado.padEnd(6)} ${c.id.padEnd(12)} ${c.numero || c.erro}`);
    console.log('');
  }
  process.exit(d === casos.length ? 0 : 1);
})();
