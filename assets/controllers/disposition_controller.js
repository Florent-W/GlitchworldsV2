import { Controller } from '@hotwired/stimulus';

const CLE = 'glitchworlds-disposition';
const CLE_LEGACY = 'glitchworlds-disposition-accueil';
const MODES = ['grille', 'double', 'liste'];

/**
 * Bascule grille (3 colonnes), 2 par colonne (cartes compactes) ou liste.
 */
export default class extends Controller {
    static targets = ['boutonGrille', 'boutonDouble', 'boutonListe'];

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

    lire() {
        const valeur = localStorage.getItem(CLE) ?? localStorage.getItem(CLE_LEGACY);

        if (valeur === 'liste') {
            return 'liste';
        }

        if (valeur === 'double' || valeur === '2') {
            return 'double';
        }

        return 'grille';
    }

    appliquer(mode, persister) {
        const modeEffectif = MODES.includes(mode) ? mode : 'grille';

        this.element.dataset.dispositionMode = modeEffectif;
        this.element.classList.remove('gw-disposition--grille', 'gw-disposition--double', 'gw-disposition--liste');
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

        if (persister) {
            localStorage.setItem(CLE, modeEffectif);
        }
    }
}
