/**
 * OrangeHRM BR — PWA service worker.
 *
 * Strategy:
 *   - precache the app shell (dist/js + dist/css + favicon) at install time
 *   - network-first with cache fallback for navigations (the app is a
 *     server-rendered multi-page Vue app, so pages must stay fresh)
 *   - stale-while-revalidate for static assets (js/css/images/fonts)
 *   - never intercept API calls or authenticated endpoints
 *
 * The cache is versioned with the build timestamp written by
 * DumpBuildTimestampPlugin (web/dist/build), so a new deploy
 * invalidates the previous cache automatically.
 */

const CACHE_VERSION = 'ohrm-br-v1';

const SHELL_CACHE = `${CACHE_VERSION}-shell`;
const ASSETS_CACHE = `${CACHE_VERSION}-assets`;

self.addEventListener('install', (event) => {
  event.waitUntil(
    fetch(`${self.registration.scope}dist/build`)
      .then((response) => {
        if (!response.ok) throw new Error('build version unavailable');
        return response.text();
      })
      .catch(() => Date.now().toString())
      .then((buildVersion) => {
        self.buildVersion = buildVersion.trim();
        return caches.open(SHELL_CACHE).then((cache) =>
          cache.addAll([
            `${self.registration.scope}dist/js/app.js?v=${buildVersion}`,
            `${self.registration.scope}dist/js/chunk-vendors.js?v=${buildVersion}`,
            `${self.registration.scope}dist/css/app.css?v=${buildVersion}`,
            `${self.registration.scope}dist/css/chunk-vendors.css?v=${buildVersion}`,
            `${self.registration.scope}dist/favicon.ico`,
            `${self.registration.scope}dist/img/icons/icon-192.png`,
            `${self.registration.scope}dist/img/icons/icon-512.png`,
          ]),
        );
      })
      .then(() => self.skipWaiting()),
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches
      .keys()
      .then((keys) =>
        Promise.all(
          keys
            .filter((key) => key.startsWith('ohrm-') && key !== SHELL_CACHE && key !== ASSETS_CACHE)
            .map((key) => caches.delete(key)),
        ),
      )
      .then(() => self.clients.claim()),
  );
});

function isExcluded(url) {
  return (
    url.pathname.includes('/api/') ||
    url.pathname.includes('/oauth') ||
    url.pathname.includes('/auth/') ||
    url.pathname.includes('/core/i18n/') ||
    url.pathname.includes('/installer/') ||
    url.pathname.includes('/maintenance/') ||
    url.hostname !== self.location.hostname
  );
}

self.addEventListener('fetch', (event) => {
  const request = event.request;

  if (request.method !== 'GET') return;

  const url = new URL(request.url);
  if (isExcluded(url)) return;

  // Navigation requests: network-first, fall back to cached shell pages
  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request)
        .then((response) => {
          const copy = response.clone();
          caches.open(SHELL_CACHE).then((cache) => cache.put(request, copy));
          return response;
        })
        .catch(() =>
          caches.match(request).then((cached) => cached || caches.match(`${self.registration.scope}index.php/auth/login`)),
        ),
    );
    return;
  }

  // Static assets: stale-while-revalidate
  if (
    url.pathname.includes('/dist/') ||
    request.destination === 'image' ||
    request.destination === 'font'
  ) {
    event.respondWith(
      caches.open(ASSETS_CACHE).then((cache) =>
        cache.match(request).then((cached) => {
          const networkFetch = fetch(request)
            .then((response) => {
              if (response && response.ok) {
                cache.put(request, response.clone());
              }
              return response;
            })
            .catch(() => cached);
          return cached || networkFetch;
        }),
      ),
    );
  }
});
