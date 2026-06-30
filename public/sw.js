const CACHE_VERSION = 'v2';
const STATIC_CACHE = `tpt-erp-static-${CACHE_VERSION}`;
const DYNAMIC_CACHE = `tpt-erp-dynamic-${CACHE_VERSION}`;
const API_CACHE = `tpt-erp-api-${CACHE_VERSION}`;
const OFFLINE_PAGE = '/offline.html';

const EXCLUDE_FROM_CACHE = [
  '/api/auth/login',
  '/api/auth/logout',
  '/api/auth/refresh',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(STATIC_CACHE).then((cache) =>
      cache.addAll([OFFLINE_PAGE, '/manifest.json']).catch(() => {})
    ).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(
        keys.filter((k) => k !== STATIC_CACHE && k !== DYNAMIC_CACHE && k !== API_CACHE)
            .map((k) => caches.delete(k))
      )
    ).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const { request } = event;
  if (request.method !== 'GET') return;

  const url = new URL(request.url);

  if (EXCLUDE_FROM_CACHE.some((p) => url.pathname.includes(p))) return;

  if (url.pathname.startsWith('/api/')) {
    event.respondWith(networkFirst(request));
  } else if (/\.(?:css|js|png|jpe?g|gif|svg|ico|woff2?|ttf|eot|webp|avif)$/.test(url.pathname)) {
    event.respondWith(cacheFirst(request));
  } else {
    event.respondWith(staleWhileRevalidate(request));
  }
});

self.addEventListener('message', (event) => {
  if (event.data?.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});

async function cacheFirst(request) {
  const cached = await caches.match(request);
  if (cached) return cached;

  try {
    const response = await fetch(request);
    if (response.ok) {
      const cache = await caches.open(STATIC_CACHE);
      cache.put(request, response.clone());
    }
    return response;
  } catch {
    return new Response('', { status: 408, statusText: 'Offline' });
  }
}

async function networkFirst(request) {
  try {
    const response = await fetch(request);
    if (response.ok && !request.url.includes('/api/auth/')) {
      const cache = await caches.open(API_CACHE);
      cache.put(request, response.clone());
    }
    return response;
  } catch {
    const cached = await caches.match(request);
    if (cached) return cached;

    return new Response(
      JSON.stringify({ error: 'Offline', message: 'You are currently offline.' }),
      { status: 503, headers: { 'Content-Type': 'application/json' } }
    );
  }
}

async function staleWhileRevalidate(request) {
  const cached = await caches.match(request);

  const fetchPromise = fetch(request).then((response) => {
    if (response.ok) {
      const cache = caches.open(DYNAMIC_CACHE).then((c) => c.put(request, response.clone()));
    }
    return response;
  }).catch(() => cached || caches.match(OFFLINE_PAGE));

  return cached || fetchPromise;
}
