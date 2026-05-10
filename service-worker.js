const CACHE = 'minimarket-v1';
const ASSETS = [
  '/login.php',
  '/dashboard.php',
  '/uploads/icon-192 .png',
  '/uploads/icon-512.png'
];

self.addEventListener('install', e => {
  e.waitUntil(caches.open(CACHE).then(c => c.addAll(ASSETS)));
});

self.addEventListener('fetch', e => {
  e.respondWith(
    fetch(e.request).catch(() => caches.match(e.request))
  );
});