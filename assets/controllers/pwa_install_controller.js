import { Controller } from '@hotwired/stimulus';

/**
 * Shows an install button once the browser offers one.
 *
 * Chrome fires beforeinstallprompt and lets us defer it; Safari has no such
 * event, so the button simply stays hidden there and users install from Share.
 */
export default class extends Controller {
    static targets = ['button'];

    connect() {
        this.deferredPrompt = null;
        this.onBeforeInstallPrompt = (event) => {
            event.preventDefault();
            this.deferredPrompt = event;

            if (this.hasButtonTarget) {
                this.buttonTarget.hidden = false;
            }
        };

        window.addEventListener('beforeinstallprompt', this.onBeforeInstallPrompt);
    }

    disconnect() {
        window.removeEventListener('beforeinstallprompt', this.onBeforeInstallPrompt);
    }

    async install() {
        if (!this.deferredPrompt) {
            return;
        }

        this.deferredPrompt.prompt();
        await this.deferredPrompt.userChoice;
        this.deferredPrompt = null;

        if (this.hasButtonTarget) {
            this.buttonTarget.hidden = true;
        }
    }
}
