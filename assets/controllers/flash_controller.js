import { Controller } from '@hotwired/stimulus';

/** Komunikat (flash) sam znika po chwili: zanika i usuwa się z DOM. */
export default class extends Controller {
    static values = { delay: { type: Number, default: 4000 } };

    connect() {
        this.timer = window.setTimeout(() => this.dismiss(), this.delayValue);
    }

    disconnect() {
        window.clearTimeout(this.timer);
    }

    dismiss() {
        this.element.classList.add('flash--hide');
        this.element.addEventListener('transitionend', () => this.element.remove(), { once: true });
    }
}
