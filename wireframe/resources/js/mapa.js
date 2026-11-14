import * as L from 'leaflet';

/**
 * Mapa protagonista da direção A: Leaflet sem tiles, malha municipal do ES versionada,
 * polígono como silhueta/enquadramento e disco de densidade como alvo de clique.
 * Componente escrito do zero para o wireframe — nada herdado do mapa do v1/v2.
 */

// janela do litoral do ES; serve para descartar partes de geometria fora do estado
// (a malha do IBGE inclui a ilha de Trindade em Vitória, a ~1.100 km do continente)
const JANELA = { sul: -21.7, norte: -17.4, oeste: -42.4, leste: -39.2 };

// só as comarcas que precisam de nome no mapa, com o lado onde o rótulo não colide
const ETIQUETAS = {
    'Vitória': 'right',
    'Vila Velha': 'bottom',
    'Cariacica': 'left',
    'Guarapari': 'right',
    'Cachoeiro de Itapemirim': 'bottom',
    'Serra': 'top',
};

const dentro = ([lng, lat]) => lat >= JANELA.sul && lat <= JANELA.norte && lng >= JANELA.oeste && lng <= JANELA.leste;

function semIlhasLonge(geo) {
    const features = [];

    for (const f of geo.features) {
        const g = f.geometry;

        if (g.type === 'Polygon') {
            if (g.coordinates[0].every(dentro)) features.push(f);
            continue;
        }

        const partes = g.coordinates.filter((poly) => poly[0].every(dentro));
        if (partes.length) {
            features.push({ ...f, geometry: { type: 'MultiPolygon', coordinates: partes } });
        }
    }

    return { ...geo, features };
}

export function iniciarMapa() {
    const el = document.getElementById('mapa');
    const fonte = document.getElementById('mapa-dados');
    if (!el || !fonte) return;

    const cfg = JSON.parse(fonte.textContent);
    const movel = () => window.matchMedia('(max-width: 767px)').matches;

    const porIbge = new Map(cfg.comarcas.filter((c) => c.ibge).map((c) => [c.ibge, c]));
    const focoIbge = cfg.foco_ibge || null;
    const camadas = new Map();
    const discos = new Map();
    const centroides = new Map();
    let contatosMax = 1;
    let camadaGeo = null;
    let selecionado = cfg.selecionada || null;

    const mapa = L.map(el, {
        center: [-19.85, -40.9],
        zoom: 7,
        minZoom: 6,
        maxZoom: 13,
        zoomControl: false,
        attributionControl: true,
        zoomSnap: 0.25,
        zoomDelta: 0.5,
        maxBoundsViscosity: 0.6,
    });

    L.control.zoom({ position: 'topleft' }).addTo(mapa);
    mapa.attributionControl.setPrefix(false);
    mapa.attributionControl.addAttribution('Malha municipal: IBGE');

    mapa.createPane('discos');
    mapa.getPane('discos').style.zIndex = 450;

    const estilo = (feature) => {
        if (focoIbge && feature.properties.codarea === focoIbge) {
            return {
                color: '#5EEAD4',
                weight: 2,
                opacity: 1,
                dashArray: '6 4',
                fillColor: '#2DD4BF',
                fillOpacity: 0.22,
            };
        }

        const comarca = porIbge.get(feature.properties.codarea);
        const ativo = selecionado && comarca && comarca.chave === selecionado;

        return {
            color: ativo ? '#5EEAD4' : comarca ? 'rgba(94,234,212,0.42)' : 'rgba(140,163,182,0.18)',
            weight: ativo ? 2 : 1,
            opacity: 1,
            fillColor: ativo ? '#2DD4BF' : comarca ? '#15304a' : '#0f1a28',
            fillOpacity: ativo ? 0.4 : comarca ? 0.85 : 0.6,
            dashArray: comarca ? null : '3 5',
        };
    };

    const conteudo = (feature) => {
        const c = porIbge.get(feature.properties.codarea);
        if (!c) return '<span class="num" style="color:#8CA3B6">sem contatos</span>';

        return `<strong style="font-weight:500">${c.nome}</strong>
                <span class="num" style="color:#5EEAD4;margin-left:.4rem">${c.contatos}</span>`;
    };

    const raioDe = (contatos) => (4 + 13 * Math.sqrt(contatos / contatosMax)) * (movel() ? 0.74 : 1);

    const enquadrar = (camada) => {
        if (!camada) return;
        const folha = document.querySelector('.painel')?.getBoundingClientRect();
        const pad = movel()
            ? { paddingTopLeft: [16, 140], paddingBottomRight: [16, Math.max(folha ? folha.height : 120, 150)] }
            : { paddingTopLeft: [56, 124], paddingBottomRight: [468, 72] };

        mapa.fitBounds(camada.getBounds(), { ...pad, animate: true, duration: 0.5 });
    };

    const emFoco = (chave) => document.querySelectorAll(`[data-comarca="${chave}"]`);

    const inicializar = (bruto) => {
        const geo = semIlhasLonge(bruto);

        // centróide = média do anel externo; no caso de ilhas, o anel com mais pontos
        geo.features.forEach((f) => {
            const g = f.geometry;
            const aneis = g.type === 'Polygon' ? [g.coordinates[0]] : g.coordinates.map((p) => p[0]);
            let maior = null;

            aneis.forEach((anel) => {
                if (!maior || anel.length > maior.length) maior = anel;
            });

            if (!maior) return;
            const soma = maior.reduce((acc, p) => [acc[0] + p[0], acc[1] + p[1]], [0, 0]);
            centroides.set(f.properties.codarea, [soma[1] / maior.length, soma[0] / maior.length]);
        });

        contatosMax = Math.max(...cfg.comarcas.map((c) => c.contatos), 1);

        camadaGeo = L.geoJSON(geo, {
            style: estilo,
            onEachFeature: (feature, layer) => {
                const codarea = feature.properties.codarea;
                const comarca = porIbge.get(codarea);
                camadas.set(codarea, layer);

                layer.bindTooltip(conteudo(feature), {
                    className: 'municipio',
                    sticky: true,
                    direction: 'top',
                    offset: [0, -4],
                });

                layer.on('mouseover', () => {
                    if (!comarca || selecionado !== comarca.chave) {
                        layer.setStyle({ fillColor: '#2DD4BF', fillOpacity: 0.3, color: '#5EEAD4', weight: 1.4 });
                    }
                    if (comarca) emFoco(comarca.chave).forEach((n) => n.classList.add('em-foco'));
                });

                layer.on('mouseout', () => {
                    if (!comarca || selecionado !== comarca.chave) layer.setStyle(estilo(feature));
                    if (comarca) emFoco(comarca.chave).forEach((n) => n.classList.remove('em-foco'));
                });

                layer.on('click', () => {
                    if (comarca && comarca.url) {
                        window.location.href = comarca.url;
                    } else if (!comarca) {
                        window.location.href = `/municipio/${codarea}`;
                    }
                });
            },
        }).addTo(mapa);

        const comarcas = cfg.comarcas
            .filter((c) => c.ibge && centroides.has(c.ibge))
            .sort((a, b) => a.contatos - b.contatos);

        comarcas.forEach((c) => {
            const [lat, lon] = centroides.get(c.ibge);
            const raio = raioDe(c.contatos);
            const ativo = selecionado === c.chave;

            const disco = L.circleMarker([lat, lon], {
                pane: 'discos',
                radius: raio,
                color: ativo ? '#EAFBFF' : 'rgba(94,234,212,0.7)',
                weight: ativo ? 2 : 1,
                fillColor: '#2DD4BF',
                fillOpacity: ativo ? 0.16 : 0.72,
            }).addTo(mapa);

            disco.bindTooltip(
                `<strong style="font-weight:500">${c.nome}</strong><span class="num" style="color:#5EEAD4;margin-left:.4rem">${c.contatos}</span>`,
                { className: 'municipio', direction: 'top', offset: [0, -Math.round(raio) - 2] }
            );

            disco.on('mouseover', () => {
                disco.setStyle({ fillOpacity: 0.95, radius: raio + 2 });
                emFoco(c.chave).forEach((n) => n.classList.add('em-foco'));
            });

            disco.on('mouseout', () => {
                disco.setStyle({ fillOpacity: ativo ? 0.95 : 0.72, radius: raio });
                emFoco(c.chave).forEach((n) => n.classList.remove('em-foco'));
            });

            disco.on('click', () => {
                if (c.url) window.location.href = c.url;
            });

            discos.set(c.ibge, disco);

            const etiqueta = ETIQUETAS[c.nome];
            const lado = etiqueta && (!movel() || ['Vitória', 'Cariacica', 'Vila Velha'].includes(c.nome)) ? etiqueta : null;
            if (lado) {
                const deslocamento = {
                    right: [raio + 6, 0],
                    left: [-raio - 6, 0],
                    top: [0, -raio - 6],
                    bottom: [0, raio + 6],
                }[lado];

                L.circleMarker([lat, lon], { pane: 'discos', radius: 0, opacity: 0, fillOpacity: 0 })
                    .addTo(mapa)
                    .bindTooltip(c.nome, {
                        permanent: true,
                        direction: lado,
                        offset: deslocamento,
                        className: 'rotulo-municipio',
                    });
            }
        });

        const ibgeDe = (chave) => cfg.comarcas.find((c) => c.chave === chave)?.ibge;
        const camadaDe = (chave) => (chave && ibgeDe(chave) ? camadas.get(ibgeDe(chave)) : null);

        enquadrar(camadaDe(selecionado) || (focoIbge && camadas.get(focoIbge)) || camadaGeo);

        if (focoIbge && centroides.has(focoIbge)) {
            const [lat, lon] = centroides.get(focoIbge);
            L.circleMarker([lat, lon], { pane: 'discos', radius: 0, opacity: 0, fillOpacity: 0 })
                .addTo(mapa)
                .bindTooltip(cfg.municipio_nome || 'município sem comarca', {
                    permanent: true,
                    direction: 'top',
                    offset: [0, -6],
                    className: 'rotulo-municipio',
                });
        }

        mapa.on('zoomend', () => {
            document.body.dataset.zoom = mapa.getZoom().toFixed(1);
        });

        // sincroniza a lista do painel com o mapa (hover nos dois sentidos)
        document.querySelectorAll('[data-comarca]').forEach((linha) => {
            const chave = linha.dataset.comarca;
            const comarca = cfg.comarcas.find((c) => c.chave === chave);
            const layer = comarca && comarca.ibge ? camadas.get(comarca.ibge) : null;
            if (!layer) return;

            linha.addEventListener('mouseenter', () => {
                if (selecionado === chave) return;
                layer.setStyle({ fillColor: '#2DD4BF', fillOpacity: 0.3, color: '#5EEAD4', weight: 1.5 });
                layer.bringToFront();
            });

            linha.addEventListener('mouseleave', () => {
                if (selecionado === chave) return;
                layer.setStyle(estilo(layer.feature));
            });

            linha.addEventListener('focus', () => {
                mapa.panInside(layer.getBounds().getCenter(), { animate: true });
            });
        });

        document.documentElement.dataset.mapa = 'pronto';
        document.dispatchEvent(new CustomEvent('mapa:pronto'));
    };

    const falhar = (erro) => {
        console.warn('Mapa não carregou:', erro);
        document.documentElement.dataset.mapa = 'falhou';
        document.documentElement.dataset.mapaErro = String((erro && erro.message) || erro).slice(0, 200);
        document.dispatchEvent(new CustomEvent('mapa:falhou'));
        const aviso = document.getElementById('aviso-mapa');
        if (aviso) aviso.hidden = false;
    };

    const carregar = () =>
        fetch(cfg.geo_url, { headers: { Accept: 'application/geo+json' } })
            .then((r) => {
                if (!r.ok) throw new Error(`HTTP ${r.status}`);
                return r.json();
            })
            .then(inicializar)
            .catch(falhar);

    if (cfg.modo === 'falha') {
        falhar(new Error('falha forçada pelo parâmetro ?mapa=falha'));
    } else if (cfg.modo === 'lento') {
        setTimeout(carregar, 6000);
    } else {
        carregar();
    }

    window.addEventListener('resize', () => mapa.invalidateSize());

    window.addEventListener('folha:mudou', () => {
        setTimeout(() => {
            mapa.invalidateSize();
            enquadrar(camadaDe(selecionado) || camadaGeo);
        }, 380);
    });

    // gancho de inspeção do wireframe (não existe no v3 real)
    window.__mapa = mapa;
}
