import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['barres', 'courbe', 'bouton'];
    static values = { labels: String };

    connect() {
        this.vue = localStorage.getItem('gw-graphiques-administration') === 'courbe' ? 'courbe' : 'barres';
        this.afficher(this.vue);
        this.synchronisationLiee = (event) => this.afficher(event.detail.vue);
        window.addEventListener('gw:graphiques-administration', this.synchronisationLiee);
        this.observateur = new ResizeObserver(() => {
            if (this.vue === 'courbe') this.dessiner();
        });
        this.observateur.observe(this.element);
        this.tooltip = document.createElement('div');
        this.tooltip.className = 'gw-chart-line__tooltip';
        this.tooltip.hidden = true;
        this.courbeTarget.append(this.tooltip);
        this.canvas = this.courbeTarget.querySelector('canvas');
        this.survolLie = (event) => this.survoler(event);
        this.sortieLie = () => {
            this.tooltip.hidden = true;
            this.indexSurvole = null;
            this.dessiner();
        };
        this.canvas?.addEventListener('mousemove', this.survolLie);
        this.canvas?.addEventListener('mouseleave', this.sortieLie);
    }

    disconnect() {
        this.observateur?.disconnect();
        this.canvas?.removeEventListener('mousemove', this.survolLie);
        this.canvas?.removeEventListener('mouseleave', this.sortieLie);
        window.removeEventListener('gw:graphiques-administration', this.synchronisationLiee);
    }

    changer(event) {
        this.afficher(event.currentTarget.dataset.vue);
        window.dispatchEvent(new CustomEvent('gw:graphiques-administration', { detail: { vue: this.vue } }));
    }

    afficher(vue) {
        this.vue = vue === 'courbe' ? 'courbe' : 'barres';
        this.barresTarget.hidden = this.vue !== 'barres';
        this.courbeTarget.hidden = this.vue !== 'courbe';
        this.boutonTargets.forEach((bouton) => {
            const actif = bouton.dataset.vue === this.vue;
            bouton.classList.toggle('active', actif);
            bouton.setAttribute('aria-pressed', String(actif));
        });
        localStorage.setItem('gw-graphiques-administration', this.vue);
        if (this.vue === 'courbe') requestAnimationFrame(() => this.dessiner());
    }

    dessiner() {
        const canvas = this.courbeTarget.querySelector('canvas');
        const jours = Array.from(this.barresTarget.querySelectorAll('.gw-admin-chart__day'));
        if (!canvas || jours.length === 0) return;

        const series = [0, 1].map((index) => jours.map((jour) => Number(jour.querySelectorAll('[data-graph-value]')[index]?.dataset.graphValue ?? 0)));
        this.series = series;
        this.libellesPoints = jours.map((jour) => jour.querySelector('small')?.textContent.trim() ?? '');
        const premiereJournee = jours[0].querySelectorAll('[data-graph-value]');
        const couleurs = [0, 1].map((index) => getComputedStyle(premiereJournee[index]).backgroundColor);
        const ratio = window.devicePixelRatio || 1;
        const largeur = Math.max(canvas.parentElement.clientWidth, 320);
        const hauteur = 260;
        canvas.width = largeur * ratio;
        canvas.height = hauteur * ratio;
        canvas.style.width = `${largeur}px`;
        canvas.style.height = `${hauteur}px`;
        const contexte = canvas.getContext('2d');
        contexte.scale(ratio, ratio);
        contexte.clearRect(0, 0, largeur, hauteur);
        const marge = { haut: 14, droite: 12, bas: 30, gauche: 12 };

        series.forEach((valeurs, serie) => {
            const maximum = Math.max(1, ...valeurs);
            contexte.beginPath();
            valeurs.forEach((valeur, index) => {
                const x = marge.gauche + (index / Math.max(1, valeurs.length - 1)) * (largeur - marge.gauche - marge.droite);
                const y = marge.haut + (1 - valeur / maximum) * (hauteur - marge.haut - marge.bas);
                index === 0 ? contexte.moveTo(x, y) : contexte.lineTo(x, y);
            });
            contexte.strokeStyle = couleurs[serie];
            contexte.lineWidth = 2.5;
            contexte.lineJoin = 'round';
            contexte.lineCap = 'round';
            contexte.stroke();
        });

        if (Number.isInteger(this.indexSurvole)) {
            const x = marge.gauche + (this.indexSurvole / Math.max(1, series[0].length - 1)) * (largeur - marge.gauche - marge.droite);
            contexte.beginPath();
            contexte.moveTo(x, marge.haut);
            contexte.lineTo(x, hauteur - marge.bas);
            contexte.strokeStyle = 'rgba(127, 127, 127, .38)';
            contexte.lineWidth = 1;
            contexte.stroke();

            series.forEach((valeurs, serie) => {
                const maximum = Math.max(1, ...valeurs);
                const y = marge.haut + (1 - valeurs[this.indexSurvole] / maximum) * (hauteur - marge.haut - marge.bas);
                contexte.beginPath();
                contexte.arc(x, y, 4.5, 0, Math.PI * 2);
                contexte.fillStyle = couleurs[serie];
                contexte.fill();
                contexte.lineWidth = 2;
                contexte.strokeStyle = '#fff';
                contexte.stroke();
            });
        }
    }

    survoler(event) {
        if (!this.series?.[0]?.length) return;
        const rectangle = this.canvas.getBoundingClientRect();
        const margeGauche = 12;
        const largeurUtile = rectangle.width - 24;
        const index = Math.max(0, Math.min(this.series[0].length - 1, Math.round(((event.clientX - rectangle.left - margeGauche) / largeurUtile) * (this.series[0].length - 1))));
        if (this.indexSurvole !== index) {
            this.indexSurvole = index;
            this.dessiner();
        }
        const noms = this.labelsValue.split('|');
        const format = new Intl.NumberFormat('fr-FR');
        this.tooltip.textContent = `${this.libellesPoints[index]}\n${noms[0]} : ${format.format(this.series[0][index])}\n${noms[1]} : ${format.format(this.series[1][index])}`;
        this.tooltip.hidden = false;
        const gauche = Math.max(8, Math.min(rectangle.width - this.tooltip.offsetWidth - 8, event.clientX - rectangle.left + 12));
        const haut = Math.max(8, event.clientY - rectangle.top - this.tooltip.offsetHeight - 12);
        this.tooltip.style.transform = `translate(${gauche}px, ${haut}px)`;
    }
}
