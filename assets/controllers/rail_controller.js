import { Controller } from '@hotwired/stimulus';

/**
 * Flèches superposées en haut et en bas de la zone visible du rail.
 */
export default class extends Controller {
    static targets = ['liste', 'haut', 'bas', 'selection'];

    connect() {
        this.surScroll = () => this.actualiser();
        this.surResize = () => this.actualiser();
        this.surSelection = (event) => this.afficherSelection(event);

        if (this.hasListeTarget) {
            this.listeTarget.addEventListener('scroll', this.surScroll, { passive: true });
            this.listeTarget.addEventListener('focusin', this.surSelection);
            this.listeTarget.addEventListener('pointerover', this.surSelection);
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
            this.listeTarget.removeEventListener('focusin', this.surSelection);
            this.listeTarget.removeEventListener('pointerover', this.surSelection);
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

        if (document.documentElement.getAttribute('data-theme') === 'ps3' && !this.estHorizontal()) {
            const premierItem = this.listeTarget.querySelector('li');
            const hauteurItem = premierItem?.getBoundingClientRect().height || 72;
            const styles = getComputedStyle(this.listeTarget);
            const espacement = Number.parseFloat(styles.rowGap || styles.gap) || 0;

            return Math.max(48, Math.round(hauteurItem + espacement));
        }

        return this.estHorizontal()
            ? Math.max(96, Math.round(this.listeTarget.clientWidth * 0.72))
            : Math.max(96, Math.round(this.listeTarget.clientHeight * 0.72));
    }

    defiler(delta) {
        if (!this.hasListeTarget) {
            return;
        }

        const reduit = document.documentElement.getAttribute('data-reduced-motion') === 'true';
        this.listeTarget.scrollBy(this.estHorizontal()
            ? { left: delta, behavior: reduit ? 'auto' : 'smooth' }
            : { top: delta, behavior: reduit ? 'auto' : 'smooth' });
    }

    estHorizontal() {
        return getComputedStyle(this.listeTarget).overflowX === 'auto';
    }

    afficherSelection(event) {
        if (!this.hasSelectionTarget) return;

        const item = event.target.closest('.gw-rail__item');
        if (item) this.selectionTarget.textContent = item.getAttribute('title') || '';
    }

    actualiser() {
        if (!this.hasListeTarget) {
            return;
        }

        const horizontal = this.estHorizontal();
        const position = horizontal ? this.listeTarget.scrollLeft : this.listeTarget.scrollTop;
        const taille = horizontal ? this.listeTarget.scrollWidth : this.listeTarget.scrollHeight;
        const visible = horizontal ? this.listeTarget.clientWidth : this.listeTarget.clientHeight;
        const debordement = taille - visible > 4;

        if (this.hasHautTarget) {
            this.hautTarget.hidden = !debordement || position <= 4;
        }

        if (this.hasBasTarget) {
            this.basTarget.hidden = !debordement || position + visible >= taille - 4;
        }
    }
}
