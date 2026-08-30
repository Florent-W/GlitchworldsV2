import { Controller } from '@hotwired/stimulus';

// Durée au-delà de laquelle le contenu déjà chargé est considéré comme périmé.
const DUREE_FRAICHEUR = 30000;

export default class extends Controller {
    static targets = ['bouton', 'panneau'];
    static values = { url: String };

    connect() {
        this.charge = 0;
        this.requete = null;
        this.fermerAuClic = (event) => {
            if (!this.element.contains(event.target)) this.fermer();
        };
        this.fermerAuClavier = (event) => {
            if (event.key === 'Escape' && this.ouvert) { this.fermer(); this.boutonTarget.focus(); }
        };
        document.addEventListener('click', this.fermerAuClic);
        document.addEventListener('keydown', this.fermerAuClavier);
    }

    disconnect() {
        this.requete?.abort();
        document.removeEventListener('click', this.fermerAuClic);
        document.removeEventListener('keydown', this.fermerAuClavier);
    }

    get ouvert() {
        return this.element.classList.contains('is-open');
    }

    basculer(event) {
        event.preventDefault();
        this.ouvert ? this.fermer() : this.ouvrir();
    }

    ouvrir() {
        this.element.classList.add('is-open');
        this.panneauTarget.hidden = false;
        this.boutonTarget.setAttribute('aria-expanded', 'true');
        if (Date.now() - this.charge > DUREE_FRAICHEUR) this.charger();
    }

    fermer() {
        if (!this.ouvert) return;
        this.element.classList.remove('is-open');
        this.panneauTarget.hidden = true;
        this.boutonTarget.setAttribute('aria-expanded', 'false');
    }

    async charger() {
        this.requete?.abort();
        this.requete = new AbortController();
        if (this.charge === 0) this.panneauTarget.innerHTML = '<p class="gw-notif-panel__empty">Chargement…</p>';
        try {
            const reponse = await fetch(this.urlValue, { signal: this.requete.signal, headers: { 'X-Requested-With': 'fetch' } });
            if (!reponse.ok) throw new Error('Notifications indisponibles');
            this.panneauTarget.innerHTML = await reponse.text();
            this.charge = Date.now();
        } catch (erreur) {
            if (erreur.name === 'AbortError') return;
            this.panneauTarget.innerHTML = '<p class="gw-notif-panel__empty">Impossible de charger les notifications.</p>';
        }
    }
}
