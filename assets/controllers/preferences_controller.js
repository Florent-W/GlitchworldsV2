import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['mouvement'];

    connect() {
        this.mouvementTarget.checked = localStorage.getItem('glitchworlds-reduced-motion') === 'true';
    }

    changerMouvement() {
        const reduit = this.mouvementTarget.checked;
        document.documentElement.dataset.reducedMotion = String(reduit);
        localStorage.setItem('glitchworlds-reduced-motion', String(reduit));
    }
}
