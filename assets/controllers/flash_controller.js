import { Controller } from '@hotwired/stimulus';

/** Flash message that fades out and removes itself from the DOM. */
export default class extends Controller {
    static values = { delay: { type: Number, default: 4000 } };

    connect() {
        // twice as fast on phones
        const mobile = window.matchMedia('(max-width: 600px)').matches;
        const delay = mobile ? this.delayValue / 2 : this.delayValue;
        this.timer = window.setTimeout(() => this.dismiss(), delay);
    }

    disconnect() {
        window.clearTimeout(this.timer);
    }

    dismiss() {
        this.element.classList.add('flash--hide');
        this.element.addEventListener('transitionend', () => this.element.remove(), { once: true });
    }
}
