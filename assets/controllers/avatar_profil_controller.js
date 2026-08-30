import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['form', 'input'];

    ouvrir() {
        this.inputTarget.click();
    }

    envoyer() {
        if (this.inputTarget.files?.length) {
            this.formTarget.requestSubmit();
        }
    }
}
