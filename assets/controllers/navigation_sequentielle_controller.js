import { Controller } from '@hotwired/stimulus';

/**
 * Navigation clavier gauche / droite entre fiches séquentielles (jeu, actualité…).
 * S'appuie sur les liens rel="prev" et rel="next" présents dans la page.
 */
export default class extends Controller {
    naviguerClavier(event) {
        if (!this.doitNaviguer(event)) {
            return;
        }

        const lien = event.key === 'ArrowLeft'
            ? this.element.querySelector('a[rel="prev"]')
            : event.key === 'ArrowRight'
                ? this.element.querySelector('a[rel="next"]')
                : null;

        if (!lien) {
            return;
        }

        event.preventDefault();
        lien.click();
    }

    doitNaviguer(event) {
        if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') {
            return false;
        }

        if (event.altKey || event.ctrlKey || event.metaKey || event.shiftKey) {
            return false;
        }

        const cible = event.target;
        if (!(cible instanceof Element)) {
            return true;
        }

        if (cible.closest('input, textarea, select, button, [contenteditable="true"], .carousel, .modal')) {
            return false;
        }

        if (document.querySelector('.modal.show, .gw-search.is-open, .lg-outer.lg-visible')) {
            return false;
        }

        return true;
    }
}
