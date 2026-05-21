importScripts('https://storage.googleapis.com/workbox-cdn/releases/7.0.0/workbox-sw.js');

if (workbox) {
    console.log(`Workbox is loaded.`);
    
    self.addEventListener('message', (event) => {
      if (event.data && event.data.type === 'SKIP_WAITING') {
        console.log('Service Worker received SKIP_WAITING message, activating now.');
        self.skipWaiting();
      }
    });

    workbox.precaching.precacheAndRoute([
    {
        "url": "icon-72.png",
        "revision": "d7e2fea916659bbf271b4632f028fed5"
    },
    {
        "url": "icon-96.png",
        "revision": "f1bdf68dd999551760088eb79f5cbcba"
    },
    {
        "url": "icon-128.png",
        "revision": "1ec8980d1a4a78149a5ec7aac0a52a79"
    },
    {
        "url": "icon-144.png",
        "revision": "70839b0a9ce56b64a72ca87b9c29f139"
    },
    {
        "url": "icon-152.png",
        "revision": "24fd20976f6dc6d81b99bdecd571fced"
    },
    {
        "url": "icon-192.png",
        "revision": "286958b45be08bdc916b8e3126b2203d"
    },
    {
        "url": "icon-384.png",
        "revision": "d3d46f60ff5014f7d85d047e1c55329f"
    },
    {
        "url": "icon-512.png",
        "revision": "1a98134a086859d517de611e9e0579bc"
    },
    {
        "url": "favicon.ico",
        "revision": "7cbcf8be98d3ce63133537d07504db0f"
    },
    {
        "url": ".gitattributes",
        "revision": "05bdb783ee6514c8c072e47680af8ff7"
    },
    {
        "url": "build pwa make html app offline and installable yasin best working re-run this for updates best for html only.pyw",
        "revision": "da46200dec49ca639eeaf4cc0335c9cb"
    },
    {
        "url": "icon.svg",
        "revision": "b516c54370c62f167c950366e2262d70"
    },
    {
        "url": "index.html",
        "revision": "297c390406df613cb89a795b839e7ae1"
    },
    {
        "url": "index.php",
        "revision": "c4a11f8d9b707a16e86107d107fb8f94"
    },
    {
        "url": "manifest.json",
        "revision": "3f84a037c8236a23efc8efff433deee9"
    },
    {
        "url": "offline.html",
        "revision": "6897c04e5a56779336074d162c71a242"
    },
    {
        "url": "pwa-register.js",
        "revision": "30562e4c2a2a3e07efc7cf94128baa1e"
    },
    {
        "url": "sw.js",
        "revision": "6f39e8536c623bfd7ff1b833ede4e57a"
    }
]);

    workbox.routing.registerRoute(
        ({request}) => request.destination === 'style' || request.destination === 'script',
        new workbox.strategies.StaleWhileRevalidate({ cacheName: 'asset-cache' })
    );

    workbox.routing.registerRoute(
        ({request}) => request.destination === 'image',
        new workbox.strategies.CacheFirst({
            cacheName: 'image-cache',
            plugins: [ new workbox.expiration.ExpirationPlugin({ maxEntries: 60, maxAgeSeconds: 30 * 24 * 60 * 60 }) ],
        })
    );

    workbox.routing.setCatchHandler(async ({event}) => {
        if (event.request.destination === 'document') {
            return await caches.match('offline.html') || Response.error();
        }
        return Response.error();
    });

} else {
    console.log(`Workbox failed to load.`);
}