import { Controller } from '@hotwired/stimulus';
import Sortable from 'sortablejs';

export default class extends Controller {
    static values = { url: String, token: String };

    connect() {
        this.sortable = Sortable.create(this.element, {
            animation: 160,
            draggable: '.gw-custom-list__game',
            handle: '.gw-custom-list__drag-handle',
            ghostClass: 'gw-custom-list__game--ghost',
            chosenClass: 'gw-custom-list__game--chosen',
            onStart: () => { this.ordreInitial = this.ordre(); },
            onEnd: () => this.sauvegarder(),
        });
    }

    disconnect() {
        this.sortable?.destroy();
    }

    async sauvegarder() {
        this.actualiserRangs();
        try {
            const reponse = await fetch(this.urlValue, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ _token: this.tokenValue, ordre: this.ordre() }),
            });
            if (!reponse.ok) throw new Error('Échec de l’enregistrement');
        } catch {
            this.sortable.sort(this.ordreInitial ?? []);
            this.actualiserRangs();
        }
    }

    deplacerAuClavier(event) {
        if (!['ArrowUp', 'ArrowDown'].includes(event.key)) return;
        event.preventDefault();
        const jeu = event.currentTarget.closest('.gw-custom-list__game');
        const jeux = Array.from(this.element.querySelectorAll(':scope > .gw-custom-list__game'));
        const index = jeux.indexOf(jeu);
        const destination = index + (event.key === 'ArrowUp' ? -1 : 1);
        if (index < 0 || destination < 0 || destination >= jeux.length) return;

        this.ordreInitial = this.ordre();
        if (destination < index) this.element.insertBefore(jeu, jeux[destination]);
        else this.element.insertBefore(jeux[destination], jeu);
        this.sauvegarder();
        event.currentTarget.focus();
    }

    ordre() {
        return Array.from(this.element.querySelectorAll(':scope > .gw-custom-list__game')).map((jeu) => jeu.dataset.jeuId);
    }

    actualiserRangs() {
        this.element.querySelectorAll(':scope > .gw-custom-list__game').forEach((jeu, index) => {
            const rang = jeu.querySelector('.gw-custom-list__rank-value');
            if (rang) rang.textContent = String(index + 1);
        });
    }
}
