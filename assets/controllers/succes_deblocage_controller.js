import { Controller } from '@hotwired/stimulus';

/**
 * Popup « succès débloqué » façon overlay de console. Les cartes sont rendues
 * par Twig et animées en CSS : ce contrôleur ne fait qu'ajouter le retour
 * sonore à l'ouverture et retirer chaque carte une fois sa sortie terminée.
 */
export default class extends Controller {
    connect() {
        this.placerSousTopbar();

        this.surDebut = event => {
            if (event.animationName === 'gw-succes-entree') {
                window.dispatchEvent(new CustomEvent('glitchworlds:son', {
                    detail: { type: 'succes' },
                }));
            }
        };
        this.surFin = event => {
            if (event.animationName === 'gw-succes-sortie') {
                event.target.remove();
            }
        };

        this.element.addEventListener('animationstart', this.surDebut);
        this.element.addEventListener('animationend', this.surFin);
    }

    disconnect() {
        this.element.removeEventListener('animationstart', this.surDebut);
        this.element.removeEventListener('animationend', this.surFin);
    }

    /**
     * La topbar n'a pas la même hauteur selon le thème : on la mesure pour
     * poser la popup juste en dessous plutôt que de figer un décalage.
     */
    placerSousTopbar() {
        const topbar = document.querySelector('.gw-topbar');
        const bas = topbar ? topbar.getBoundingClientRect().bottom : 0;

        // Page déjà défilée : la topbar est hors écran, on colle en haut.
        this.element.style.setProperty('--gw-succes-decalage', `${Math.max(bas + 12, 16)}px`);
    }
}
