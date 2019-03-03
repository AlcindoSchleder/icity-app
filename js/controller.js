/**
 * Service Workers Controller.
 *
 * Controlador do Service Workers registros e eventos
 *
 * @version    1.0.0
 * @package    VocatioTelecom
 * @subpackage js
 * @author     Alcindo Schleder <alcindoschleder@gmail.com>
 *
 */

let IndexController = function () {


    /*
     *   If you need to use IDB to store data then change <dbname> to your dbName:
     */
    this._dbName = 'vktio-icity';
    /*   
     *   ex: <vktio-icity> where:
     *        vktio: prefix of database. This is a best pratice because the user can be very apps
     *        icity: name of your app
     */
    this._dbVersion = 1; // version of database
    this._pkField = 'pk-vktio-icity'; // field that has a primary key of database
    this._indexes = { // List of indexes to create on database idb
        "idxArray":
            [
                {
                    "name": "upd-date", // name of index
                    "field": "date_update" // name of field for index
                }
            ]
    };
    this._dbStoreFields = {
        "pk-vktio-icity": '',
        "date_update": ''
    };
    this._dbPromise = null;
    this._lostConnectionToast = null;

    this.startController = function (container) {
        this._container = container;
        //    this._authUser = new AuthUser(this._container); // this function get a authorization to user to refres new version off app
        //    this._postsView = new PostsView(this._container);
        //    this._dbPromise = this._openDatabase(); 
        this._registerServiceWorker();
        this._installApp();
        //    this._cleanImageCache();
        /*
            let indexController = this;
            setInterval(function() {
              indexController._cleanImageCache();
            }, 1000 * 60 * 5);
            this._showCachedMessages().then(function() {
                indexController._openSocket();
            });
        */

    };

    this._getLocation = function () {
        return new Promise((resolve, reject) => {
            try {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(position => {
                        const result = {
                            coords: {
                                lat: position.coords.latitude,
                                lng: position.coords.longitude
                            },
                            error: false,
                            msg: ''
                        }
                        return resolve(result);
                    });
                } else {
                    throw Error("Geo Location not supported by browser");
                }
            } catch (error) {
                const result = {
                    coords: {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    },
                    error: true,
                    msg: this._getLocationError(error)
                }
                return reject(result);
            };
        });
    };
    this._getLocationError = function (error) {
        switch(error.code) {
            case error.PERMISSION_DENIED:
                return "User denied the request for Geolocation."
                break;
            case error.POSITION_UNAVAILABLE:
                return "Location information is unavailable."
                break;
            case error.TIMEOUT:
                return "The request to get user location timed out."
                break;
            case error.UNKNOWN_ERROR:
                return "An unknown error occurred."
                break;
            default:
                return error;
        };
    };
    this._setWathLocation = function () {

    };
    this._unsetWathLocation = function () {

    };
    this._openDatabase = function () {
        // If the browser doesn't support service worker,
        // we don't care about having a database
        if ((!this._dbName) && (!navigator.serviceWorker)) {
            return Promise.resolve();
        }
        if ((!this._dbName) || (!this._dbVersion) || (!this._pkField)) {
            console.Error('Impossible create Database without complete parameters dbName, dbVersion and pkField');
            return Promise.resolve();
        }
        return idb.open(this._dbName, this._dbVersion, upgradeDb => {
            var store = upgradeDb.createObjectStore(dbName, { keyPath: this._pkField });
            // create a index to orer your databse
            if ((this._indexes) && (this._indexes.idxArray)) {
                this._indexes.idxArray.forEach(index => {
                    store.createIndex(index.name, index.field)
                        .then(function () {
                            return;
                        })
                        .catch(function () {
                            console.error('Error on create index into idb: ', index);
                        });
                });
            } else {
                console.Warning('Indexed Database created without indexes.');
            };
        });
    };

    this._installApp = function () {
        window.addEventListener('beforeinstallprompt', event => {
            // beforeinstallprompt Event fired

            // e.userChoice will return a Promise.
            // For more details read: https://developers.google.com/web/fundamentals/getting-started/primers/promises
            event.userChoice.then(choiceResult => {

                console.log(choiceResult.outcome);

                if (choiceResult.outcome === 'dismissed') {
                    console.log('User cancelled home screen install');
                }
                else {
                    console.log('User added to home screen');
                }
            });
        });
    };
    this._registerServiceWorker = function () {
        if (!navigator.serviceWorker) return;

        let indexController = this;

        navigator.serviceWorker.register('/js/sw.js').then(reg => {
            if (!navigator.serviceWorker.controller) return;
            if (reg.installing) {
                indexController._trackInstalling(reg.installing);
                return;
            }
            reg.addEventListener('updatefound', function () {
                indexController._trackInstalling(reg.installing);
            });
        });

        // Ensure refresh is only called once.
        // This works around a bug in "force update on reload".
        let refreshing;
        navigator.serviceWorker.addEventListener('controllerchange', function () {
            if (refreshing) return;
            window.location.reload();
            refreshing = true;
        });
    };

    this._showCachedMessages = function () {
        //    let indexController = this;

        //    return this._dbPromise.then(function (db) {
        // if we're already showing posts, eg shift-refresh
        // or the very first load, there's no point fetching
        // posts from IDB
        //        if (!db || indexController._postsView.showingPosts())
        return;

        //        let index = db.transaction(this._dbName)
        //                .objectStore(this._dbName).index('by-date');

        //        return index.getAll().then(function (messages) {
        //            indexController._postsView.addPosts(messages.reverse());
        //        });
        //    });
    };

    this._trackInstalling = function (worker) {
        worker.addEventListener('statechange', function () {
            if (worker.state === 'installed') {
                // IndexController._updateReady(worker); // uncomment this if you want get a user authorization
                return;
            }
        });
    };

    this._updateReady = function (worker) {
        let auth = this._authUser.show("New version available", {
            buttons: ['refresh', 'dismiss']
        });

        auth.answer.then(function (answer) {
            if (answer !== 'refresh')
                return;
            worker.postMessage({ action: 'skipWaiting' });
        });
    };

    // open a connection to the server for live updates
    this._openSocket = function () {
        let latestPostDate = this._getLastUpdate();

        // create a url pointing to /updates with the ws protocol
        let socketUrl = new URL('/updates', window.location);
        socketUrl.protocol = 'ws';

        if (latestPostDate) {
            socketUrl.search = 'since=' + latestPostDate.valueOf();
        }

        // this is a little hack for the settings page's tests,
        // it isn't needed for Wittr
        socketUrl.search += '&' + location.search.slice(1);

        var ws = new WebSocket(socketUrl.href);

        // add listeners
        ws.addEventListener('open', function () {
            if (IndexController._lostConnectionToast) {
                IndexController._lostConnectionToast.hide();
            }
        });

        ws.addEventListener('message', event => {
            requestAnimationFrame(function () {
                IndexController._onSocketMessage(event.data);
            });
        });
        /*
            ws.addEventListener('close', function () {
                // tell the user
                if (!IndexController._lostConnectionToast) {
                    IndexController._lostConnectionToast = indexController._toastsView.show("Unable to connect. Retrying…");
                }
    
                // try and reconnect in 5 seconds
                setTimeout(function () {
                    indexController._openSocket();
                }, 5000);
            });
        */
    };

    this._cleanImageCache = function () {
        return this._dbPromise.then(function (db) {
            if (!db)
                return;

            var imagesNeeded = [];

            var tx = db.transaction(this._dbName);
            return tx.objectStore(this._dbName).getAll().then(messages => {
                messages.forEach(message => {
                    if (message.photo) {
                        imagesNeeded.push(message.photo);
                    }
                    imagesNeeded.push(message.avatar);
                });

                return caches.open('vktio-content-imgs');
            }).then(function (cache) {
                return cache.keys().then(requests => {
                    requests.forEach(request => {
                        var url = new URL(request.url);
                        if (!imagesNeeded.includes(url.pathname))
                            cache.delete(request);
                    });
                });
            });
        });
    };

    // called when the web socket sends message data
    this._onSocketMessage = function (data) {
        let messages = JSON.parse(data);

        this._dbPromise.then(db => {
            if (!db)
                return;

            let tx = db.transaction(this._dbName, 'readwrite');
            let store = tx.objectStore(this._dbName);
            messages.forEach(message => {
                store.put(message);
            });

            // get index from _indexes property
            let index = this._indexes.indexes[0];
            // limit store to 30 items
            store.index(index.name).openCursor(null, "prev").then(cursor => {
                return cursor.advance(30);
            }).then(function deleteRest(cursor) {
                if (!cursor)
                    return;
                cursor.delete();
                return cursor.continue().then(deleteRest);
            });
        });

        //    this._postsView.addPosts(messages);
    };
    return {
        //main function to initiate the module
        init: function (container) {
            startController(container);
        },
        getLocalization:  function () {
            return new Promise((resolve, reject) => {
                _getLocation().then(pos => {
                    return resolve(pos);
                }).catch(posError => {
                    return reject(posError);
                });
            });
        }
    };
}();

IndexController.init(null);
