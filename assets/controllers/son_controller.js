import { Controller } from '@hotwired/stimulus';

const CLE = 'glitchworlds-sons';

/**
 * Préférence audio globale. Elle contrôle les vidéos d’arrière-plan et les
 * éventuelles musiques, sans produire de bruitages lors des interactions.
 */
export default class extends Controller {
    static targets = ['icone'];

    connect() {
        this.root = document.documentElement;
        this.actif = this.lirePreference();
        this.appliquerEtat();
        this.notifierChangement();
    }

    basculer() {
        this.actif = !this.actif;
        localStorage.setItem(CLE, this.actif ? '1' : '0');
        this.appliquerEtat();
        this.notifierChangement();
    }

    lirePreference() {
        const stocke = localStorage.getItem(CLE);
        return stocke === null || stocke === '1' || stocke === 'true';
    }

    appliquerEtat() {
        this.root.dataset.gwSons = this.actif ? '1' : '0';
        this.element.setAttribute('aria-pressed', String(this.actif));
        this.element.setAttribute('aria-label', this.actif ? 'Couper le son des vidéos et musiques' : 'Activer le son des vidéos et musiques');
        this.element.title = this.actif ? 'Son activé' : 'Son coupé';

        if (this.hasIconeTarget) {
            this.iconeTarget.className = `bi ${this.actif ? 'bi-volume-up-fill' : 'bi-volume-mute-fill'}`;
        }
    }

    notifierChangement() {
        window.dispatchEvent(new CustomEvent('glitchworlds:sons-change', {
            detail: { actif: this.actif },
        }));
    }
}
