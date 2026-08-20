const CACHE = 'admin-v2';

// Solo se cachean estáticos. Nunca HTML de la aplicación: las vistas del panel
// contienen datos de clientes (nombres, teléfonos, direcciones) y no deben
// quedar en el disco del navegador después de cerrar sesión.
const CACHEABLE = /\.(?:css|js|png|jpe?g|gif|svg|webp|ico|woff2?|ttf|mp3)(?:\?|$)/i;

self.addEventListener('install', e => {
    self.skipWaiting();
});

self.addEventListener('activate', e => {
    e.waitUntil(
        caches.keys()
            .then(keys => Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

// Permite a la app vaciar la caché al cerrar sesión.
self.addEventListener('message', e => {
    if (e.data === 'clear-cache') {
        e.waitUntil(caches.keys().then(keys => Promise.all(keys.map(k => caches.delete(k)))));
    }
});

self.addEventListener('fetch', e => {
    if (e.request.method !== 'GET') return;

    const url = new URL(e.request.url);
    if (!CACHEABLE.test(url.pathname)) return; // se va a la red, sin tocar la caché

    e.respondWith(
        fetch(e.request)
            .then(res => {
                if (res.ok) {
                    const clone = res.clone();
                    caches.open(CACHE).then(c => c.put(e.request, clone));
                }
                return res;
            })
            .catch(() => caches.match(e.request))
    );
});
