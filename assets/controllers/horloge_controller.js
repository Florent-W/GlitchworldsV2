import { Controller } from '@hotwired/stimulus';

/**
 * Affiche l'heure et la date dans le fuseau local du navigateur.
 * Se met à jour chaque minute.
 */
export default class extends Controller {
    static targets = ['heure', 'date', 'time'];

    connect() {
        this.refresh();
        // Aligne le prochain tick sur le début de minute suivante
        const msBeforeNextMinute = 60000 - (Date.now() % 60000);
        this.timeoutId = window.setTimeout(() => {
            this.refresh();
            this.intervalId = window.setInterval(() => this.refresh(), 60000);
        }, msBeforeNextMinute);
    }

    disconnect() {
        if (this.timeoutId) {
            window.clearTimeout(this.timeoutId);
        }
        if (this.intervalId) {
            window.clearInterval(this.intervalId);
        }
    }

    refresh() {
        const now = new Date();

        // Sans timeZone explicite → fuseau du navigateur (celui de l'utilisateur)
        const heure = new Intl.DateTimeFormat('fr-FR', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
        }).format(now);

        const date = new Intl.DateTimeFormat('fr-FR', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        }).format(now);

        if (this.hasHeureTarget) {
            this.heureTarget.textContent = heure;
        }
        if (this.hasDateTarget) {
            this.dateTarget.textContent = this.capitalize(date);
        }
        if (this.hasTimeTarget) {
            this.timeTarget.setAttribute('datetime', now.toISOString());
        }
    }

    capitalize(text) {
        return text.charAt(0).toUpperCase() + text.slice(1);
    }
}
