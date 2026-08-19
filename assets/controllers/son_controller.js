import { Controller } from '@hotwired/stimulus';

const CLE = 'glitchworlds-sons';

/**
 * Correspondance entre les évènements d'interface et les fichiers du pack audio,
 * déposés dans public/sons/. Tant qu'un fichier est absent, la synthèse Web Audio
 * assure le retour sonore : le site reste utilisable sans le pack.
 */
const FICHIERS = {
    'survol-jeu': 'hover-game',
    'survol-article': 'hover-game',
    'ouvrir-jeu': 'open-game',
    'ouvrir-article': 'open-news',
    selection: 'confirm',
    ok: 'confirm',
    retour: 'back',
    notification: 'notification',
    erreur: 'error',
    activer: 'sound-on',
};

const VOLUMES = {
    'survol-jeu': 0.35,
    'survol-article': 0.32,
};

/**
 * Sons d'interface : pack audio si disponible, synthèse Web Audio en repli.
 * Le bouton coupe ou réactive le son ; le choix est mémorisé en localStorage.
 */
export default class extends Controller {
    static targets = ['icone'];

    connect() {
        this.root = document.documentElement;
        this.actif = this.lirePreference();
        this.contexte = null;
        this.dernierJeu = 0;
        this.derniersSurvols = new WeakMap();
        this.baseUrl = this.element.dataset.sonBaseUrl || null;
        this.sources = new Map();
        this.indisponibles = new Set();

        this.surClic = event => this.traiterClic(event);
        this.surSurvol = event => this.traiterSurvol(event);
        this.surEvenement = event => {
            const type = event.detail?.type || 'clic';
            this.jouer(type);
        };
        this.surTheme = event => {
            if (event.detail?.persisted) {
                this.jouer('ok');
            }
        };

        document.addEventListener('click', this.surClic, true);
        document.addEventListener('pointerover', this.surSurvol, true);
        window.addEventListener('glitchworlds:son', this.surEvenement);
        window.addEventListener('glitchworlds:theme-change', this.surTheme);
        this.appliquerEtat();
    }

    disconnect() {
        document.removeEventListener('click', this.surClic, true);
        document.removeEventListener('pointerover', this.surSurvol, true);
        window.removeEventListener('glitchworlds:son', this.surEvenement);
        window.removeEventListener('glitchworlds:theme-change', this.surTheme);
        this.contexte?.close?.();
        this.contexte = null;
        this.sources.clear();
    }

    basculer() {
        this.actif = !this.actif;
        localStorage.setItem(CLE, this.actif ? '1' : '0');
        this.appliquerEtat();

        // Même geste utilisateur : on peut débloquer AudioContext et jouer la confirmation.
        if (this.actif) {
            this.jouer('activer');
        }
    }

    traiterClic(event) {
        if (!this.actif) {
            return;
        }

        const cible = event.target.closest(
            '.gw-rail__item, .gw-icon-btn, .gw-theme-card, .gw-game-card, .gw-similar-card, article.card, [data-son], [data-son-survol]'
        );
        if (!cible || cible.closest('[data-son-ignore]')) {
            return;
        }

        // Le bouton son gère lui-même son feedback dans basculer().
        if (cible === this.element || this.element.contains(cible)) {
            return;
        }

        if (cible.dataset.son) {
            this.jouer(cible.dataset.son);
            return;
        }

        if (cible.classList.contains('gw-theme-card')) {
            this.jouer('selection');
            return;
        }

        const carte = this.natureCarte(cible);
        this.jouer(carte ? `ouvrir-${carte}` : 'clic');
    }

    traiterSurvol(event) {
        if (!this.actif || event.pointerType === 'touch') {
            return;
        }

        const cible = event.target.closest(
            '.gw-game-card, .gw-similar-card, article.card, [data-son-survol]'
        );
        if (!cible || cible.closest('[data-son-ignore]')) {
            return;
        }

        // pointerover remonte aussi lorsque la souris passe d'un enfant de la
        // carte à un autre : un seul son doit être joué à l'entrée de la carte.
        if (event.relatedTarget instanceof Node && cible.contains(event.relatedTarget)) {
            return;
        }

        const maintenant = performance.now();
        const dernierSurvol = this.derniersSurvols.get(cible) || 0;
        if (maintenant - dernierSurvol < 900) {
            return;
        }
        this.derniersSurvols.set(cible, maintenant);

        const type = cible.dataset.sonSurvol || `survol-${this.natureCarte(cible) ?? 'article'}`;
        this.jouer(type);
    }

    natureCarte(element) {
        if (element.matches('.gw-game-card, .gw-similar-card')) {
            return 'jeu';
        }
        if (element.matches('article.card, [data-son-survol]')) {
            return 'article';
        }

        return null;
    }

    jouer(type = 'clic') {
        if (!this.actif) {
            return;
        }

        // Évite le spam sur les clics rapides (double déclenchement Turbo, etc.).
        const maintenant = performance.now();
        if (maintenant - this.dernierJeu < 80) {
            return;
        }
        this.dernierJeu = maintenant;

        if (this.jouerFichier(type)) {
            return;
        }

        this.jouerProfil(this.profil(type)).catch(() => {
            // Autoplay / contexte bloqué : silencieux, pas d'erreur visible.
        });
    }

    /** Joue le fichier du pack s'il est déjà chargé. Retourne false pour laisser la synthèse répondre. */
    jouerFichier(type) {
        const nom = FICHIERS[type];
        if (this.baseUrl === null || nom === undefined || this.indisponibles.has(nom)) {
            return false;
        }

        const source = this.obtenirSource(nom);
        // Fichier absent ou encore en cours de chargement : la synthèse assure
        // le retour immédiat, sans attendre le réseau.
        if (source.readyState < 2) {
            return false;
        }

        // Un clone par lecture : deux sons peuvent se superposer sans se couper.
        const lecture = source.cloneNode();
        lecture.volume = VOLUMES[type] ?? 0.6;
        lecture.play().catch(() => this.indisponibles.add(nom));

        return true;
    }

    obtenirSource(nom) {
        let source = this.sources.get(nom);
        if (source !== undefined) {
            return source;
        }

        source = new Audio(`${this.baseUrl}${nom}.ogg`);
        source.preload = 'auto';
        // Un 404 ou un format refusé bascule définitivement ce son sur la synthèse.
        source.addEventListener('error', () => this.indisponibles.add(nom));
        this.sources.set(nom, source);

        return source;
    }

    async jouerProfil({ frequence, duree, typeOscillo, gain, glissade = 0 }) {
        const contexte = await this.obtenirContexte();
        if (!contexte) {
            return;
        }

        const maintenant = contexte.currentTime;
        const oscillateur = contexte.createOscillator();
        const enveloppe = contexte.createGain();

        oscillateur.type = typeOscillo;
        oscillateur.frequency.setValueAtTime(frequence, maintenant);
        if (glissade !== 0) {
            oscillateur.frequency.exponentialRampToValueAtTime(
                Math.max(40, frequence + glissade),
                maintenant + duree
            );
        }

        // Enveloppe courte : attaque puis extinction, pour un « bip » d'UI.
        enveloppe.gain.setValueAtTime(0.0001, maintenant);
        enveloppe.gain.exponentialRampToValueAtTime(gain, maintenant + 0.012);
        enveloppe.gain.exponentialRampToValueAtTime(0.0001, maintenant + duree);

        oscillateur.connect(enveloppe);
        enveloppe.connect(contexte.destination);
        oscillateur.start(maintenant);
        oscillateur.stop(maintenant + duree + 0.02);
    }

    async obtenirContexte() {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) {
            return null;
        }

        if (!this.contexte || this.contexte.state === 'closed') {
            this.contexte = new AudioCtx();
        }

        if (this.contexte.state === 'suspended') {
            await this.contexte.resume();
        }

        return this.contexte;
    }

    profil(type) {
        switch (type) {
            case 'activer':
                return { frequence: 660, duree: 0.12, typeOscillo: 'triangle', gain: 0.045, glissade: 220 };
            case 'selection':
                return { frequence: 520, duree: 0.09, typeOscillo: 'sine', gain: 0.035, glissade: 80 };
            case 'ok':
                return { frequence: 740, duree: 0.1, typeOscillo: 'triangle', gain: 0.04, glissade: 160 };
            case 'retour':
                return { frequence: 480, duree: 0.1, typeOscillo: 'sine', gain: 0.032, glissade: -190 };
            case 'notification':
                return { frequence: 880, duree: 0.22, typeOscillo: 'triangle', gain: 0.038, glissade: 180 };
            case 'erreur':
                return { frequence: 200, duree: 0.16, typeOscillo: 'triangle', gain: 0.04, glissade: -60 };
            case 'survol-jeu':
                return { frequence: 310, duree: 0.065, typeOscillo: 'triangle', gain: 0.018, glissade: 70 };
            case 'survol-article':
                return { frequence: 460, duree: 0.06, typeOscillo: 'sine', gain: 0.016, glissade: 45 };
            case 'ouvrir-jeu':
                // Son montant chaleureux, proche du retour de sélection d'une interface Wii.
                return { frequence: 560, duree: 0.115, typeOscillo: 'triangle', gain: 0.042, glissade: 240 };
            case 'ouvrir-article':
                return { frequence: 700, duree: 0.1, typeOscillo: 'sine', gain: 0.036, glissade: 200 };
            case 'clic':
            default:
                return { frequence: 420, duree: 0.055, typeOscillo: 'sine', gain: 0.03, glissade: -120 };
        }
    }

    lirePreference() {
        const stocke = localStorage.getItem(CLE);
        if (stocke === null) {
            return true;
        }

        return stocke === '1' || stocke === 'true';
    }

    appliquerEtat() {
        this.root.dataset.gwSons = this.actif ? '1' : '0';
        this.element.setAttribute('aria-pressed', String(this.actif));
        this.element.setAttribute(
            'aria-label',
            this.actif ? 'Couper les sons d\'interface' : 'Activer les sons d\'interface'
        );
        this.element.title = this.actif ? 'Sons activés' : 'Sons coupés';

        if (this.hasIconeTarget) {
            this.iconeTarget.className = `bi ${this.actif ? 'bi-volume-up-fill' : 'bi-volume-mute-fill'}`;
        }
    }
}
