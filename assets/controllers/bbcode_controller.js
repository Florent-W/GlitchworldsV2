import { Controller } from '@hotwired/stimulus';
import { Tooltip } from 'bootstrap';
import { Picker } from 'emoji-picker-element';
import traductionEmojiFr from 'emoji-picker-element/i18n/fr';
import { MODELES_BBCODE } from '../bbcode_templates.js';

export default class extends Controller {
    static targets = ['contenu', 'apercu', 'rendu', 'boutonApercu', 'groupeTemplates', 'menuEmoji'];
    static values = { apercuUrl: String, jeton: String };

    connect() {
        this.apercuActif = false;
        this.delaiApercu = null;
        this.fermerEmojisAuClic = this.fermerEmojisAuClic.bind(this);
        this.tooltips = [];
        this.element.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => {
            this.tooltips.push(new Tooltip(element));
        });
        this.actualiserVisibiliteTemplates();
    }

    disconnect() {
        window.clearTimeout(this.delaiApercu);
        document.removeEventListener('click', this.fermerEmojisAuClic);
        this.observateurTheme?.disconnect();
        this.tooltips.forEach((tooltip) => tooltip.dispose());
        this.tooltips = [];
    }

    basculerApercu() {
        this.apercuActif = !this.apercuActif;
        this.apercuTarget.classList.toggle('d-none', !this.apercuActif);
        this.boutonApercuTarget.setAttribute('aria-expanded', String(this.apercuActif));
        this.boutonApercuTarget.innerHTML = this.apercuActif
            ? '<i class="bi bi-eye-slash-fill me-1" aria-hidden="true"></i>Masquer l’aperçu'
            : '<i class="bi bi-eye-fill me-1" aria-hidden="true"></i>Afficher l’aperçu';
        if (this.apercuActif) this.actualiserApercu();
    }

    planifierApercu() {
        this.actualiserVisibiliteTemplates();
        if (!this.apercuActif) return;
        window.clearTimeout(this.delaiApercu);
        this.delaiApercu = window.setTimeout(() => this.actualiserApercu(), 350);
    }

    appliquerTemplate(event) {
        const estSection = event.params.mode === 'section';
        if (!estSection && this.contenuTarget.value.trim() !== '') {
            return;
        }

        const modele = MODELES_BBCODE[event.params.id];
        if (!modele) {
            return;
        }

        if (estSection) {
            const separation = this.contenuTarget.value.trim() === '' ? '' : '\n\n';
            this.remplacerSelection(`${separation}${modele.contenu}`);
        } else {
            this.contenuTarget.value = modele.contenu;
            this.contenuTarget.focus();
            this.contenuTarget.dispatchEvent(new Event('input', { bubbles: true }));
        }
        if (this.apercuActif) {
            this.actualiserApercu();
        }
    }

    ajouterPartie(event) {
        const selection = event.currentTarget;
        const modele = MODELES_BBCODE[selection.value];
        if (modele) this.insererPartie(modele.contenu);
        selection.value = '';
    }

    ajouterPartiePersonnalisee() {
        const titre = window.prompt('Titre de la partie');
        if (!titre?.trim()) return;
        const titreSecurise = titre.trim().replace(/["\[\]]/g, '');
        this.insererPartie(`[section type=personnalisee titre="${titreSecurise}"]\nRédigez cette partie.\n[/section]`);
    }

    insererPartie(contenu) {
        const separation = this.contenuTarget.value.trim() === '' ? '' : '\n\n';
        this.remplacerSelection(`${separation}${contenu}`);
        if (this.apercuActif) this.actualiserApercu();
    }

    actualiserVisibiliteTemplates() {
        if (!this.hasGroupeTemplatesTarget) {
            return;
        }

        this.groupeTemplatesTarget.querySelectorAll('[data-bbcode-mode-param="complet"]').forEach((bouton) => {
            bouton.disabled = this.contenuTarget.value.trim() !== '';
        });
    }

    async actualiserApercu() {
        this.renduTarget.innerHTML = '<div class="text-secondary"><span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Actualisation...</div>';
        const donnees = new URLSearchParams({ contenu: this.contenuTarget.value, _token: this.jetonValue });

        try {
            const reponse = await fetch(this.apercuUrlValue, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8', 'X-Requested-With': 'XMLHttpRequest' },
                body: donnees,
            });
            if (!reponse.ok) throw new Error(`HTTP ${reponse.status}`);
            this.renduTarget.innerHTML = await reponse.text() || '<p class="text-secondary mb-0">Le contenu est vide.</p>';
        } catch (erreur) {
            this.renduTarget.innerHTML = '<div class="alert alert-danger mb-0">Impossible de charger l’aperçu.</div>';
        }
    }

    encadrer(event) {
        this.insererAutourSelection(event.params.ouvrante, event.params.fermante);
    }

    liste() {
        const selection = this.selection() || 'Premier élément\nDeuxième élément';
        const elements = selection.split(/\r?\n/).filter(Boolean).map((ligne) => `[elementliste]${ligne}[/elementliste]`).join('\n');
        this.remplacerSelection(`[liste]\n${elements}\n[/liste]`);
    }

    lien() {
        const url = window.prompt('Adresse du lien (https://...)');
        if (!url) return;
        const texte = this.selection() || 'Texte du lien';
        this.remplacerSelection(`[lien]${url}[/lien][texteLien]${texte}[/texteLien]`);
    }

    image() {
        const url = window.prompt('Adresse de l’image');
        if (url) this.remplacerSelection(`[image]${url}[/image]`);
    }

    video() {
        const url = window.prompt('Adresse de la vidéo YouTube');
        if (url) this.remplacerSelection(`[video]${url}[/video]`);
    }

    tableau() {
        const saisieLignes = window.prompt('Nombre de lignes', '2');
        if (saisieLignes === null) return;
        const saisieColonnes = window.prompt('Nombre de colonnes', '2');
        if (saisieColonnes === null) return;
        const lignes = Math.max(1, Math.min(20, Number.parseInt(saisieLignes, 10) || 1));
        const colonnes = Math.max(1, Math.min(10, Number.parseInt(saisieColonnes, 10) || 1));

        let contenu = '[Tableau]\n[TableauCorps]\n';
        for (let ligne = 0; ligne < lignes; ligne += 1) {
            contenu += '[TableauLigne]';
            for (let colonne = 0; colonne < colonnes; colonne += 1) contenu += '[TableauColonne]Texte[/TableauColonne]';
            contenu += '[/TableauLigne]\n';
        }
        this.remplacerSelection(`${contenu}[/TableauCorps]\n[/Tableau]`);
    }

    choisir(event) {
        const choix = event.currentTarget;
        if (!choix.value) return;
        const ouvrante = choix.dataset.bbcodeModeleOuvrant.replace('{valeur}', choix.value);
        const fermante = choix.dataset.bbcodeModeleFermant;
        this.insererAutourSelection(ouvrante, fermante);
        choix.value = '';
    }

    icone(event) {
        if (event.currentTarget.value) this.remplacerSelection(`[icone=${event.currentTarget.value}][/icone]`);
        event.currentTarget.value = '';
    }

    initialiserEmojis() {
        if (!this.hasMenuEmojiTarget || this.selecteurEmoji) return;

        this.selecteurEmoji = new Picker({
            dataSource: 'https://cdn.jsdelivr.net/npm/emoji-picker-element-data@^1/fr/emojibase/data.json',
            i18n: traductionEmojiFr,
            locale: 'fr',
        });
        this.selecteurEmoji.addEventListener('emoji-click', (event) => {
            this.remplacerSelection(event.detail.unicode);
            this.fermerEmojis();
        });
        this.menuEmojiTarget.append(this.selecteurEmoji);
        this.actualiserThemeEmoji();

        this.observateurTheme = new MutationObserver(() => this.actualiserThemeEmoji());
        this.observateurTheme.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['data-bs-theme'],
        });
    }

    basculerEmojis(event) {
        event.stopPropagation();
        const doitOuvrir = this.menuEmojiTarget.hidden;
        if (!doitOuvrir) {
            this.fermerEmojis();
            return;
        }

        this.initialiserEmojis();
        document.addEventListener('click', this.fermerEmojisAuClic);
        this.menuEmojiTarget.hidden = false;
        event.currentTarget.setAttribute('aria-expanded', 'true');
        window.requestAnimationFrame(() => this.selecteurEmoji.shadowRoot?.querySelector('input')?.focus());
    }

    fermerEmojisAuClic(event) {
        if (!this.menuEmojiTarget.contains(event.target)) this.fermerEmojis();
    }

    fermerEmojis() {
        if (!this.hasMenuEmojiTarget) return;
        this.menuEmojiTarget.hidden = true;
        document.removeEventListener('click', this.fermerEmojisAuClic);
        this.element.querySelector('[data-action~="bbcode#basculerEmojis"]')?.setAttribute('aria-expanded', 'false');
    }

    actualiserThemeEmoji() {
        this.selecteurEmoji?.classList.toggle('dark', document.documentElement.dataset.bsTheme === 'dark');
        this.selecteurEmoji?.classList.toggle('light', document.documentElement.dataset.bsTheme !== 'dark');
    }

    insererAutourSelection(ouvrante, fermante) {
        this.remplacerSelection(`${ouvrante}${this.selection() || 'texte'}${fermante}`);
    }

    selection() {
        return this.contenuTarget.value.substring(this.contenuTarget.selectionStart, this.contenuTarget.selectionEnd);
    }

    remplacerSelection(texte) {
        const champ = this.contenuTarget;
        const debut = champ.selectionStart;
        champ.setRangeText(texte, debut, champ.selectionEnd, 'end');
        champ.focus();
        champ.dispatchEvent(new Event('input', { bubbles: true }));
    }
}
