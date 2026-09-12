import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['champ', 'compteur', 'validation', 'bouton'];
    static values = { max: { type: Number, default: 6 } };

    connect() {
        this.verifier();
    }

    verifier() {
        const choix = this.choixSaisis();
        const depassement = choix.length > this.maxValue;

        this.compteurTarget.textContent = `${choix.length} / ${this.maxValue} choix`;
        this.compteurTarget.classList.toggle('text-danger', depassement);
        this.compteurTarget.classList.toggle('fw-semibold', depassement);
        this.validationTarget.textContent = depassement
            ? `Supprime ${choix.length - this.maxValue} choix pour pouvoir publier.`
            : '';
        this.boutonTarget.disabled = depassement;
    }

    valider(event) {
        if (this.choixSaisis().length > this.maxValue) {
            event.preventDefault();
            this.champTarget.focus();
        }
    }

    choixSaisis() {
        return [...new Set(
            this.champTarget.value
                .split(/\r?\n/)
                .map((choix) => choix.trim())
                .filter(Boolean),
        )];
    }
}
