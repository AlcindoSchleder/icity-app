let staticCacheName = 'vktio-icity-v0.5';
let staticCacheImgs = 'vktio-icity-img-v0.5';
let allCaches = [
    staticCacheName,
    staticCacheImgs
];

self.addEventListener('install', event => {
    event.waitUntil(caches.open(staticCacheName).then(cache => {
        return cache.addAll([
            '/',
            '/index.html',
            'https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css',
            'https://use.fontawesome.com/releases/v5.6.1/css/all.css',
            //    '/icity/css/colors.css',
            '/css/styles.css',
            'https://code.jquery.com/jquery-3.3.1.slim.min.js',
            'https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js',
            'https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js',
            //    'js/push.js',
            '/js/main.js',
            '/img/LogoVocatioIBMiCity.png'
        ]);
    }));
});

self.addEventListener('activate', event => {
    event.waitUntil(caches.keys().then(cacheNames => {
        return Promise.all(cacheNames.filter(cacheName => {
            return cacheName.startsWith('vktio-') && !allCaches.includes(cacheName);
        }).map(cacheName => {
            return caches.delete(cacheName);
        }));
    }));
});

self.addEventListener('fetch', function (event) {
    let requestUrl = new URL(event.request.url);

    if (requestUrl.origin === location.origin) {
        if (requestUrl.pathname === '/') {
            event.respondWith(caches.match('/')); // page index.html wothout any formated data
            return;
        }
        if (requestUrl.pathname.startsWith('/photos/')) { // All images used into html
            event.respondWith(servePhoto(event.request));
            return;
        }
        if (requestUrl.pathname.startsWith('/videos/')) { // All videos used into html
            event.respondWith(serveVideo(event.request));
            return;
        }
        if (requestUrl.pathname.startsWith('/avatars/')) { // All avatars coleted and used into html
            event.respondWith(serveAvatar(event.request));
            return;
        }
    }

    event.respondWith(caches.match(event.request)
        .then(response => {
            return response || fetch(event.request);
        })
        .catch(err => {
            console.Error("'Error: Can't retive data from request.", err, event.request);
        })
    );
});

function serveAvatar(request) {
    var storageUrl = request.url.replace(/-\dx\.jpg$/, '');

    return caches.open(staticCacheImgs).then(cache => {
        return cache.match(storageUrl).then(response => {
            if (response)
                return response;

            var networkFetch = fetch(request).then(networkResponse => {
                cache.put(storageUrl, networkResponse.clone());
                return networkResponse;
            });

            return response || networkFetch;
        });
    });
}

function servePhoto(request) {
    var storageUrl = request.url.replace(/-\d+px\.jpg$/, '');

    return caches.open(staticCacheImgs).then(cache => {
        return cache.match(storageUrl).then(response => {
            if (response)
                return response;

            return fetch(request).then(networkResponse => {
                cache.put(storageUrl, networkResponse.clone());
                return networkResponse;
            });
        });
    });
}

function serveVideo(request) {
    var storageUrl = request.url.replace(/-\d+px\.webm$/, '');

    return caches.open(staticCacheImgs).then(cache => {
        return cache.match(storageUrl).then(response => {
            if (response)
                return response;

            return fetch(request).then(networkResponse => {
                cache.put(storageUrl, networkResponse.clone());
                return networkResponse;
            });
        });
    });
}

self.addEventListener('message', event => {
    if (event.data.action === 'skipWaiting') {
        self.skipWaiting();
    }
});

self.addEventListener('push', event => {
    console.log('[Service Worker] Push Received.');
    console.log(`[Service Worker] Push had this data: "${event.data.text()}"`);
    const title = 'i-City - Cidades Interativas';
    const options = {
    body: 'Serviço de push está ok.',
        icon: 'img/icons/icon-72x72.png',
        badge: 'img/icons/icon-72x72.png'
    };
});
