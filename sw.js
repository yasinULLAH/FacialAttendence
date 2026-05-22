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
        "revision": "4ba91ac00766609d96565c83e63436d8"
    },
    {
        "url": "client readme.md",
        "revision": "26f4d8aebdf8afdb9b61cb84d2fc02f9"
    },
    {
        "url": "client readmeD.md",
        "revision": "f94aae4214044beffc48c89433d55bb9"
    },
    {
        "url": "features and functionalities.md",
        "revision": "660cdc44bc566a764dd56dec0f80e2f7"
    },
    {
        "url": "features and functionalities.pdf",
        "revision": "aaeb311995e4a7daac4904bd87c94296"
    },
    {
        "url": "icon.svg",
        "revision": "b516c54370c62f167c950366e2262d70"
    },
    {
        "url": "index.html",
        "revision": "6126d4b447bb5dbd6d135c1290f91970"
    },
    {
        "url": "index.php",
        "revision": "58858eba7138db7d7109149ca5d0df68"
    },
    {
        "url": "index.php.before_mysql_fix.bak",
        "revision": "e9f94b3d093b554e2bd8ab04777848de"
    },
    {
        "url": "manifest.json",
        "revision": "3f84a037c8236a23efc8efff433deee9"
    },
    {
        "url": "motion-tracking-overview.md",
        "revision": "123faa4b1a6eba88f314d5d07843f9b8"
    },
    {
        "url": "offline.html",
        "revision": "b12b49ed911aa82a7d4430205f5dfdef"
    },
    {
        "url": "pwa-register.js",
        "revision": "30562e4c2a2a3e07efc7cf94128baa1e"
    },
    {
        "url": "readme.md",
        "revision": "6b39d90f66d0ebf71d4a78276ff3b3b4"
    },
    {
        "url": "readmeD.md",
        "revision": "2211661ed2c30099cce8e45da40d83cb"
    },
    {
        "url": "sw.js",
        "revision": "78ab956ef47e7e5b87fa0e9375099a00"
    },
    {
        "url": "_phpserver_err.log",
        "revision": "30ce6158c7221ac16152a678bb43f639"
    },
    {
        "url": "_phpserver_out.log",
        "revision": "d41d8cd98f00b204e9800998ecf8427e"
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