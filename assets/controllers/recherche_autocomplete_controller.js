import { Controller } from '@hotwired/stimulus';

const CLE_RECENTES = 'glitchworlds-recherches-recentes';
const MAX_RECENTES = 6;

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

    ouvrir() {
        this.element.classList.add('is-open');
        this.rechercher();
    }

    rechercher() {
        clearTimeout(this.delai);
        const recherche = this.champTarget.value.trim();
        if (recherche.length < 2) {
            this.afficherRecentes();
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
            this.afficher(donnees, recherche);
        } catch (erreur) {
            if (erreur.name !== 'AbortError') this.fermer();
        }
    }

    afficher(donnees, recherche) {
        const resultats = donnees.resultats ?? [];
        const totaux = donnees.totaux ?? {};
        this.index = -1;
        this.resultatsTarget.replaceChildren();
        if (resultats.length === 0) {
            const vide = document.createElement('div');
            vide.className = 'gw-autocomplete__empty';
            vide.textContent = 'Aucun résultat';
            this.resultatsTarget.append(vide);
        } else {
            const groupes = new Map();
            resultats.forEach(resultat => {
                const categorie = resultat.type ?? '';
                if (!groupes.has(categorie)) groupes.set(categorie, []);
                groupes.get(categorie).push(resultat);
            });

            let position = 0;
            groupes.forEach((elements, categorie) => {
                const entete = this.creerEntete(this.libellerCategorie(categorie));
                const total = totaux[categorie] ?? elements.length;
                const compte = document.createElement('span');
                compte.className = 'gw-autocomplete__count';
                compte.textContent = total > elements.length ? `${elements.length} / ${total}` : total;
                entete.append(compte);
                this.resultatsTarget.append(entete);

                elements.forEach(resultat => {
                    const lien = document.createElement('a');
                    lien.href = resultat.url;
                    lien.className = 'gw-autocomplete__item';
                    lien.id = `gw-search-option-${position}`;
                    position += 1;
                    lien.setAttribute('role', 'option');
                    const visuel = resultat.miniature
                        ? `<img src="${this.echapper(resultat.miniature)}" alt="" loading="lazy">`
                        : `<i class="bi bi-${this.echapper(resultat.icone)}" aria-hidden="true"></i>`;
                    lien.innerHTML = `${visuel}<span><strong>${this.echapper(resultat.titre)}</strong><small>${this.echapper(resultat.detail)}</small></span>`;
                    this.resultatsTarget.append(lien);
                });
            });
        }
        const cumul = donnees.total ?? resultats.length;
        const complet = document.createElement('a');
        complet.href = `${this.formulaireTarget.action}?recherche=${encodeURIComponent(recherche)}`;
        complet.className = 'gw-autocomplete__all';
        complet.textContent = cumul > 0
            ? `Voir ${cumul > 1 ? `les ${cumul} résultats` : 'le résultat'} pour « ${recherche} »`
            : `Voir tous les résultats pour « ${recherche} »`;
        complet.addEventListener('click', () => this.memoriser());
        this.resultatsTarget.append(complet);
        this.resultatsTarget.classList.remove('d-none');
        this.champTarget.setAttribute('aria-expanded', 'true');
    }

    afficherRecentes() {
        this.index = -1;
        this.resultatsTarget.replaceChildren();
        const recentes = this.lireRecentes();

        const entete = this.creerEntete('Recherches récentes');
        if (recentes.length > 0) {
            const effacer = document.createElement('button');
            effacer.type = 'button';
            effacer.className = 'gw-autocomplete__clear';
            effacer.textContent = 'Effacer';
            effacer.addEventListener('click', () => this.effacerRecentes());
            entete.append(effacer);
        }
        this.resultatsTarget.append(entete);

        if (recentes.length === 0) {
            const vide = document.createElement('div');
            vide.className = 'gw-autocomplete__empty';
            vide.textContent = 'Tape au moins 2 caractères pour lancer une recherche.';
            this.resultatsTarget.append(vide);
        } else {
            recentes.forEach((terme, index) => {
                const lien = document.createElement('a');
                lien.href = `${this.formulaireTarget.action}?recherche=${encodeURIComponent(terme)}`;
                lien.className = 'gw-autocomplete__item';
                lien.id = `gw-search-option-${index}`;
                lien.setAttribute('role', 'option');
                lien.innerHTML = `<i class="bi bi-clock-history" aria-hidden="true"></i><span><strong>${this.echapper(terme)}</strong><small>Recherche récente</small></span>`;
                this.resultatsTarget.append(lien);
            });
        }

        this.resultatsTarget.classList.remove('d-none');
        this.champTarget.setAttribute('aria-expanded', 'true');
    }

    creerEntete(libelle) {
        const entete = document.createElement('div');
        entete.className = 'gw-autocomplete__header';
        const titre = document.createElement('span');
        titre.textContent = libelle;
        entete.append(titre);
        return entete;
    }

    libellerCategorie(type) {
        return { 'Jeu': 'Jeux', 'Actualité': 'Actualités', 'Membre': 'Membres' }[type] ?? type;
    }

    lireRecentes() {
        try {
            const stocke = JSON.parse(localStorage.getItem(CLE_RECENTES) ?? '[]');
            return Array.isArray(stocke)
                ? stocke.filter(terme => typeof terme === 'string' && terme.trim() !== '').slice(0, MAX_RECENTES)
                : [];
        } catch (erreur) {
            return [];
        }
    }

    memoriser() {
        const recherche = this.champTarget.value.trim();
        if (recherche.length < 2) return;
        const conservees = this.lireRecentes().filter(terme => terme.toLowerCase() !== recherche.toLowerCase());
        try {
            localStorage.setItem(CLE_RECENTES, JSON.stringify([recherche, ...conservees].slice(0, MAX_RECENTES)));
        } catch (erreur) {
            // Stockage indisponible (navigation privée, quota) : la recherche reste fonctionnelle.
        }
    }

    effacerRecentes() {
        try {
            localStorage.removeItem(CLE_RECENTES);
        } catch (erreur) {
            // Stockage indisponible : rien à nettoyer.
        }
        this.champTarget.focus();
        this.afficherRecentes();
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
        this.element.classList.remove('is-open');
        this.resultatsTarget.classList.add('d-none');
        this.resultatsTarget.replaceChildren();
        this.champTarget.setAttribute('aria-expanded', 'false');
        this.champTarget.removeAttribute('aria-activedescendant');
    }

    echapper(valeur) {
        return String(valeur ?? '').replace(/[&<>'"]/g, caractere => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[caractere]));
    }
}
