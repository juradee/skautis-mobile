import { Controller } from '@hotwired/stimulus';

/**
 * Filters an already rendered list in place.
 *
 * The whole unit roster is on the page anyway, so searching it locally is
 * instant and keeps working when the connection drops.
 */
export default class extends Controller {
    static targets = ['input', 'list', 'count', 'empty'];

    filter() {
        const query = this.inputTarget.value.trim().toLowerCase();
        let visible = 0;

        for (const item of this.listTarget.children) {
            const term = item.dataset.filterTerm;

            if (term === undefined) {
                continue;
            }

            const matches = query === '' || term.includes(query);
            item.hidden = !matches;

            if (matches) {
                visible += 1;
            }
        }

        if (this.hasCountTarget) {
            this.countTarget.textContent = String(visible);
        }

        if (this.hasEmptyTarget) {
            this.emptyTarget.hidden = visible > 0;
        }
    }
}
