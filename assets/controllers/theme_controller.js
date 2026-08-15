import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['icone'];

    connect() {
        this.media = window.matchMedia('(prefers-color-scheme: dark)');
        this.suivreSysteme = !localStorage.getItem('glitchworlds-theme');
        this.changementSysteme = event => {
            if (this.suivreSysteme) this.appliquer(event.matches ? 'dark' : 'light', false);
        };
        this.media.addEventListener('change', this.changementSysteme);
        this.appliquer(document.documentElement.dataset.bsTheme === 'dark' ? 'dark' : 'light', false);
    }

    disconnect() {
        this.media?.removeEventListener('change', this.changementSysteme);
    }

    basculer() {
        const theme = document.documentElement.dataset.bsTheme === 'dark' ? 'light' : 'dark';
        this.suivreSysteme = false;
        this.appliquer(theme, true);
    }

    appliquer(theme, memoriser) {
        const sombre = theme === 'dark';
        document.documentElement.dataset.bsTheme = theme;
        if (memoriser) localStorage.setItem('glitchworlds-theme', theme);
        this.element.setAttribute('aria-pressed', String(sombre));
        this.element.setAttribute('aria-label', sombre ? 'Activer le thème clair' : 'Activer le thème sombre');
        this.element.title = sombre ? 'Activer le thème clair' : 'Activer le thème sombre';
        this.iconeTarget.className = `bi ${sombre ? 'bi-sun-fill' : 'bi-moon-stars-fill'}`;
    }
}
