import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        if (this.element.dataset.adsenseInitialise === 'true') return;

        try {
            window.adsbygoogle = window.adsbygoogle || [];
            window.adsbygoogle.push({});
            this.element.dataset.adsenseInitialise = 'true';
        } catch (error) {
            // Un bloqueur de publicité ou un script Google indisponible ne doit pas casser la page.
        }
    }
}
