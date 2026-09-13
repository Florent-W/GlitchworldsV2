import { Controller } from '@hotwired/stimulus';
import { Modal } from 'bootstrap';

export default class extends Controller {
    connect() {
        Modal.getOrCreateInstance(this.element).show();
    }
}
