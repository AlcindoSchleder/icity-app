let cacheName = 'vktio-icity-v0.1';
let filesToCache = [
    '/icity/',
    '/icity/index.html',
    'https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css',
    'https://use.fontawesome.com/releases/v5.6.1/css/all.css',
//    'css/colors.css',
    '/icity/css/styles.css',
    'https://code.jquery.com/jquery-3.3.1.slim.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js',
    'https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js',
//    'js/array.observe.polyfill.js',
//    'js/object.observe.polyfill.js',
//    'js/push.js',
    '/icity/js/main.js'
];

self.addEventListener('install', function (e) {
    console.log('[ServiceWorker] Installer');
    e.waitUntil(
        caches.open(cacheName).then(function(cache) {
            console.log('[ServiceWorker] Caching app shell');
            return cache.addAll(filesToCache);
        })
    );
});

self.addEventListener('activate', function (e) {
    console.log('[ServiceWorker] Activate');
    e.waitUntil(
        caches.keys().then(function (keyList) {
            return Promise.all(keyList.map(function(key) {
                if (key !== cacheName) {
                    console.log('[ServiceWorker] Removing old cache', key);
                    return caches.delete(key);
                }
            }));
        })
    );
});

self.addEventListener('fetch', function (e) {
    console.log('[ServiceWorker] Fetch', e.request.url);
    e.respondWith(
        caches.match(e.request).then(function(response) {
            return response || fetch(e.request);
        })
    );
});

self.addEventListener('push', function(event) {
  console.log('[Service Worker] Push Received.');
  console.log(`[Service Worker] Push had this data: "${event.data.text()}"`);

  const title = 'i-City - Cidades Interativas';
  const options = {
    body: 'Serviço de push está ok.',
    icon: 'img/icons/icon-72x72.png',
    badge: 'img/icons/icon-72x72.png'
  };

  event.waitUntil(self.registration.showNotification(title, options));
});