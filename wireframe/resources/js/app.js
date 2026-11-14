import '@fontsource-variable/space-grotesk';
import '@fontsource-variable/jetbrains-mono';
import 'leaflet/dist/leaflet.css';

import Alpine from 'alpinejs';
import { iniciarMapa } from './mapa';

window.Alpine = Alpine;

Alpine.data('shell', (inicial = 'recolhida') => ({
    altura: inicial,
    copiado: null,

    alternarAltura() {
        const ordem = ['recolhida', 'meia', 'cheia'];
        const i = ordem.indexOf(this.altura);
        this.altura = ordem[(i + 1) % ordem.length];
        window.dispatchEvent(new CustomEvent('folha:mudou', { detail: this.altura }));
    },

    abrir(nivel) {
        this.altura = nivel;
        window.dispatchEvent(new CustomEvent('folha:mudou', { detail: nivel }));
    },

    copiar(texto) {
        const fim = () => {
            this.copiado = texto;
            setTimeout(() => (this.copiado = null), 1600);
        };

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(texto).then(fim).catch(fim);
        } else {
            const t = document.createElement('textarea');
            t.value = texto;
            t.style.position = 'fixed';
            t.style.opacity = '0';
            document.body.appendChild(t);
            t.select();
            document.execCommand('copy');
            document.body.removeChild(t);
            fim();
        }
    },
}));

Alpine.start();

document.addEventListener('keydown', (e) => {
    const alvo = e.target;
    const digitando = alvo && /^(INPUT|TEXTAREA)$/.test(alvo.tagName);
    if (digitando) return;

    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        document.querySelector('[data-busca]')?.focus();
    }
});

document.addEventListener('DOMContentLoaded', () => {
    iniciarMapa();
});
