import { Controller } from '@hotwired/stimulus';

const CLE = 'glitchworlds-disposition';
const CLE_LEGACY = 'glitchworlds-disposition-accueil';

/**
 * Bascule grille / liste pour les catalogues (accueil, jeux, actualités).
 */
export default class extends Controller {
    static targets = ['boutonGrille', 'boutonListe'];

    connect() {
        this.appliquer(this.lire(), false);
    }

    choisirGrille() {
        this.appliquer('grille', true);
    }

    choisirListe() {
        this.appliquer('liste', true);
    }

    lire() {
        const valeur = localStorage.getItem(CLE) ?? localStorage.getItem(CLE_LEGACY);

        return valeur === 'liste' ? 'liste' : 'grille';
    }

    appliquer(mode, persister) {
        const liste = mode === 'liste';

        this.element.classList.toggle('gw-disposition--liste', liste);
        this.element.classList.toggle('gw-disposition--grille', !liste);

        if (this.hasBoutonGrilleTarget) {
            this.boutonGrilleTarget.classList.toggle('active', !liste);
            this.boutonGrilleTarget.setAttribute('aria-pressed', !liste ? 'true' : 'false');
        }

        if (this.hasBoutonListeTarget) {
            this.boutonListeTarget.classList.toggle('active', liste);
            this.boutonListeTarget.setAttribute('aria-pressed', liste ? 'true' : 'false');
        }

        if (persister) {
            localStorage.setItem(CLE, mode);
        }
    }
}
