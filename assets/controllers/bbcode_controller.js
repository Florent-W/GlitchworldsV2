import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['contenu', 'apercu', 'rendu', 'boutonApercu'];
    static values = { apercuUrl: String, jeton: String };

    connect() {
        this.apercuActif = false;
        this.delaiApercu = null;
    }

    disconnect() {
        window.clearTimeout(this.delaiApercu);
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
        if (!this.apercuActif) return;
        window.clearTimeout(this.delaiApercu);
        this.delaiApercu = window.setTimeout(() => this.actualiserApercu(), 350);
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
