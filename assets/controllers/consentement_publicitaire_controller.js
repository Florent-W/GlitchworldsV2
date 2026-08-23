import { Controller } from '@hotwired/stimulus';

/**
 * Rend disponible le point d'entrée officiel de révocation de la CMP Google.
 * Le bouton reste masqué tant que l'API n'est pas prête, afin d'éviter une
 * action sans effet lorsque AdSense est bloqué ou n'est pas configuré.
 */
export default class extends Controller {
    connect() {
        window.googlefc = window.googlefc || {};
        window.googlefc.callbackQueue = window.googlefc.callbackQueue || [];
        window.googlefc.callbackQueue.push({
            CONSENT_API_READY: () => {
                if (typeof window.googlefc.showRevocationMessage === 'function') {
                    this.element.hidden = false;
                }
            },
        });
    }

    ouvrir(event) {
        event.preventDefault();

        if (typeof window.googlefc?.showRevocationMessage === 'function') {
            window.googlefc.showRevocationMessage();
        }
    }
}
