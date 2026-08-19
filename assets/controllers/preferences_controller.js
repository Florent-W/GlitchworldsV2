import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['mouvement', 'contraste', 'taille', 'retourHaut', 'notificationsEmail', 'notificationsMessages', 'notificationsCommunaute', 'profilPrive'];

    connect() {
        this.syncUrl = this.element.dataset.preferencesSyncUrl || null;
        this.appliquerValeursDepuisServeur();
        this.ajouterEcouteurs();
    }

    disconnect() {
        window.removeEventListener('glitchworlds:theme-change', this.onThemeChange);
    }

    ajouterEcouteurs() {
        this.onThemeChange = event => {
            const detail = event.detail || {};
            // « saved » signale que le contrôleur de thème a déjà fait l'appel réseau lui-même.
            if (!detail.persisted || detail.saved) {
                return;
            }

            const theme = typeof detail.theme === 'string'
                ? detail.theme
                : (localStorage.getItem('glitchworlds-theme') || 'glitchworlds:system');

            this.sauvegarder({ theme });
        };
        window.addEventListener('glitchworlds:theme-change', this.onThemeChange);
    }

    appliquerValeursDepuisServeur() {
        const valeur = this.element.dataset.preferencesReduction === 'true';
        this.mouvementTarget.checked = localStorage.getItem('glitchworlds-reduced-motion') !== null
            ? localStorage.getItem('glitchworlds-reduced-motion') === 'true'
            : valeur;
        this.appliquerMouvement(this.mouvementTarget.checked, false);

        const contraste = this.element.dataset.preferencesContraste === 'true';
        this.contrasteTarget.checked = contraste;
        this.appliquerContraste(contraste, false);

        const taille = this.element.dataset.preferencesTaille || 'normal';
        this.tailleTarget.value = taille;
        this.appliquerTaille(taille, false);

        // Préférence locale uniquement, comme les sons d'interface.
        this.retourHautTarget.checked = localStorage.getItem('glitchworlds-retour-haut') !== '0';
        this.appliquerRetourHaut(this.retourHautTarget.checked, false);

        const notifications = this.element.dataset.preferencesNotifications ? JSON.parse(this.element.dataset.preferencesNotifications) : { email: true, messages: true, communaute: true };
        this.notificationsEmailTarget.checked = notifications.email ?? true;
        this.notificationsMessagesTarget.checked = notifications.messages ?? true;
        this.notificationsCommunauteTarget.checked = notifications.communaute ?? true;
        this.appliquerNotifications(notifications, false);

        const profilPrive = this.element.dataset.preferencesProfilPrive === 'true';
        this.profilPriveTarget.checked = profilPrive;
        this.appliquerProfilPrive(profilPrive, false);
    }

    changerMouvement() {
        this.appliquerMouvement(this.mouvementTarget.checked, true);
        this.sauvegarder({ reductionAnimations: this.mouvementTarget.checked });
    }

    changerContraste() {
        this.appliquerContraste(this.contrasteTarget.checked, true);
        this.sauvegarder({ contrasteRenforce: this.contrasteTarget.checked });
    }

    changerTaille(event) {
        const valeur = event.target.value;
        this.appliquerTaille(valeur, true);
        this.sauvegarder({ tailleTexte: valeur });
    }

    changerRetourHaut() {
        this.appliquerRetourHaut(this.retourHautTarget.checked, true);
    }

    changerNotification() {
        const notifications = {
            email: this.notificationsEmailTarget.checked,
            messages: this.notificationsMessagesTarget.checked,
            communaute: this.notificationsCommunauteTarget.checked,
        };
        this.appliquerNotifications(notifications, true);
        this.sauvegarder({ notifications });
    }

    changerProfilPrive() {
        this.appliquerProfilPrive(this.profilPriveTarget.checked, true);
        this.sauvegarder({ profilPrive: this.profilPriveTarget.checked });
    }

    appliquerMouvement(reduit, memoriser) {
        document.documentElement.dataset.reducedMotion = String(reduit);
        if (memoriser) localStorage.setItem('glitchworlds-reduced-motion', String(reduit));
    }

    appliquerContraste(active, memoriser) {
        document.documentElement.dataset.contrastRenforce = String(active);
        if (memoriser) localStorage.setItem('glitchworlds-contrast', String(active));
    }

    appliquerTaille(valeur, memoriser) {
        document.documentElement.dataset.gwTextSize = valeur;
        if (memoriser) localStorage.setItem('glitchworlds-text-size', valeur);
    }

    appliquerRetourHaut(actif, memoriser) {
        document.documentElement.dataset.gwRetourHaut = actif ? '1' : '0';
        if (memoriser) {
            localStorage.setItem('glitchworlds-retour-haut', actif ? '1' : '0');
        }
        window.dispatchEvent(new CustomEvent('glitchworlds:retour-haut-change', {
            detail: { actif },
        }));
    }

    appliquerNotifications(notifications, memoriser) {
        document.documentElement.dataset.gwNotifications = JSON.stringify(notifications);
        if (memoriser) localStorage.setItem('glitchworlds-notifications', JSON.stringify(notifications));
    }

    appliquerProfilPrive(active, memoriser) {
        document.documentElement.dataset.gwProfilPrive = String(active);
        if (memoriser) localStorage.setItem('glitchworlds-profil-prive', String(active));
    }

    sauvegarder(payload) {
        if (!this.syncUrl) {
            return;
        }

        fetch(this.syncUrl, {
            method: 'POST',
            credentials: 'same-origin',
            keepalive: true,
            headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
            body: JSON.stringify(payload),
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Impossible de sauvegarder les préférences.');
                }

                window.dispatchEvent(new CustomEvent('glitchworlds:preferences-save-success', {
                    detail: { payload },
                }));
            })
            .catch(() => {
                window.dispatchEvent(new CustomEvent('glitchworlds:preferences-save-failed', {
                    detail: { payload },
                }));
            });
    }
}
