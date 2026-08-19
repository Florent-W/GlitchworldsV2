import { Controller } from '@hotwired/stimulus';

/**
 * Bouton flottant « retour en haut », visible une fois la page suffisamment
 * défilée. Peut être désactivé depuis les paramètres (préférence locale).
 */
export default class extends Controller {
    static values = {
        seuil: { type: Number, default: 480 },
    };

    connect() {
        this.visible = false;
        this.actif = this.lirePreference();
        this.surScroll = () => this.actualiser();
        this.surPreference = event => {
            this.actif = Boolean(event.detail?.actif);
            this.actualiser();
        };

        window.addEventListener('scroll', this.surScroll, { passive: true });
        window.addEventListener('glitchworlds:retour-haut-change', this.surPreference);
        this.actualiser();
    }

    disconnect() {
        window.removeEventListener('scroll', this.surScroll);
        window.removeEventListener('glitchworlds:retour-haut-change', this.surPreference);
    }

    remonter() {
        if (!this.actif) {
            return;
        }

        const reduit = this.rootReduitMouvement();
        window.scrollTo({
            top: 0,
            behavior: reduit ? 'auto' : 'smooth',
        });

        window.dispatchEvent(new CustomEvent('glitchworlds:son', {
            detail: { type: 'retour' },
        }));
    }

    actualiser() {
        const doitAfficher = this.actif && window.scrollY >= this.seuilValue;
        if (doitAfficher === this.visible) {
            return;
        }

        this.visible = doitAfficher;
        this.element.classList.toggle('is-visible', this.visible);
        this.element.toggleAttribute('hidden', !this.visible);
        this.element.setAttribute('aria-hidden', String(!this.visible));
        this.element.tabIndex = this.visible ? 0 : -1;
    }

    lirePreference() {
        if (document.documentElement.dataset.gwRetourHaut === '0') {
            return false;
        }

        return localStorage.getItem('glitchworlds-retour-haut') !== '0';
    }

    rootReduitMouvement() {
        return document.documentElement.dataset.reducedMotion === 'true'
            || window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }
}
