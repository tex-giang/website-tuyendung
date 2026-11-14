/* TEX-GIANG — service worker tối giản.
 * Cần có fetch handler để trình duyệt coi web là PWA cài được.
 * Không cache HTML để tránh hiển thị nội dung cũ.
 */
const VERSION = 'tg-v1';

self.addEventListener('install', () => {
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', (event) => {
  const request = event.request;
  if (request.method !== 'GET') {
    return;
  }
  const url = new URL(request.url);
  if (url.origin !== self.location.origin) {
    return;
  }
  // HTML và API: luôn lấy mạng (không cache) — tránh nội dung lỗi thời.
  if (request.mode === 'navigate' || url.pathname.startsWith('/admin')) {
    return;
  }
  // Tài nguyên tĩnh: ưu tiên cache, cập nhật ngầm.
  event.respondWith(
    caches.open(VERSION).then((cache) =>
      cache.match(request).then((cached) => {
        const network = fetch(request)
          .then((response) => {
            if (response && response.ok) {
              cache.put(request, response.clone());
            }
            return response;
          })
          .catch(() => cached);
        return cached || network;
      })
    )
  );
});