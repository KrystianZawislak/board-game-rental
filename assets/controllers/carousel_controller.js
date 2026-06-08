import { Controller } from '@hotwired/stimulus';

/**
 * Poziomy pasek kart (wiersz katalogu) ze strzałkami w lewo/prawo.
 * Strzałka chowa się, gdy nie ma już dokąd przewijać w danym kierunku.
 */
export default class extends Controller {
    static targets = ['track', 'prev', 'next'];

    connect() {
        this.refresh = () => this.updateArrows();
        this.trackTarget.addEventListener('scroll', this.refresh, { passive: true });
        window.addEventListener('resize', this.refresh);
        this.updateArrows();
        this.element.classList.add('is-ready'); // dopiero teraz pokazujemy aktywne strzałki
    }

    disconnect() {
        this.element.classList.remove('is-ready');
        this.trackTarget.removeEventListener('scroll', this.refresh);
        window.removeEventListener('resize', this.refresh);
    }

    prev() { this.step(-1); }
    next() { this.step(1); }

    step(direction) {
        const card = this.trackTarget.querySelector('.game-card');
        if (!card) return;

        // jedna karta + odstęp (gap) = dokładnie jeden krok
        const styles = getComputedStyle(this.trackTarget);
        const gap = parseFloat(styles.columnGap || styles.gap) || 0;
        const stepPx = card.offsetWidth + gap;

        this.trackTarget.scrollBy({ left: direction * stepPx, behavior: 'smooth' });
    }

    updateArrows() {
        const track = this.trackTarget;
        const maxScroll = track.scrollWidth - track.clientWidth;

        this.prevTarget.disabled = track.scrollLeft <= 1;
        this.nextTarget.disabled = track.scrollLeft >= maxScroll - 1;
    }
}
