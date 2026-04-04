// Extract BASE_PATH from query parameter passed during registration
const BASE_PATH = new URL(self.location).searchParams.get('base') || '';
const CACHE_NAME = 'cfm-tasks-v7';
const STATIC_ASSETS = [
    BASE_PATH + '/pwa/manifest.php',
    BASE_PATH + '/pwa/icons/icon-192.svg',
    BASE_PATH + '/pwa/icons/icon-512.svg'
];
// Never cache these — auth-sensitive or dynamic pages
const NO_CACHE = ['/login.php', '/logout.php', '/pwa/login.php', '/pwa/login_api.php', '/pwa/logout_api.php', '/pwa/index.php', '/forgot_password.php', '/reset_password.php'];

// ═══════════════════════════════════════════════════════════
// INSTALL — cache static shell
// ═══════════════════════════════════════════════════════════
self.addEventListener('install', e => {
    e.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(STATIC_ASSETS))
            .then(() => self.skipWaiting())
    );
});

// ═══════════════════════════════════════════════════════════
// ACTIVATE — clear old caches
// ═══════════════════════════════════════════════════════════
self.addEventListener('activate', e => {
    e.waitUntil(
        caches.keys().then(keys =>
            Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
        ).then(() => self.clients.claim())
    );
});

// ═══════════════════════════════════════════════════════════
// FETCH — network-first for API, cache-first for static
// ═══════════════════════════════════════════════════════════
self.addEventListener('fetch', e => {
    const url = new URL(e.request.url);

    // Never cache auth-related pages — always go to network
    const isNoCachePage = NO_CACHE.some(p => url.pathname.endsWith(p));
    if (isNoCachePage || e.request.method === 'POST') {
        e.respondWith(fetch(e.request));
        return;
    }

    // PHP pages (HTML): network-first, fall back to cache
    if (url.pathname.endsWith('.php') || url.pathname.endsWith('/')) {
        e.respondWith(
            fetch(e.request)
                .then(response => {
                    // If server redirected to login, the session expired — clear cache
                    if (response.redirected && response.url.includes('login.php')) {
                        caches.open(CACHE_NAME).then(c => c.keys().then(keys =>
                            Promise.all(keys.filter(k => !STATIC_ASSETS.some(a => k.url.includes(a)))
                                .map(k => c.delete(k)))
                        ));
                        return response;
                    }
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(CACHE_NAME).then(cache => cache.put(e.request, clone));
                    }
                    return response;
                })
                .catch(() => caches.match(e.request))
        );
        return;
    }

    // API calls: network-first, fall back to cache
    if (url.pathname.startsWith(BASE_PATH + '/api/')) {
        e.respondWith(
            fetch(e.request)
                .then(response => {
                    if (e.request.method === 'GET' && response.ok) {
                        const clone = response.clone();
                        caches.open(CACHE_NAME).then(cache => cache.put(e.request, clone));
                    }
                    return response;
                })
                .catch(() => caches.match(e.request))
        );
        return;
    }

    // Static assets: cache-first
    e.respondWith(
        caches.match(e.request).then(cached => {
            if (cached) return cached;
            return fetch(e.request).then(response => {
                if (response.ok) {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then(cache => cache.put(e.request, clone));
                }
                return response;
            });
        })
    );
});

// ═══════════════════════════════════════════════════════════
// SYNC — flush queued offline actions when back online
// ═══════════════════════════════════════════════════════════
self.addEventListener('sync', e => {
    if (e.tag === 'sync-tasks') {
        e.waitUntil(flushOfflineQueue());
    }
});

async function flushOfflineQueue() {
    const cache = await caches.open(CACHE_NAME);
    const queueRes = await cache.match('/__offline_queue__');
    if (!queueRes) return;

    const queue = await queueRes.json();
    const remaining = [];

    for (const item of queue) {
        try {
            const fd = new FormData();
            Object.entries(item.data).forEach(([k, v]) => fd.set(k, v));
            const res = await fetch(item.url, { method: 'POST', body: fd });
            if (!res.ok) remaining.push(item);
        } catch {
            remaining.push(item);
        }
    }

    if (remaining.length > 0) {
        await cache.put('/__offline_queue__', new Response(JSON.stringify(remaining)));
    } else {
        await cache.delete('/__offline_queue__');
    }

    // Notify clients that sync is done
    const clients = await self.clients.matchAll();
    clients.forEach(client => client.postMessage({ type: 'sync-complete' }));
}

// Listen for queue-action messages from the page
self.addEventListener('message', e => {
    if (e.data && e.data.type === 'queue-action') {
        // Store the action in the offline queue inside the cache
        caches.open(CACHE_NAME).then(async cache => {
            const queueRes = await cache.match('/__offline_queue__');
            const queue = queueRes ? await queueRes.json() : [];
            queue.push(e.data.payload);
            await cache.put('/__offline_queue__', new Response(JSON.stringify(queue)));
        });
    }
});
