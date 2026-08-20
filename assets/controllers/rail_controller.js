import { Controller } from '@hotwired/stimulus';

/**
 * Flèches superposées en haut et en bas de la zone visible du rail.
 */
export default class extends Controller {
    static targets = ['liste', 'haut', 'bas'];

    connect() {
        this.surScroll = () => this.actualiser();
        this.surResize = () => this.actualiser();

        if (this.hasListeTarget) {
            this.listeTarget.addEventListener('scroll', this.surScroll, { passive: true });
        }

        window.addEventListener('resize', this.surResize, { passive: true });

        this.observateur = typeof ResizeObserver !== 'undefined'
            ? new ResizeObserver(() => this.actualiser())
            : null;

        if (this.observateur && this.hasListeTarget) {
            this.observateur.observe(this.listeTarget);
        }

        this.actualiser();
    }

    disconnect() {
        if (this.hasListeTarget) {
            this.listeTarget.removeEventListener('scroll', this.surScroll);
        }

        window.removeEventListener('resize', this.surResize);
        this.observateur?.disconnect();
    }

    monter() {
        this.defiler(-this.pas());
    }

    descendre() {
        this.defiler(this.pas());
    }

    pas() {
        if (!this.hasListeTarget) {
            return 96;
        }

        return Math.max(96, Math.round(this.listeTarget.clientHeight * 0.72));
    }

    defiler(delta) {
        if (!this.hasListeTarget) {
            return;
        }

        const reduit = document.documentElement.getAttribute('data-reduced-motion') === 'true';
        this.listeTarget.scrollBy({ top: delta, behavior: reduit ? 'auto' : 'smooth' });
    }

    actualiser() {
        if (!this.hasListeTarget) {
            return;
        }

        const { scrollTop, scrollHeight, clientHeight } = this.listeTarget;
        const debordement = scrollHeight - clientHeight > 4;

        if (this.hasHautTarget) {
            this.hautTarget.hidden = !debordement || scrollTop <= 4;
        }

        if (this.hasBasTarget) {
            this.basTarget.hidden = !debordement || scrollTop + clientHeight >= scrollHeight - 4;
        }
    }
}
