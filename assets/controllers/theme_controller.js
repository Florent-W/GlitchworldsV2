import { Controller } from '@hotwired/stimulus';

const ALIAS_PALETTES = {
    gamecube: 'wii',
    dreamcast: 'ps3',
    wave: 'ps3',
    neon: 'ps3',
};
const PALETTES = ['glitchworlds', 'wii', 'ps3', 'legacy', 'ds', 'dsi', '3ds'];
const MODES = ['system', 'light', 'dark'];
const THEME_STYLES = { glitchworlds: 'glitchworlds', legacy: 'legacy', wii: 'theme1', ps3: 'theme2', ds: 'theme3', dsi: 'theme4', '3ds': 'theme5' };

/**
 * Le thème a deux axes indépendants : une palette (ambiance) et un mode clair/sombre.
 * La valeur mémorisée les combine sous la forme « palette:mode ».
 */
export default class extends Controller {
    static targets = ['icone', 'paletteOption', 'modeOption', 'applyButton', 'cancelButton', 'status'];

    connect() {
        this.media = window.matchMedia('(prefers-color-scheme: dark)');
        this.root = document.documentElement;
        this.syncUrl = this.element.dataset.themeSyncUrl || null;
        this.enregistre = this.lireThemeSauvegarde();
        this.apercu = null;
        this.pendingPersist = null;

        this.changementSysteme = () => {
            if (this.themeActif().mode === 'system') {
                this.appliquerTheme(this.themeActif());
            }
        };
        this.media.addEventListener('change', this.changementSysteme);

        this.changementExterne = event => {
            const detail = event.detail || {};
            if (typeof detail.theme === 'string' && (detail.persisted || detail.rollback)) {
                this.enregistre = this.normaliser(detail.theme);
                this.apercu = null;
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

            const repli = this.pendingPersist.previousTheme;
            this.pendingPersist = null;
            this.enregistre = this.normaliser(repli);
            this.apercu = null;
            this.ecrireThemeSauvegarde(this.enregistre);
            this.appliquerTheme(this.enregistre, { notifier: true, rollback: true });
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

        this.positionnerNavigation(this.root.dataset.theme);
        this.actualiser();
    }

    disconnect() {
        this.media?.removeEventListener('change', this.changementSysteme);
        window.removeEventListener('glitchworlds:theme-change', this.changementExterne);
        window.removeEventListener('glitchworlds:preferences-save-failed', this.onPreferencesSaveFailed);
        window.removeEventListener('glitchworlds:preferences-save-success', this.onPreferencesSaveSuccess);
    }

    /** Bouton lune/soleil : inverse le mode sans jamais toucher à la palette. */
    basculer() {
        const actif = this.themeActif();
        const mode = this.resoudreMode(actif.mode) === 'dark' ? 'light' : 'dark';
        this.enregistrer({ palette: actif.palette, mode });
    }

    choisirMode(event) {
        const actif = this.themeActif();
        this.previsualiser({ palette: actif.palette, mode: event.params.mode });
    }

    choisirPalette(event) {
        const actif = this.themeActif();
        this.previsualiser({ palette: event.params.palette, mode: actif.mode });
    }

    appliquerSelection() {
        this.enregistrer(this.themeActif());
        this.mettreAJourStatut(`Thème appliqué: ${this.libelle(this.enregistre)}.`);
    }

    annulerApercu() {
        this.apercu = null;
        this.appliquerTheme(this.enregistre);
        this.mettreAJourStatut('Aperçu annulé.');
        this.actualiser();
    }

    restaurerDefaut() {
        this.enregistrer({ palette: 'glitchworlds', mode: 'system' });
        this.mettreAJourStatut('Thème par défaut restauré.');
    }

    previsualiser(theme) {
        this.apercu = this.normaliser(theme);
        this.appliquerTheme(this.apercu);
        this.mettreAJourStatut(`Aperçu: ${this.libelle(this.apercu)}.`);
        this.actualiser();
    }

    enregistrer(theme) {
        const choix = this.normaliser(theme);
        const precedent = this.serialiser(this.enregistre);
        this.enregistre = choix;
        this.apercu = null;
        this.pendingPersist = { theme: this.serialiser(choix), previousTheme: precedent };
        this.ecrireThemeSauvegarde(choix);
        this.appliquerTheme(choix, { notifier: true, persisted: true, previousTheme: precedent });
        this.actualiser();
    }

    appliquerTheme(theme, { notifier = false, persisted = false, previousTheme = null, rollback = false } = {}) {
        const { palette, mode } = this.normaliser(theme);
        const modeResolu = this.resoudreMode(mode);
        const sombre = modeResolu === 'dark';

        this.root.dataset.theme = palette;
        this.root.dataset.themeStyle = THEME_STYLES[palette] ?? 'glitchworlds';
        this.root.dataset.gwTheme = palette;
        this.root.dataset.gwMode = mode;
        this.root.dataset.themeResolved = modeResolu;
        this.root.dataset.bsTheme = modeResolu;
        this.positionnerNavigation(palette);

        if (this.hasIconeTarget) {
            this.element.setAttribute('aria-pressed', String(sombre));
            this.element.setAttribute('aria-label', sombre ? 'Activer le thème clair' : 'Activer le thème sombre');
            this.element.title = sombre ? 'Activer le thème clair' : 'Activer le thème sombre';
            this.iconeTarget.className = `bi ${sombre ? 'bi-sun-fill' : 'bi-moon-stars-fill'}`;
        }

        if (!notifier) {
            return;
        }

        // Hors page Paramètres, aucun contrôleur « preferences » n'écoute : on enregistre soi-même.
        const enregistreSoiMeme = persisted && this.syncUrl !== null;
        if (enregistreSoiMeme) {
            this.envoyerAuServeur(this.serialiser({ palette, mode }));
        }

        window.dispatchEvent(new CustomEvent('glitchworlds:theme-change', {
            detail: {
                theme: this.serialiser({ palette, mode }),
                palette,
                mode,
                resolvedMode: modeResolu,
                persisted,
                saved: enregistreSoiMeme,
                previousTheme,
                rollback,
            },
        }));
    }

    positionnerNavigation(palette) {
        const navigation = document.querySelector('.gw-rail');
        const entete = document.querySelector('.gw-topbar');
        const disposition = document.querySelector('.gw-layout');
        const recherche = entete?.querySelector('.gw-search');

        if (!navigation || !entete || !disposition) {
            return;
        }

        if (palette === 'legacy') {
            if (navigation.parentElement !== entete) {
                entete.insertBefore(navigation, recherche || entete.lastElementChild);
            }
        } else if (navigation.parentElement !== disposition) {
            disposition.prepend(navigation);
        }
    }

    envoyerAuServeur(theme) {
        fetch(this.syncUrl, {
            method: 'POST',
            credentials: 'same-origin',
            keepalive: true,
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ theme }),
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Impossible de sauvegarder le thème.');
                }

                window.dispatchEvent(new CustomEvent('glitchworlds:preferences-save-success', {
                    detail: { payload: { theme } },
                }));
            })
            .catch(() => {
                window.dispatchEvent(new CustomEvent('glitchworlds:preferences-save-failed', {
                    detail: { payload: { theme } },
                }));
            });
    }

    actualiser() {
        const { palette, mode } = this.themeActif();
        this.appliquerTheme({ palette, mode });

        if (this.hasPaletteOptionTarget) {
            this.paletteOptionTargets.forEach(option => this.marquer(option, option.dataset.themeValue === palette));
        }
        if (this.hasModeOptionTarget) {
            this.modeOptionTargets.forEach(option => this.marquer(option, option.dataset.themeValue === mode));
        }

        if (this.hasApplyButtonTarget) {
            this.applyButtonTarget.disabled = this.apercu === null;
        }
        if (this.hasCancelButtonTarget) {
            this.cancelButtonTarget.disabled = this.apercu === null;
        }
    }

    marquer(option, actif) {
        option.classList.toggle('is-active', actif);
        option.setAttribute('aria-pressed', String(actif));
    }

    themeActif() {
        return this.apercu ?? this.enregistre;
    }

    lireThemeSauvegarde() {
        const stocke = localStorage.getItem('glitchworlds-theme');

        return this.normaliser(stocke || this.root.dataset.defaultTheme || 'glitchworlds:system');
    }

    ecrireThemeSauvegarde(theme) {
        localStorage.setItem('glitchworlds-theme', this.serialiser(theme));
    }

    serialiser(theme) {
        const { palette, mode } = this.normaliser(theme);

        return `${palette}:${mode}`;
    }

    /** Accepte « palette:mode », un objet, ou une ancienne valeur mono-axe. */
    normaliser(theme) {
        let palette;
        let mode;

        if (typeof theme === 'string') {
            const parties = theme.split(':');
            palette = parties[0];
            mode = parties.length > 1 ? parties[1] : null;
        } else {
            palette = theme?.palette;
            mode = theme?.mode ?? null;
        }

        palette = ALIAS_PALETTES[palette] ?? palette;

        if (mode === null || mode === undefined) {
            if (MODES.includes(palette)) {
                mode = palette;
                palette = 'glitchworlds';
            } else if (palette === 'ps3') {
                mode = 'dark';
            } else if (palette === 'wii' || palette === 'legacy' || palette === 'ds' || palette === 'dsi' || palette === '3ds') {
                mode = 'light';
            } else {
                mode = 'system';
            }
        }

        return {
            palette: PALETTES.includes(palette) ? palette : 'glitchworlds',
            mode: MODES.includes(mode) ? mode : 'system',
        };
    }

    resoudreMode(mode) {
        if (mode === 'system') {
            return this.media.matches ? 'dark' : 'light';
        }

        return MODES.includes(mode) ? mode : 'light';
    }

    libelle(theme) {
        const { palette, mode } = this.normaliser(theme);
        const palettes = {
            glitchworlds: 'Glitchworlds',
            wii: 'Thème 1',
            ps3: 'Thème 2',
            legacy: 'GlitchWorlds Legacy',
            ds: 'Thème 3',
            dsi: 'Thème 4',
            '3ds': 'Thème 5',
        };
        const modes = {
            system: 'système',
            light: 'clair',
            dark: 'sombre',
        };

        return `${palettes[palette]} en ${modes[mode]}`;
    }

    mettreAJourStatut(message) {
        if (!this.hasStatusTarget) {
            return;
        }
        this.statusTarget.textContent = message;
    }
}
