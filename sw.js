const CACHE_NAME = 'aura-face-cache-v1';
const ASSETS = [
    './',
    'index.html',
    'manifest.json',
    'icon.svg',
    'https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1.7.12/dist/face-api.js',
    'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap',
    'https://justadudewhohacks.github.io/face-api.js/models/tiny_face_detector_model-weights_manifest.json',
    'https://justadudewhohacks.github.io/face-api.js/models/tiny_face_detector_model-shard1',
    'https://justadudewhohacks.github.io/face-api.js/models/face_landmark_68_model-weights_manifest.json',
    'https://justadudewhohacks.github.io/face-api.js/models/face_landmark_68_model-shard1',
    'https://justadudewhohacks.github.io/face-api.js/models/face_recognition_model-weights_manifest.json',
    'https://justadudewhohacks.github.io/face-api.js/models/face_recognition_model-shard1'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(ASSETS);
        })
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.map((key) => {
                    if (key !== CACHE_NAME) {
                        return caches.delete(key);
                    }
                })
            );
        })
    );
});

self.addEventListener('fetch', (event) => {
    event.respondWith(
        caches.match(event.request).then((cachedResponse) => {
            if (cachedResponse) {
                return cachedResponse;
            }
            return fetch(event.request).then((networkResponse) => {
                if (networkResponse && networkResponse.status === 200) {
                    const cacheCopy = networkResponse.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(event.request, cacheCopy);
                    });
                }
                return networkResponse;
            }).catch(() => {
                return new Response('Offline content unavailable');
            });
        })
    );
});
