const CACHE_NAME = 'simlaundry-v1';
const STATIC_ASSETS = [
    './',
    './offline.html',
    './assets/css/bootstrap.min.css',
    './assets/css/bootstrap-icons.min.css',
    './assets/css/laundrypro.css',
    './assets/js/bootstrap.bundle.min.js',
    './assets/js/chart.min.js',
    './assets/js/laundrypro.js',
    './manifest.json'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(STATIC_ASSETS);
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
            );
        })
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    // Bypass caching for all POST, PUT, DELETE or API requests (especially payments and transactions)
    if (request.method !== 'GET' || url.pathname.startsWith('/api/')) {
        return;
    }

    // Network-first strategy for HTML pages, cache-first for static assets
    if (request.headers.get('accept')?.includes('text/html')) {
        event.respondWith(
            fetch(request)
                .catch(() => caches.match('./offline.html'))
        );
        return;
    }

    // Cache-first for CSS, JS, Fonts, Images
    event.respondWith(
        caches.match(request).then((cachedResponse) => {
            return cachedResponse || fetch(request).then((networkResponse) => {
                if (networkResponse && networkResponse.status === 200 && networkResponse.type === 'basic') {
                    const responseToCache = networkResponse.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(request, responseToCache);
                    });
                }
                return networkResponse;
            });
        })
    );
});
