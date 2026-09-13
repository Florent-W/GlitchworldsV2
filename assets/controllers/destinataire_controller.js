import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['recherche', 'identifiant', 'resultats'];
    static values = { url: String };

    connect() {
        this.delai = null;
        this.requete = null;
        this.fermetureLiee = (event) => {
            if (!this.element.contains(event.target)) this.fermer();
        };
        document.addEventListener('click', this.fermetureLiee);
    }

    disconnect() {
        clearTimeout(this.delai);
        this.requete?.abort();
        document.removeEventListener('click', this.fermetureLiee);
    }

    rechercher() {
        this.identifiantTarget.value = '';
        clearTimeout(this.delai);
        const terme = this.rechercheTarget.value.trim();
        if (terme.length < 2) {
            this.message('Saisissez au moins 2 caractères.');
            return;
        }
        this.delai = setTimeout(() => this.charger(terme), 220);
    }

    async charger(terme) {
        this.requete?.abort();
        this.requete = new AbortController();
        try {
            const url = new URL(this.urlValue, window.location.origin);
            url.searchParams.set('recherche', terme);
            const reponse = await fetch(url, { signal: this.requete.signal, headers: { Accept: 'application/json' } });
            if (!reponse.ok) throw new Error();
            const donnees = await reponse.json();
            if (this.rechercheTarget.value.trim() !== terme) return;
            this.afficher(donnees.resultats ?? []);
        } catch (erreur) {
            if (erreur.name !== 'AbortError') this.message('Recherche momentanément indisponible.');
        }
    }

    afficher(membres) {
        this.resultatsTarget.replaceChildren();
        if (membres.length === 0) {
            this.message('Aucun membre trouvé.');
            return;
        }
        membres.forEach((membre) => {
            const bouton = document.createElement('button');
            bouton.type = 'button';
            bouton.className = 'gw-member-search__result';
            bouton.innerHTML = '<i class="bi bi-person-fill" aria-hidden="true"></i>';
            const pseudo = document.createElement('strong');
            pseudo.textContent = membre.pseudo;
            bouton.append(pseudo);
            bouton.addEventListener('click', () => this.selectionner(membre));
            this.resultatsTarget.append(bouton);
        });
        this.ouvrir();
    }

    selectionner(membre) {
        this.identifiantTarget.value = membre.id;
        this.rechercheTarget.value = membre.pseudo;
        this.fermer();
    }

    message(texte) {
        const message = document.createElement('div');
        message.className = 'gw-autocomplete__empty';
        message.textContent = texte;
        this.resultatsTarget.replaceChildren(message);
        this.ouvrir();
    }

    ouvrir() {
        this.resultatsTarget.hidden = false;
        this.rechercheTarget.setAttribute('aria-expanded', 'true');
    }

    fermer() {
        this.resultatsTarget.hidden = true;
        this.rechercheTarget.setAttribute('aria-expanded', 'false');
    }
}
