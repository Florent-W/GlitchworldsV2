import { Controller } from '@hotwired/stimulus';

const CLE = 'glitchworlds-home-menu-page';

/**
 * Pagination optionnelle des sections de l'accueil (jeux, actus, communauté…).
 * Les thèmes qui ne l'utilisent pas conservent toutes les sections visibles.
 */
export default class extends Controller {
    static targets = ['page', 'dot'];

    connect() {
        this.index = this.lireIndex();
        this.appliquer(false);

        this.observer = new MutationObserver(() => this.appliquer(false));
        this.observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['data-theme'],
        });
    }

    disconnect() {
        this.observer?.disconnect();
    }

    paginationActive() {
        return document.documentElement.dataset.theme === '3ds';
    }

    lireIndex() {
        const valeur = parseInt(localStorage.getItem(CLE) ?? '0', 10);
        const max = Math.max(0, this.pageTargets.length - 1);

        if (Number.isNaN(valeur)) {
            return 0;
        }

        return Math.min(Math.max(0, valeur), max);
    }

    appliquer(persister) {
        if (this.pageTargets.length === 0) {
            return;
        }

        if (this.index >= this.pageTargets.length) {
            this.index = 0;
        }

        const paginationActive = this.paginationActive();

        this.pageTargets.forEach((page, i) => {
            const actif = !paginationActive || i === this.index;
            page.classList.toggle('gw-home-page--active', actif);
            page.hidden = paginationActive && i !== this.index;
        });

        this.dotTargets.forEach((dot, i) => {
            const actif = i === this.index;
            dot.classList.toggle('active', actif);
            dot.setAttribute('aria-selected', actif ? 'true' : 'false');
        });

        this.element.dataset.homeMenuPage = String(this.index);

        if (persister && paginationActive) {
            localStorage.setItem(CLE, String(this.index));
        }
    }

    aller(event) {
        const index = parseInt(event.currentTarget.dataset.index ?? '', 10);

        if (Number.isNaN(index) || index < 0 || index >= this.pageTargets.length) {
            return;
        }

        this.index = index;
        this.appliquer(true);
    }

    suivant() {
        if (!this.paginationActive() || this.pageTargets.length < 2) {
            return;
        }

        this.index = (this.index + 1) % this.pageTargets.length;
        this.appliquer(true);
    }

    precedent() {
        if (!this.paginationActive() || this.pageTargets.length < 2) {
            return;
        }

        this.index = (this.index - 1 + this.pageTargets.length) % this.pageTargets.length;
        this.appliquer(true);
    }
}
