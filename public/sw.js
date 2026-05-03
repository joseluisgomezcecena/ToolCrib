// Nexus Tool Crib · Service Worker
// Estrategia: network-first para HTML, cache-first para assets estáticos.
// No cachea respuestas HTTP de la app (movimientos, alertas) — siempre fresh.

const VERSION = 'v1';
const STATIC_CACHE = `tool-crib-static-${VERSION}`;
const RUNTIME_CACHE = `tool-crib-runtime-${VERSION}`;

const STATIC_ASSETS = [
    '/icons/icon.svg',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => cache.addAll(STATIC_ASSETS))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(
                keys
                    .filter((k) => ![STATIC_CACHE, RUNTIME_CACHE].includes(k))
                    .map((k) => caches.delete(k))
            )
        ).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const req = event.request;

    if (req.method !== 'GET') return;

    const url = new URL(req.url);

    // Solo manejamos same-origin (no terceros como CDN externos)
    if (url.origin !== self.location.origin) return;

    // Bypass: WebSocket, Reverb, broadcasting auth, login, logout
    if (
        url.pathname.startsWith('/broadcasting/') ||
        url.pathname.startsWith('/livewire/') ||
        url.pathname === '/login' ||
        url.pathname === '/logout'
    ) {
        return;
    }

    // Assets construidos por Vite (/build/*) y otros estáticos: cache-first
    if (
        url.pathname.startsWith('/build/') ||
        url.pathname.startsWith('/icons/') ||
        /\.(?:css|js|png|jpg|jpeg|svg|woff2?|ttf|ico)$/i.test(url.pathname)
    ) {
        event.respondWith(cacheFirst(req));
        return;
    }

    // Resto (HTML, API): network-first con fallback a cache si offline
    event.respondWith(networkFirst(req));
});

async function cacheFirst(req) {
    const cache = await caches.open(STATIC_CACHE);
    const cached = await cache.match(req);
    if (cached) return cached;
    try {
        const fresh = await fetch(req);
        if (fresh.ok) cache.put(req, fresh.clone());
        return fresh;
    } catch (e) {
        return new Response('Offline', { status: 503 });
    }
}

async function networkFirst(req) {
    const cache = await caches.open(RUNTIME_CACHE);
    try {
        const fresh = await fetch(req);
        if (fresh.ok && req.headers.get('accept')?.includes('text/html')) {
            cache.put(req, fresh.clone());
        }
        return fresh;
    } catch (e) {
        const cached = await cache.match(req);
        if (cached) return cached;
        return new Response(
            '<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Sin conexión</title></head>'
            + '<body style="font-family:system-ui;padding:2rem;text-align:center;background:#0f172a;color:#fff">'
            + '<h1>📡 Sin conexión</h1><p>No se puede contactar al servidor. Reintenta cuando vuelva la red.</p>'
            + '<p><a style="color:#a5b4fc" href="javascript:location.reload()">Reintentar</a></p></body></html>',
            { status: 503, headers: { 'Content-Type': 'text/html; charset=utf-8' } }
        );
    }
}
