const CACHE_VERSION = 'operatore-shell-v1';
const OFFLINE_URL = '/operatore-offline.html';

const PRECACHE = [
  OFFLINE_URL,
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_VERSION).then((cache) => cache.addAll(PRECACHE)),
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(
        keys.filter((key) => key !== CACHE_VERSION).map((key) => caches.delete(key)),
      ),
    ),
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  if (request.method !== 'GET') {
    return;
  }

  if (url.pathname.startsWith('/operatore/api/')) {
    event.respondWith(
      fetch(request).catch(() =>
        new Response(
          JSON.stringify({
            offline: true,
            message: 'API non disponibile offline — riprovare in connettività.',
          }),
          { status: 503, headers: { 'Content-Type': 'application/json' } },
        ),
      ),
    );

    return;
  }

  if (request.mode === 'navigate' && url.pathname.startsWith('/operatore')) {
    event.respondWith(
      fetch(request)
        .then((response) => response)
        .catch(() => caches.match(OFFLINE_URL)),
    );

    return;
  }
});
