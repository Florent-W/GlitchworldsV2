import { Controller } from '@hotwired/stimulus';
import { Carousel } from 'bootstrap';

export default class extends Controller {
    static targets = ['principale', 'agrandie', 'miniature'];

    connect() {
        if (this.hasPrincipaleTarget) {
            this.principaleTarget.addEventListener('slid.bs.carousel', this.synchroniserMiniatures);
        }
    }

    disconnect() {
        if (this.hasPrincipaleTarget) {
            this.principaleTarget.removeEventListener('slid.bs.carousel', this.synchroniserMiniatures);
        }
    }

    ouvrir({ params: { index } }) {
        if (this.hasAgrandieTarget) {
            Carousel.getOrCreateInstance(this.agrandieTarget, { interval: false }).to(Number(index));
        }
    }

    synchroniserMiniatures = (event) => {
        this.miniatureTargets.forEach((bouton, index) => {
            const active = index === event.to;
            bouton.classList.toggle('active', active);
            bouton.setAttribute('aria-current', active ? 'true' : 'false');
        });
    };
}
