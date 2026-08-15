import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['champ', 'resultats', 'formulaire'];
    static values = { url: String };

    connect() {
        this.index = -1;
        this.delai = null;
        this.requete = null;
        this.fermerAuClic = (event) => {
            if (!this.element.contains(event.target)) this.fermer();
        };
        document.addEventListener('click', this.fermerAuClic);
    }

    disconnect() {
        clearTimeout(this.delai);
        this.requete?.abort();
        document.removeEventListener('click', this.fermerAuClic);
    }

    rechercher() {
        clearTimeout(this.delai);
        const recherche = this.champTarget.value.trim();
        if (recherche.length < 2) {
            this.fermer();
            return;
        }
        this.delai = setTimeout(() => this.charger(recherche), 250);
    }

    async charger(recherche) {
        this.requete?.abort();
        this.requete = new AbortController();
        try {
            const url = new URL(this.urlValue, window.location.origin);
            url.searchParams.set('recherche', recherche);
            const reponse = await fetch(url, { signal: this.requete.signal, headers: { Accept: 'application/json' } });
            if (!reponse.ok) throw new Error('Recherche indisponible');
            const donnees = await reponse.json();
            if (this.champTarget.value.trim() !== recherche) return;
            this.afficher(donnees.resultats, recherche);
        } catch (erreur) {
            if (erreur.name !== 'AbortError') this.fermer();
        }
    }

    afficher(resultats, recherche) {
        this.index = -1;
        this.resultatsTarget.replaceChildren();
        if (resultats.length === 0) {
            const vide = document.createElement('div');
            vide.className = 'gw-autocomplete__empty';
            vide.textContent = 'Aucun résultat';
            this.resultatsTarget.append(vide);
        } else {
            resultats.forEach((resultat, index) => {
                const lien = document.createElement('a');
                lien.href = resultat.url;
                lien.className = 'gw-autocomplete__item';
                lien.id = `gw-search-option-${index}`;
                lien.setAttribute('role', 'option');
                const visuel = resultat.miniature
                    ? `<img src="${this.echapper(resultat.miniature)}" alt="" loading="lazy">`
                    : `<i class="bi bi-${this.echapper(resultat.icone)}" aria-hidden="true"></i>`;
                lien.innerHTML = `${visuel}<span><strong>${this.echapper(resultat.titre)}</strong><small>${this.echapper(resultat.type)} · ${this.echapper(resultat.detail)}</small></span>`;
                this.resultatsTarget.append(lien);
            });
        }
        const complet = document.createElement('a');
        complet.href = `${this.formulaireTarget.action}?recherche=${encodeURIComponent(recherche)}`;
        complet.className = 'gw-autocomplete__all';
        complet.textContent = `Voir tous les résultats pour « ${recherche} »`;
        this.resultatsTarget.append(complet);
        this.resultatsTarget.classList.remove('d-none');
        this.champTarget.setAttribute('aria-expanded', 'true');
    }

    naviguer(event) {
        const options = [...this.resultatsTarget.querySelectorAll('[role="option"]')];
        if (event.key === 'Escape') { this.fermer(); return; }
        if (this.resultatsTarget.classList.contains('d-none') || options.length === 0) return;
        if (event.key === 'ArrowDown') this.index = Math.min(this.index + 1, options.length - 1);
        else if (event.key === 'ArrowUp') this.index = Math.max(this.index - 1, 0);
        else if (event.key === 'Enter' && this.index >= 0) { event.preventDefault(); options[this.index].click(); return; }
        else return;
        event.preventDefault();
        options.forEach((option, index) => option.classList.toggle('is-active', index === this.index));
        this.champTarget.setAttribute('aria-activedescendant', options[this.index].id);
    }

    fermer() {
        this.index = -1;
        this.resultatsTarget.classList.add('d-none');
        this.resultatsTarget.replaceChildren();
        this.champTarget.setAttribute('aria-expanded', 'false');
        this.champTarget.removeAttribute('aria-activedescendant');
    }

    echapper(valeur) {
        return String(valeur ?? '').replace(/[&<>'"]/g, caractere => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[caractere]));
    }
}
