import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['icone', 'option'];

    connect() {
        this.media = window.matchMedia('(prefers-color-scheme: dark)');
        this.suivreSysteme = !localStorage.getItem('glitchworlds-theme');
        this.changementSysteme = event => {
            if (this.suivreSysteme) this.appliquer(event.matches ? 'dark' : 'light', false);
        };
        this.media.addEventListener('change', this.changementSysteme);
        this.changementExterne = () => this.actualiser();
        window.addEventListener('glitchworlds:theme-change', this.changementExterne);
        this.actualiser();
    }

    disconnect() {
        this.media?.removeEventListener('change', this.changementSysteme);
        window.removeEventListener('glitchworlds:theme-change', this.changementExterne);
    }

    basculer() {
        const theme = document.documentElement.dataset.bsTheme === 'dark' ? 'light' : 'dark';
        this.suivreSysteme = false;
        this.appliquer(theme, true);
        window.dispatchEvent(new CustomEvent('glitchworlds:theme-change'));
    }

    selectionner(event) {
        const choix = event.params.theme;
        this.suivreSysteme = choix === 'system';

        if (this.suivreSysteme) {
            localStorage.removeItem('glitchworlds-theme');
            this.appliquer(this.media.matches ? 'dark' : 'light', false);
        } else {
            this.appliquer(choix, true);
        }

        window.dispatchEvent(new CustomEvent('glitchworlds:theme-change'));
    }

    appliquer(theme, memoriser) {
        const sombre = theme === 'dark' || theme === 'wave';
        document.documentElement.dataset.bsTheme = sombre ? 'dark' : 'light';
        document.documentElement.dataset.gwTheme = theme;
        if (memoriser) localStorage.setItem('glitchworlds-theme', theme);
        if (this.hasIconeTarget) {
            this.element.setAttribute('aria-pressed', String(sombre));
            this.element.setAttribute('aria-label', sombre ? 'Activer le thème clair' : 'Activer le thème sombre');
            this.element.title = sombre ? 'Activer le thème clair' : 'Activer le thème sombre';
            this.iconeTarget.className = `bi ${sombre ? 'bi-sun-fill' : 'bi-moon-stars-fill'}`;
        }
    }

    actualiser() {
        const choix = localStorage.getItem('glitchworlds-theme') || 'system';
        this.suivreSysteme = choix === 'system';
        this.appliquer(choix === 'system' ? (this.media.matches ? 'dark' : 'light') : choix, false);
        this.optionTargets.forEach(option => {
            const actif = option.dataset.themeValue === choix;
            option.classList.toggle('is-active', actif);
            option.setAttribute('aria-pressed', String(actif));
        });
    }
}
