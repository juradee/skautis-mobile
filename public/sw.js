/*
 * Service worker for skautIS mobil.
 *
 * Member data is private and changes in skautIS, so pages are never served
 * from the cache while the device is online. The cache exists to make the app
 * start instantly and to show something useful when the signal drops.
 */

const VERSION = 'v1';
const SHELL_CACHE = `shell-${VERSION}`;
const ASSET_CACHE = `assets-${VERSION}`;
const PAGE_CACHE = `pages-${VERSION}`;
const OFFLINE_URL = '/offline';

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(SHELL_CACHE)
            .then((cache) => cache.addAll([OFFLINE_URL]))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    const keep = [SHELL_CACHE, ASSET_CACHE, PAGE_CACHE];

    event.waitUntil(
        caches.keys()
            .then((names) => Promise.all(names.filter((n) => !keep.includes(n)).map((n) => caches.delete(n))))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (url.origin !== self.location.origin) {
        return;
    }

    // Never hand a cached page to someone who just logged out or in.
    if (url.pathname === '/login' || url.pathname === '/logout' || url.pathname === '/odhlasit') {
        return;
    }

    // AssetMapper URLs carry a content digest, so a hit is always correct.
    if (url.pathname.startsWith('/assets/')) {
        event.respondWith(cacheFirst(request, ASSET_CACHE));

        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(networkFirst(request));
    }
});

async function cacheFirst(request, cacheName) {
    const cached = await caches.match(request);

    if (cached) {
        return cached;
    }

    const response = await fetch(request);

    if (response.ok) {
        const cache = await caches.open(cacheName);
        cache.put(request, response.clone());
    }

    return response;
}

async function networkFirst(request) {
    try {
        const response = await fetch(request);

        if (response.ok) {
            const cache = await caches.open(PAGE_CACHE);
            cache.put(request, response.clone());
        }

        return response;
    } catch {
        // Offline: last seen copy of this page, or the offline notice.
        return (await caches.match(request))
            || (await caches.match(OFFLINE_URL))
            || new Response('Offline', { status: 503, headers: { 'Content-Type': 'text/plain' } });
    }
}
