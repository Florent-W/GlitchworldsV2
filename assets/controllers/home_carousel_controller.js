import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['piste', 'precedent', 'suivant'];

    connect() {
        this.mettreAJour = this.mettreAJour.bind(this);
        this.pisteTarget.addEventListener('scroll', this.mettreAJour, { passive: true });
        this.observateur = new ResizeObserver(this.mettreAJour);
        this.observateur.observe(this.pisteTarget);
        requestAnimationFrame(this.mettreAJour);
    }

    disconnect() {
        this.pisteTarget.removeEventListener('scroll', this.mettreAJour);
        this.observateur?.disconnect();
    }

    precedent() {
        this.defiler(-1);
    }

    suivant() {
        this.defiler(1);
    }

    defiler(direction) {
        this.pisteTarget.scrollBy({ left: direction * this.pisteTarget.clientWidth, behavior: 'smooth' });
    }

    mettreAJour() {
        const piste = this.pisteTarget;
        const maximum = Math.max(0, piste.scrollWidth - piste.clientWidth - 2);
        this.precedentTarget.disabled = piste.scrollLeft <= 2;
        this.suivantTarget.disabled = piste.scrollLeft >= maximum;
    }
}
