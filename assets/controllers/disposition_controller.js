import { Controller } from '@hotwired/stimulus';

const CLE = 'glitchworlds-disposition';
const CLE_LEGACY = 'glitchworlds-disposition-accueil';
const MODES = ['grille', 'double', 'liste', 'carrousel'];

/**
 * Bascule grille (4 colonnes), 2 par colonne (cartes compactes) ou liste.
 */
export default class extends Controller {
    static targets = ['boutonGrille', 'boutonDouble', 'boutonListe', 'boutonCarrousel'];

    connect() {
        this.appliquer(this.lire(), false);
    }

    choisirGrille() {
        this.appliquer('grille', true);
    }

    choisirDouble() {
        this.appliquer('double', true);
    }

    choisirListe() {
        this.appliquer('liste', true);
    }

    choisirCarrousel() {
        this.appliquer('carrousel', true);
    }

    lire() {
        const valeur = localStorage.getItem(CLE) ?? localStorage.getItem(CLE_LEGACY);

        if (valeur === 'liste') {
            return 'liste';
        }

        if (valeur === 'double' || valeur === '2') {
            return 'double';
        }

        if (valeur === 'carrousel') {
            return 'carrousel';
        }

        return 'grille';
    }

    appliquer(mode, persister) {
        // La préférence est partagée par toutes les pages, mais le carrousel n'est
        // proposé que sur l'accueil : ailleurs, on retombe sur la grille plutôt que
        // de laisser les quatre boutons sans état actif.
        const disponible = MODES.includes(mode) && (mode !== 'carrousel' || this.hasBoutonCarrouselTarget);
        const modeEffectif = disponible ? mode : 'grille';

        this.element.dataset.dispositionMode = modeEffectif;
        this.element.classList.remove('gw-disposition--grille', 'gw-disposition--double', 'gw-disposition--liste', 'gw-disposition--carrousel');
        this.element.classList.add(`gw-disposition--${modeEffectif}`);

        if (this.hasBoutonGrilleTarget) {
            const actif = modeEffectif === 'grille';
            this.boutonGrilleTarget.classList.toggle('active', actif);
            this.boutonGrilleTarget.setAttribute('aria-pressed', actif ? 'true' : 'false');
        }

        if (this.hasBoutonDoubleTarget) {
            const actif = modeEffectif === 'double';
            this.boutonDoubleTarget.classList.toggle('active', actif);
            this.boutonDoubleTarget.setAttribute('aria-pressed', actif ? 'true' : 'false');
        }

        if (this.hasBoutonListeTarget) {
            const actif = modeEffectif === 'liste';
            this.boutonListeTarget.classList.toggle('active', actif);
            this.boutonListeTarget.setAttribute('aria-pressed', actif ? 'true' : 'false');
        }

        if (this.hasBoutonCarrouselTarget) {
            const actif = modeEffectif === 'carrousel';
            this.boutonCarrouselTarget.classList.toggle('active', actif);
            this.boutonCarrouselTarget.setAttribute('aria-pressed', actif ? 'true' : 'false');
        }

        if (persister) {
            localStorage.setItem(CLE, modeEffectif);
        }
    }
}
