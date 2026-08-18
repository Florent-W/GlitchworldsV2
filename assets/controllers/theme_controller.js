import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['icone', 'option', 'applyButton', 'cancelButton', 'status'];

    connect() {
        this.media = window.matchMedia('(prefers-color-scheme: dark)');
        this.root = document.documentElement;
        this.themeSauvegarde = this.obtenirThemeSauvegarde();
        this.themeApercu = null;
        this.pendingPersist = null;

        this.changementSysteme = event => {
            if (this.themeActive() === 'system') {
                this.appliquerTheme('system', { memoriser: false, notifier: false });
            }
        };
        this.media.addEventListener('change', this.changementSysteme);

        this.changementExterne = event => {
            const detail = event.detail || {};
            if (typeof detail.theme === 'string' && (detail.persisted || detail.rollback)) {
                this.themeSauvegarde = this.normaliserTheme(detail.theme);
                this.themeApercu = null;
            }
            this.actualiser();
        };
        window.addEventListener('glitchworlds:theme-change', this.changementExterne);

        this.onPreferencesSaveFailed = event => {
            const payload = event.detail?.payload;
            if (!payload || typeof payload.theme !== 'string') {
                return;
            }
            if (!this.pendingPersist || this.pendingPersist.theme !== payload.theme) {
                return;
            }

            const fallback = this.pendingPersist.previousTheme;
            this.pendingPersist = null;
            this.themeSauvegarde = fallback;
            this.themeApercu = null;
            this.ecrireThemeSauvegarde(fallback);
            this.appliquerTheme(fallback, { memoriser: false, notifier: true, rollback: true });
            this.mettreAJourStatut('Échec de sauvegarde: thème précédent restauré.');
            this.actualiser();
        };

        this.onPreferencesSaveSuccess = event => {
            const payload = event.detail?.payload;
            if (!payload || typeof payload.theme !== 'string') {
                return;
            }
            if (this.pendingPersist && this.pendingPersist.theme === payload.theme) {
                this.pendingPersist = null;
            }
        };

        window.addEventListener('glitchworlds:preferences-save-failed', this.onPreferencesSaveFailed);
        window.addEventListener('glitchworlds:preferences-save-success', this.onPreferencesSaveSuccess);

        this.actualiser();
    }

    disconnect() {
        this.media?.removeEventListener('change', this.changementSysteme);
        window.removeEventListener('glitchworlds:theme-change', this.changementExterne);
        window.removeEventListener('glitchworlds:preferences-save-failed', this.onPreferencesSaveFailed);
        window.removeEventListener('glitchworlds:preferences-save-success', this.onPreferencesSaveSuccess);
    }

    basculer() {
        const actif = this.themeActive();
        const theme = actif === 'dark' ? 'light' : 'dark';
        this.themeApercu = theme;
        this.appliquerTheme(theme, { memoriser: false, notifier: false });
        this.actualiser();
    }

    selectionner(event) {
        const choix = this.normaliserTheme(event.params.theme);
        this.themeApercu = choix;
        this.appliquerTheme(choix, { memoriser: false, notifier: false });
        this.mettreAJourStatut(`Aperçu: ${this.libelleTheme(choix)}.`);
        this.actualiser();
    }

    appliquerSelection() {
        const choix = this.themeApercu ?? this.themeSauvegarde;
        const precedent = this.themeSauvegarde;
        this.themeSauvegarde = choix;
        this.themeApercu = null;
        this.pendingPersist = { theme: choix, previousTheme: precedent };
        this.ecrireThemeSauvegarde(choix);
        this.appliquerTheme(choix, { memoriser: false, notifier: true, persisted: true, previousTheme: precedent });
        this.mettreAJourStatut(`Thème appliqué: ${this.libelleTheme(choix)}.`);
        this.actualiser();
    }

    annulerApercu() {
        this.themeApercu = null;
        this.appliquerTheme(this.themeSauvegarde, { memoriser: false, notifier: false });
        this.mettreAJourStatut('Aperçu annulé.');
        this.actualiser();
    }

    restaurerDefaut() {
        const themeParDefaut = 'system';
        const precedent = this.themeSauvegarde;
        this.themeSauvegarde = themeParDefaut;
        this.themeApercu = null;
        this.pendingPersist = { theme: themeParDefaut, previousTheme: precedent };
        this.ecrireThemeSauvegarde(themeParDefaut);
        this.appliquerTheme(themeParDefaut, { memoriser: false, notifier: true, persisted: true, previousTheme: precedent });
        this.mettreAJourStatut('Thème par défaut restauré.');
        this.actualiser();
    }

    appliquerTheme(theme, { memoriser = false, notifier = false, persisted = false, previousTheme = null, rollback = false } = {}) {
        const normalise = this.normaliserTheme(theme);
        const resolu = this.resoudreTheme(normalise);
        const sombre = resolu === 'dark' || resolu === 'ps3';

        this.root.dataset.theme = normalise;
        this.root.dataset.themeResolved = resolu;
        this.root.dataset.gwTheme = normalise;
        this.root.dataset.bsTheme = sombre ? 'dark' : 'light';

        if (memoriser) {
            this.ecrireThemeSauvegarde(normalise);
        }

        if (this.hasIconeTarget) {
            this.element.setAttribute('aria-pressed', String(sombre));
            this.element.setAttribute('aria-label', sombre ? 'Activer le thème clair' : 'Activer le thème sombre');
            this.element.title = sombre ? 'Activer le thème clair' : 'Activer le thème sombre';
            this.iconeTarget.className = `bi ${sombre ? 'bi-sun-fill' : 'bi-moon-stars-fill'}`;
        }

        if (notifier) {
            window.dispatchEvent(new CustomEvent('glitchworlds:theme-change', {
                detail: {
                    theme: normalise,
                    resolvedTheme: resolu,
                    persisted,
                    previousTheme,
                    rollback,
                },
            }));
        }
    }

    actualiser() {
        const themeAffiche = this.themeActive();
        this.appliquerTheme(themeAffiche, { memoriser: false, notifier: false });

        this.optionTargets.forEach(option => {
            const actif = option.dataset.themeValue === themeAffiche;
            option.classList.toggle('is-active', actif);
            option.setAttribute('aria-pressed', String(actif));
        });

        if (this.hasApplyButtonTarget) {
            this.applyButtonTarget.disabled = this.themeApercu === null;
        }
        if (this.hasCancelButtonTarget) {
            this.cancelButtonTarget.disabled = this.themeApercu === null;
        }
    }

    themeActive() {
        return this.themeApercu ?? this.themeSauvegarde;
    }

    obtenirThemeSauvegarde() {
        const themeStocke = localStorage.getItem('glitchworlds-theme');
        const themeParDefaut = this.root.dataset.defaultTheme || 'system';
        const depuisRacine = this.root.dataset.theme || 'system';
        const source = themeStocke || themeParDefaut || depuisRacine;

        return this.normaliserTheme(source);
    }

    ecrireThemeSauvegarde(theme) {
        localStorage.setItem('glitchworlds-theme', this.normaliserTheme(theme));
    }

    normaliserTheme(theme) {
        const correspondances = {
            ds: 'wii',
            gamecube: 'wii',
            dreamcast: 'ps3',
            wave: 'ps3',
            neon: 'ps3',
        };
        const valeur = correspondances[theme] ?? theme;
        const autorises = ['system', 'light', 'dark', 'glitchworlds', 'wii', 'ps3', 'legacy'];

        return autorises.includes(valeur) ? valeur : 'system';
    }

    resoudreTheme(theme) {
        if (theme === 'system') {
            return this.media.matches ? 'dark' : 'light';
        }

        return this.normaliserTheme(theme);
    }

    libelleTheme(theme) {
        const libelles = {
            system: 'Système',
            light: 'Clair',
            dark: 'Sombre',
            glitchworlds: 'GlitchWorlds',
            wii: 'Wii',
            ps3: 'PS3',
            legacy: 'Retro',
        };

        return libelles[this.normaliserTheme(theme)] ?? 'Système';
    }

    mettreAJourStatut(message) {
        if (!this.hasStatusTarget) {
            return;
        }
        this.statusTarget.textContent = message;
    }
}
