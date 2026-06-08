import { Controller } from '@hotwired/stimulus';

/**
 * Filtrowanie katalogu w miejscu (bez przeładowania strony).
 * Brak filtrów → wiersze „Netflix". Dowolna kombinacja (kategoria / gracze / czas)
 * → płaska siatka pasujących gier renderowana pod filtrami.
 */
export default class extends Controller {
    static targets = ['category', 'players', 'time', 'rows', 'results', 'empty', 'clear'];

    apply(event) {
        if (event) event.preventDefault();

        const category = this.categoryTarget.value;
        const players = parseInt(this.playersTarget.value, 10);
        const maxTime = parseInt(this.timeTarget.value, 10);
        const hasPlayers = !Number.isNaN(players);
        const hasTime = !Number.isNaN(maxTime);
        const active = category !== '' || hasPlayers || hasTime;

        this.clearTarget.hidden = !active;

        if (!active) {
            this.showRows();
            return;
        }

        const matches = [...this.rowsTarget.querySelectorAll('.game-card')].filter((card) => {
            if (category && card.dataset.category !== category) return false;
            // przedział domknięty: min <= gracze <= max
            if (hasPlayers && !(+card.dataset.min <= players && +card.dataset.max >= players)) return false;
            if (hasTime && +card.dataset.time > maxTime) return false;
            return true;
        });

        // przy aktywnym filtrze: kolejność alfabetyczna po nazwie (PL)
        const name = (card) => card.querySelector('.game-card__name').textContent.trim();
        matches.sort((a, b) => name(a).localeCompare(name(b), 'pl'));

        this.rowsTarget.hidden = true;
        this.resultsTarget.replaceChildren(...matches.map((card) => card.cloneNode(true)));
        this.resultsTarget.hidden = matches.length === 0;
        this.emptyTarget.hidden = matches.length !== 0;
    }

    clear() {
        this.categoryTarget.value = '';
        this.playersTarget.value = '';
        this.timeTarget.value = '';
        this.clearTarget.hidden = true;
        this.showRows();
    }

    showRows() {
        this.rowsTarget.hidden = false;
        this.resultsTarget.hidden = true;
        this.emptyTarget.hidden = true;
        this.resultsTarget.replaceChildren();
    }
}
