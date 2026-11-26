/* 家計簿 service worker: fast start, offline fallback. Never caches your data changes. */
const VERSION = 'kakeibo-v1';
const SHELL = ['/offline.html', '/css/kakeibo.css', '/js/kakeibo.js', '/icons/icon-192.png', '/icons/icon-512.png', '/manifest.webmanifest'];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(VERSION).then((c) => c.addAll(SHELL)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => k !== VERSION).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const req = event.request;
    if (req.method !== 'GET') return;                       // saving always goes to the server
    const url = new URL(req.url);

    // Pages: always fresh from the server; offline → friendly page.
    if (req.mode === 'navigate') {
        event.respondWith(fetch(req).catch(() => caches.match('/offline.html')));
        return;
    }

    // Our static files and Google Fonts: cache first, refresh in the background.
    const isStatic = url.origin === location.origin && /^\/(css|js|icons)\//.test(url.pathname);
    const isFont = /fonts\.(googleapis|gstatic)\.com$/.test(url.hostname);
    if (isStatic || isFont) {
        event.respondWith(
            caches.open(VERSION).then((cache) =>
                cache.match(req).then((hit) => {
                    const fresh = fetch(req).then((res) => {
                        if (res.ok || res.type === 'opaque') cache.put(req, res.clone());
                        return res;
                    }).catch(() => hit);
                    return hit || fresh;
                })
            )
        );
    }
});
