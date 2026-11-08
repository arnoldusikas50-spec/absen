/* Presensi PWA — Service Worker
 * Scope: /  |  App: /presensi/
 * Strategi:
 *  - Cache Max Age: 1 Hari (24 Jam / 86.400.000 ms)
 *  - Navigasi /presensi/* : NetworkFirst → cache (< 1 hari) → /offline.html
 *  - Aset statis same-origin (/assets/, /icons/): StaleWhileRevalidate (< 1 hari)
 *  - POST / API check-in-out / Realtime data: diabaikan (network only)
 */
const CACHE_VERSION = 'presensi-v7';
const STATIC_CACHE = CACHE_VERSION + '-static';
const PAGES_CACHE = CACHE_VERSION + '-pages';
const ONE_DAY_MS = 24 * 60 * 60 * 1000; // Batas umur cache: 1 hari (24 jam)

const PRECACHE = [
  '/offline.html',
  '/manifest.json',
  '/icons/icon-192.png',
  '/icons/icon-512.png',
  '/icons/maskable-192.png?v=2',
  '/icons/maskable-512.png?v=2',
  '/icons/apple-touch-icon.png',
  '/assets/app/css/tailwind.css',
  '/assets/plugins/bootstrap-icon/bootstrap-icons.min.css',
  '/assets/plugins/jquery/jquery.min.js',
  '/assets/plugins/sweetalert2/sweetalert2.all.min.js'
];

// Helper: Hapus item cache yang umurnya lebih dari 1 hari (24 jam)
async function cleanExpiredCache(cacheName) {
  try {
    const cache = await caches.open(cacheName);
    const requests = await cache.keys();
    const now = Date.now();
    await Promise.all(
      requests.map(async (req) => {
        const res = await cache.match(req);
        if (!res) return;
        const dateHeader = res.headers.get('date') || res.headers.get('sw-cached-at');
        if (dateHeader) {
          const itemTime = new Date(dateHeader).getTime();
          if (!isNaN(itemTime) && now - itemTime > ONE_DAY_MS) {
            await cache.delete(req);
          }
        }
      })
    );
  } catch (err) {
    console.warn('[SW] cleanExpiredCache error:', err);
  }
}

// Helper: Simpan response ke cache dengan cap waktu
async function putWithTimestamp(cacheName, request, response) {
  try {
    const cache = await caches.open(cacheName);
    if (!response.headers.get('date')) {
      const headers = new Headers(response.headers);
      headers.set('sw-cached-at', new Date().toUTCString());
      const body = await response.blob();
      const timedResponse = new Response(body, {
        status: response.status,
        statusText: response.statusText,
        headers: headers
      });
      await cache.put(request, timedResponse);
    } else {
      await cache.put(request, response);
    }
  } catch (e) {
    try {
      const cache = await caches.open(cacheName);
      await cache.put(request, response);
    } catch (e2) {}
  }
}

// Cek apakah item cache sudah kedaluwarsa (> 1 hari)
function isCacheExpired(response) {
  if (!response) return true;
  const dateVal = response.headers.get('date') || response.headers.get('sw-cached-at');
  if (!dateVal) return false;
  const itemTime = new Date(dateVal).getTime();
  if (isNaN(itemTime)) return false;
  return Date.now() - itemTime > ONE_DAY_MS;
}

self.addEventListener('install', (event) => {
  self.skipWaiting();
  event.waitUntil(
    caches.open(STATIC_CACHE).then((cache) =>
      Promise.all(
        PRECACHE.map((url) => cache.add(url).catch((err) => console.warn('[SW] precache gagal:', url, err)))
      )
    )
  );
});

self.addEventListener('activate', (event) => {
  const allow = [STATIC_CACHE, PAGES_CACHE];
  event.waitUntil(
    // 1. Hapus cache versi lama (v1, v2, v3, dll)
    caches.keys()
      .then((keys) => Promise.all(keys.map((k) => (allow.includes(k) ? null : caches.delete(k)))))
      // 2. Bersihkan cache yang berumur > 1 hari
      .then(() => Promise.all([
        cleanExpiredCache(STATIC_CACHE),
        cleanExpiredCache(PAGES_CACHE)
      ]))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('message', (event) => {
  if (event.data === 'SKIP_WAITING') self.skipWaiting();
  if (event.data === 'PURGE_EXPIRED_CACHE') {
    event.waitUntil(
      Promise.all([
        cleanExpiredCache(STATIC_CACHE),
        cleanExpiredCache(PAGES_CACHE)
      ])
    );
  }
});

function isNavigation(request) {
  return request.mode === 'navigate' || (request.headers.get('accept') || '').includes('text/html');
}

function isStaticAsset(url) {
  return (
    url.pathname.startsWith('/assets/') ||
    url.pathname.startsWith('/icons/') ||
    url.pathname === '/favicon.ico' ||
    url.pathname === '/manifest.json' ||
    /\.(css|js|png|jpg|jpeg|webp|svg|gif|ico|woff2?|ttf)$/i.test(url.pathname)
  );
}

// GET API yang tidak boleh di-cache (data realtime presensi)
function isNoCacheApi(url) {
  return (
    url.pathname.includes('/get-notifications') ||
    url.pathname.includes('/pending-leave-count') ||
    url.pathname.includes('/izin-kuota') ||
    url.pathname.includes('/my-attendance') ||
    url.pathname.includes('/my-corrections') ||
    url.pathname.includes('/history') ||
    url.pathname.includes('/denda') ||
    url.pathname.includes('/ranking') ||
    url.pathname.includes('/surat-saya') ||
    url.pathname.includes('/get-events') ||
    url.pathname.includes('/qr-events') ||
    url.pathname.includes('/face-status') ||
    url.pathname.includes('/wfh-quota')
  );
}

// Foto & berkas user (uploads/) adalah data harian yang selalu berubah —
// JANGAN pernah di-cache SW agar tidak menyajikan foto kemarin.
function isDynamicUpload(url) {
  return url.pathname.startsWith('/uploads/');
}

// Dokumen PDF (rekap/laporan) selalu diambil dari network agar tidak
// tersimpan sebagai halaman cache harian.
function isPdfDocument(url) {
  return /\.pdf$/i.test(url.pathname) || /\/pdf$/i.test(url.pathname);
}

self.addEventListener('fetch', (event) => {
  const { request } = event;
  if (request.method !== 'GET') return;

  let url;
  try {
    url = new URL(request.url);
  } catch (e) {
    return;
  }
  // Hanya handle same-origin; CDN (fonts, dsb) biarkan network langsung
  if (url.origin !== self.location.origin) return;
  if (isNoCacheApi(url)) return;
  if (isDynamicUpload(url)) return; // foto harian: network only, tanpa cache
  if (isPdfDocument(url)) return;   // rekap/laporan PDF: network only

  // 1. Navigasi halaman (khususnya /presensi/*): NetworkFirst
  if (isNavigation(request)) {
    event.respondWith(
      fetch(request)
        .then((res) => {
          if (res && res.status === 200 && url.pathname.startsWith('/presensi')) {
            const copy = res.clone();
            putWithTimestamp(PAGES_CACHE, request, copy);
          }
          return res;
        })
        .catch(async () => {
          const cached = await caches.match(request, { ignoreSearch: true });
          if (cached) {
            // Periksa jika cache sudah > 1 hari
            if (isCacheExpired(cached)) {
              caches.open(PAGES_CACHE).then((c) => c.delete(request, { ignoreSearch: true }));
              const offline = await caches.match('/offline.html');
              if (offline) return offline;
            }
            return cached;
          }
          if (url.pathname.startsWith('/presensi')) {
            const shell = await caches.match('/presensi/', { ignoreSearch: true });
            if (shell && !isCacheExpired(shell)) return shell;
          }
          const offline = await caches.match('/offline.html');
          if (offline) return offline;
          return Response.error();
        })
    );
    return;
  }

  // 2. Aset statis: StaleWhileRevalidate dengan batas 1 hari
  if (isStaticAsset(url)) {
    event.respondWith(
      caches.match(request).then(async (cached) => {
        const expired = isCacheExpired(cached);
        if (expired && cached) {
          caches.open(STATIC_CACHE).then((c) => c.delete(request));
        }

        const networkPromise = fetch(request)
          .then((res) => {
            if (res && res.status === 200) {
              const copy = res.clone();
              putWithTimestamp(STATIC_CACHE, request, copy);
            }
            return res;
          })
          .catch(() => cached);

        // Jika cache masih segar (< 1 hari), kembalikan langsung sambil revalidate
        if (cached && !expired) {
          return cached;
        }
        // Jika sudah kadaluarsa (> 1 hari) atau belum ada di cache, tunggu network
        return networkPromise;
      })
    );
  }
});
