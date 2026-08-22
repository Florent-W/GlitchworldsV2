import { Controller } from '@hotwired/stimulus';
import Sortable from 'sortablejs';

const CLE_ORDRE = 'glitchworlds-ordre-accueil';
const SELECTEUR_SECTION = '.gw-home-sortable-section';

export default class extends Controller {
    connect() {
        this.restaurer();
        this.sortable = Sortable.create(this.element, {
            animation: 160,
            draggable: SELECTEUR_SECTION,
            handle: '.gw-home-drag-handle',
            ghostClass: 'gw-home-section--ghost',
            chosenClass: 'gw-home-section--chosen',
            dragClass: 'gw-home-section--dragging',
            onEnd: () => {
                this.sauvegarder();
                this.actualiserBoutons();
            },
        });
        this.actualiserBoutons();
    }

    disconnect() {
        this.sortable?.destroy();
    }

    basculerMode(event) {
        const actif = this.element.classList.toggle('gw-home-order--organizing');
        const bouton = event.currentTarget;
        const icone = bouton.querySelector('i');
        const libelle = bouton.querySelector('span');

        bouton.setAttribute('aria-pressed', actif ? 'true' : 'false');
        bouton.classList.toggle('btn-primary', actif);
        bouton.classList.toggle('btn-outline-secondary', !actif);
        if (icone) icone.className = actif ? 'bi bi-check-lg' : 'bi bi-arrows-move';
        if (libelle) libelle.textContent = actif ? ' Terminer' : ' Réorganiser';
    }

    deplacerAuClavier(event) {
        if (!['ArrowUp', 'ArrowDown'].includes(event.key)) {
            return;
        }

        event.preventDefault();
        const section = event.currentTarget.closest(SELECTEUR_SECTION);
        const sections = this.sections();
        const index = sections.indexOf(section);
        const nouvelIndex = event.key === 'ArrowUp' ? index - 1 : index + 1;

        if (index < 0 || nouvelIndex < 0 || nouvelIndex >= sections.length) {
            return;
        }

        if (event.key === 'ArrowUp') {
            this.element.insertBefore(section, sections[nouvelIndex]);
        } else {
            this.element.insertBefore(sections[nouvelIndex], section);
        }

        this.sauvegarder();
        this.actualiserBoutons();
        event.currentTarget.focus();
    }

    monter(event) {
        this.deplacer(event.currentTarget.closest(SELECTEUR_SECTION), -1);
    }

    descendre(event) {
        this.deplacer(event.currentTarget.closest(SELECTEUR_SECTION), 1);
    }

    deplacer(section, direction) {
        const sections = this.sections();
        const index = sections.indexOf(section);
        const nouvelIndex = index + direction;

        if (index < 0 || nouvelIndex < 0 || nouvelIndex >= sections.length) {
            return;
        }

        if (direction < 0) {
            this.element.insertBefore(section, sections[nouvelIndex]);
        } else {
            this.element.insertBefore(sections[nouvelIndex], section);
        }

        this.sauvegarder();
        this.actualiserBoutons();
    }

    restaurer() {
        let ordre = [];
        try {
            ordre = JSON.parse(localStorage.getItem(CLE_ORDRE) ?? '[]');
        } catch {
            localStorage.removeItem(CLE_ORDRE);
        }

        if (!Array.isArray(ordre)) {
            return;
        }

        const sections = new Map(this.sections().map((section) => [section.dataset.homeSection, section]));
        const ordreComplet = [...ordre, ...sections.keys()].filter((cle, index, liste) => sections.has(cle) && liste.indexOf(cle) === index);
        ordreComplet.forEach((cle) => this.element.append(sections.get(cle)));
    }

    sauvegarder() {
        localStorage.setItem(CLE_ORDRE, JSON.stringify(this.sections().map((section) => section.dataset.homeSection)));
    }

    actualiserBoutons() {
        const sections = this.sections();
        sections.forEach((section, index) => {
            const boutonHaut = section.querySelector('.gw-home-order-button--up');
            const boutonBas = section.querySelector('.gw-home-order-button--down');
            if (boutonHaut) boutonHaut.disabled = index === 0;
            if (boutonBas) boutonBas.disabled = index === sections.length - 1;
        });
    }

    sections() {
        return Array.from(this.element.querySelectorAll(`:scope > ${SELECTEUR_SECTION}`));
    }
}
