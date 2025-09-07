/**
 * TPT Free ERP - Service Worker
 * Handles caching, offline functionality, and background sync
 */

const CACHE_NAME = 'tpt-erp-v1.0.0';
const STATIC_CACHE = 'tpt-erp-static-v1.0.0';
const DYNAMIC_CACHE = 'tpt-erp-dynamic-v1.0.0';
const API_CACHE = 'tpt-erp-api-v1.0.0';

// Files to cache immediately
const STATIC_FILES = [
  '/',
  '/index.html',
  '/manifest.json',
  '/assets/css/main.css',
  '/assets/css/responsive.css',
  '/assets/js/config.js',
  '/assets/js/utils.js',
  '/assets/js/api.js',
  '/assets/js/router.js',
  '/assets/js/state.js',
  '/assets/js/components.js',
  '/assets/js/app.js',
  '/assets/js/components/LoginForm.js',
  '/assets/js/components/Dashboard.js',
  '/assets/js/components/DataTable.js',
  '/assets/js/components/Form.js',
  '/assets/js/components/Modal.js',
  '/assets/js/components/Navigation.js',
  '/assets/js/components/Notification.js',
  '/assets/icons/icon-192x192.png',
  '/assets/icons/icon-512x512.png'
];

// API endpoints to cache
const API_ENDPOINTS = [
  '/api/dashboard/stats',
  '/api/dashboard/activity',
  '/api/dashboard/tasks',
  '/api/dashboard/system-status',
  '/api/dashboard/notifications',
  '/api/user/profile',
  '/api/modules',
  '/api/settings'
];

// Files that should never be cached
const EXCLUDE_FROM_CACHE = [
  '/api/auth/login',
  '/api/auth/logout',
  '/api/auth/refresh',
  '/api/audit',
  '/api/logs'
];

// ============================================================================
// INSTALL EVENT
// ============================================================================

self.addEventListener('install', (event) => {
  console.log('[SW] Installing service worker');

  event.waitUntil(
    Promise.all([
      // Cache static files
      caches.open(STATIC_CACHE).then((cache) => {
        console.log('[SW] Caching static files');
        return cache.addAll(STATIC_FILES);
      }),

      // Skip waiting to activate immediately
      self.skipWaiting()
    ])
  );
});

// ============================================================================
// ACTIVATE EVENT
// ============================================================================

self.addEventListener('activate', (event) => {
  console.log('[SW] Activating service worker');

  event.waitUntil(
    Promise.all([
      // Clean up old caches
      cleanupOldCaches(),

      // Take control of all clients
      self.clients.claim()
    ])
  );
});

// ============================================================================
// FETCH EVENT
// ============================================================================

self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Skip non-GET requests
  if (request.method !== 'GET') {
    return;
  }

  // Skip external requests
  if (!url.origin.includes(self.location.origin) && !isAllowedExternalRequest(url)) {
    return;
  }

  // Skip excluded endpoints
  if (shouldExcludeFromCache(url.pathname)) {
    return;
  }

  // Handle different types of requests
  if (isApiRequest(url)) {
    event.respondWith(handleApiRequest(request));
  } else if (isStaticAsset(url)) {
    event.respondWith(handleStaticRequest(request));
  } else {
    event.respondWith(handlePageRequest(request));
  }
});

// ============================================================================
// MESSAGE EVENT
// ============================================================================

self.addEventListener('message', (event) => {
  const { type, data } = event.data;

  switch (type) {
    case 'SKIP_WAITING':
      self.skipWaiting();
      break;

    case 'CACHE_DATA':
      cacheData(data);
      break;

    case 'CLEAR_CACHE':
      clearCache(data);
      break;

    case 'SYNC_DATA':
      syncData(data);
      break;

    case 'UPDATE_CACHE':
      updateCache(data);
      break;

    default:
      console.log('[SW] Unknown message type:', type);
  }
});

// ============================================================================
// BACKGROUND SYNC
// ============================================================================

self.addEventListener('sync', (event) => {
  console.log('[SW] Background sync triggered:', event.tag);

  switch (event.tag) {
    case 'background-sync':
      event.waitUntil(syncPendingRequests());
      break;

    case 'data-sync':
      event.waitUntil(syncOfflineData());
      break;

    case 'notification-sync':
      event.waitUntil(syncNotifications());
      break;

    default:
      console.log('[SW] Unknown sync tag:', event.tag);
  }
});

// ============================================================================
// PUSH NOTIFICATIONS
// ============================================================================

self.addEventListener('push', (event) => {
  console.log('[SW] Push notification received');

  if (!event.data) return;

  const data = event.data.json();

  const options = {
    body: data.body,
    icon: '/assets/icons/icon-192x192.png',
    badge: '/assets/icons/icon-72x72.png',
    vibrate: [100, 50, 100],
    data: {
      url: data.url || '/',
      action: data.action
    },
    actions: [
      {
        action: 'view',
        title: 'View',
        icon: '/assets/icons/action-view.png'
      },
      {
        action: 'dismiss',
        title: 'Dismiss'
      }
    ],
    requireInteraction: true,
    silent: false
  };

  event.waitUntil(
    self.registration.showNotification(data.title || 'TPT ERP', options)
  );
});

// ============================================================================
// NOTIFICATION CLICK
// ============================================================================

self.addEventListener('notificationclick', (event) => {
  console.log('[SW] Notification clicked');

  event.notification.close();

  const { action, data } = event.notification;

  if (action === 'view' || !action) {
    const url = data?.url || '/';

    event.waitUntil(
      clients.matchAll({ type: 'window' }).then((clientList) => {
        // Check if there's already a window/tab open
        for (const client of clientList) {
          if (client.url === url && 'focus' in client) {
            return client.focus();
          }
        }

        // If not, open a new window
        if (clients.openWindow) {
          return clients.openWindow(url);
        }
      })
    );
  }
});

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

async function cleanupOldCaches() {
  const cacheNames = await caches.keys();

  return Promise.all(
    cacheNames
      .filter((name) => name !== CACHE_NAME && name !== STATIC_CACHE && name !== DYNAMIC_CACHE && name !== API_CACHE)
      .map((name) => {
        console.log('[SW] Deleting old cache:', name);
        return caches.delete(name);
      })
  );
}

function shouldExcludeFromCache(pathname) {
  return EXCLUDE_FROM_CACHE.some(excluded => pathname.includes(excluded));
}

function isApiRequest(url) {
  return url.pathname.startsWith('/api/');
}

function isStaticAsset(url) {
  const staticExtensions = ['.css', '.js', '.png', '.jpg', '.jpeg', '.gif', '.svg', '.ico', '.woff', '.woff2', '.ttf', '.eot'];
  return staticExtensions.some(ext => url.pathname.endsWith(ext));
}

function isAllowedExternalRequest(url) {
  // Allow requests to CDNs, fonts, etc.
  const allowedDomains = [
    'fonts.googleapis.com',
    'fonts.gstatic.com',
    'cdn.jsdelivr.net',
    'unpkg.com',
    'cdnjs.cloudflare.com'
  ];

  return allowedDomains.some(domain => url.hostname.includes(domain));
}

async function handleApiRequest(request) {
  const url = new URL(request.url);

  // Try network first for API requests
  try {
    const networkResponse = await fetch(request);

    // Cache successful GET responses
    if (networkResponse.ok && request.method === 'GET') {
      const cache = await caches.open(API_CACHE);
      cache.put(request, networkResponse.clone());
    }

    return networkResponse;
  } catch (error) {
    // Network failed, try cache
    console.log('[SW] Network failed for API request, trying cache');

    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
      return cachedResponse;
    }

    // Return offline response
    return new Response(
      JSON.stringify({
        error: 'Offline',
        message: 'You are currently offline. This data may be outdated.'
      }),
      {
        status: 503,
        statusText: 'Service Unavailable',
        headers: { 'Content-Type': 'application/json' }
      }
    );
  }
}

async function handleStaticRequest(request) {
  // Try cache first for static assets
  const cachedResponse = await caches.match(request);
  if (cachedResponse) {
    return cachedResponse;
  }

  // Not in cache, fetch from network
  try {
    const networkResponse = await fetch(request);
    if (networkResponse.ok) {
      const cache = await caches.open(STATIC_CACHE);
      cache.put(request, networkResponse.clone());
    }
    return networkResponse;
  } catch (error) {
    console.log('[SW] Failed to fetch static asset:', request.url);
    return new Response('Asset not available offline', { status: 404 });
  }
}

async function handlePageRequest(request) {
  // Try cache first
  const cachedResponse = await caches.match(request);
  if (cachedResponse) {
    return cachedResponse;
  }

  // Not in cache, fetch from network
  try {
    const networkResponse = await fetch(request);

    // Cache successful HTML responses
    if (networkResponse.ok && request.headers.get('accept').includes('text/html')) {
      const cache = await caches.open(DYNAMIC_CACHE);
      cache.put(request, networkResponse.clone());
    }

    return networkResponse;
  } catch (error) {
    // Return offline page
    const offlineResponse = await caches.match('/offline.html');
    if (offlineResponse) {
      return offlineResponse;
    }

    // Fallback offline response
    return new Response(
      `
      <!DOCTYPE html>
      <html>
      <head>
        <title>Offline - TPT ERP</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <style>
          body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
          h1 { color: #007bff; }
          p { color: #666; }
        </style>
      </head>
      <body>
        <h1>You're Offline</h1>
        <p>Please check your internet connection and try again.</p>
        <button onclick="window.location.reload()">Retry</button>
      </body>
      </html>
      `,
      {
        headers: { 'Content-Type': 'text/html' }
      }
    );
  }
}

async function cacheData(data) {
  try {
    const cache = await caches.open(DYNAMIC_CACHE);
    for (const [key, value] of Object.entries(data)) {
      const request = new Request(`/api/${key}`);
      const response = new Response(JSON.stringify(value), {
        headers: { 'Content-Type': 'application/json' }
      });
      await cache.put(request, response);
    }
    console.log('[SW] Data cached successfully');
  } catch (error) {
    console.error('[SW] Failed to cache data:', error);
  }
}

async function clearCache(data) {
  try {
    const cacheNames = data?.cacheNames || [STATIC_CACHE, DYNAMIC_CACHE, API_CACHE];

    for (const cacheName of cacheNames) {
      const cache = await caches.open(cacheName);
      const keys = await cache.keys();

      for (const request of keys) {
        if (!data?.patterns || data.patterns.some(pattern => request.url.includes(pattern))) {
          await cache.delete(request);
        }
      }
    }

    console.log('[SW] Cache cleared successfully');
  } catch (error) {
    console.error('[SW] Failed to clear cache:', error);
  }
}

async function syncData(data) {
  try {
    // Implement data synchronization logic
    console.log('[SW] Syncing data:', data);

    // This would typically send queued requests to the server
    // and handle conflicts/resolutions

    // Notify clients of sync completion
    const clients = await self.clients.matchAll();
    clients.forEach(client => {
      client.postMessage({
        type: 'SYNC_COMPLETE',
        data: data
      });
    });

  } catch (error) {
    console.error('[SW] Data sync failed:', error);
  }
}

async function updateCache(data) {
  try {
    const cache = await caches.open(STATIC_CACHE);

    for (const url of data.urls) {
      const request = new Request(url);
      const response = await fetch(request);

      if (response.ok) {
        await cache.put(request, response);
      }
    }

    console.log('[SW] Cache updated successfully');
  } catch (error) {
    console.error('[SW] Failed to update cache:', error);
  }
}

async function syncPendingRequests() {
  try {
    // Get pending requests from IndexedDB or similar
    const pendingRequests = await getPendingRequests();

    for (const request of pendingRequests) {
      try {
        await fetch(request);
        // Remove from pending on success
        await removePendingRequest(request.id);
      } catch (error) {
        console.log('[SW] Failed to sync request:', request.url);
      }
    }

    console.log('[SW] Pending requests synced');
  } catch (error) {
    console.error('[SW] Failed to sync pending requests:', error);
  }
}

async function syncOfflineData() {
  try {
    // Sync offline data changes
    const offlineData = await getOfflineData();

    for (const data of offlineData) {
      try {
        await syncDataToServer(data);
        await removeOfflineData(data.id);
      } catch (error) {
        console.log('[SW] Failed to sync offline data:', data.id);
      }
    }

    console.log('[SW] Offline data synced');
  } catch (error) {
    console.error('[SW] Failed to sync offline data:', error);
  }
}

async function syncNotifications() {
  try {
    // Sync notification preferences or pending notifications
    console.log('[SW] Syncing notifications');
  } catch (error) {
    console.error('[SW] Failed to sync notifications:', error);
  }
}

// Placeholder functions for data persistence
// These would typically use IndexedDB or similar

async function getPendingRequests() {
  // Return array of pending requests
  return [];
}

async function removePendingRequest(id) {
  // Remove pending request by ID
}

async function getOfflineData() {
  // Return array of offline data changes
  return [];
}

async function syncDataToServer(data) {
  // Send data to server
}

async function removeOfflineData(id) {
  // Remove offline data by ID
}

// ============================================================================
// PERIODIC BACKGROUND SYNC (if supported)
// ============================================================================

if ('periodicSync' in self.registration) {
  self.addEventListener('periodicsync', (event) => {
    if (event.tag === 'content-sync') {
      event.waitUntil(syncContent());
    }
  });
}

async function syncContent() {
  try {
    // Sync content in background
    console.log('[SW] Periodic content sync');

    // Update cache with fresh content
    const cache = await caches.open(STATIC_CACHE);

    for (const url of STATIC_FILES) {
      try {
        const response = await fetch(url);
        if (response.ok) {
          await cache.put(url, response);
        }
      } catch (error) {
        console.log('[SW] Failed to update:', url);
      }
    }

  } catch (error) {
    console.error('[SW] Periodic sync failed:', error);
  }
}

// ============================================================================
// UTILITY FUNCTIONS
// ============================================================================

function notifyClients(message) {
  self.clients.matchAll().then(clients => {
    clients.forEach(client => {
      client.postMessage(message);
    });
  });
}

// Export for development
if (typeof module !== 'undefined' && module.exports) {
  module.exports = {
    CACHE_NAME,
    STATIC_CACHE,
    DYNAMIC_CACHE,
    API_CACHE
  };
}
