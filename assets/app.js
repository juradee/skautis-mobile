import './stimulus_bootstrap.js';
import './styles/app.css';

// Register the service worker from the origin root so it can control every page.
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch((error) => {
            console.warn('Service worker se nepodařilo zaregistrovat', error);
        });
    });
}
