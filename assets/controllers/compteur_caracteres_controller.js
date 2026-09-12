import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['champ', 'compteur'];
    static values = { max: Number };

    connect() {
        this.mettreAJour();
    }

    mettreAJour() {
        const nombre = Array.from(this.champTarget.value).length;
        const maximum = this.hasMaxValue ? this.maxValue : this.champTarget.maxLength;

        this.compteurTarget.textContent = `${nombre} / ${maximum} caractères`;
    }
}
