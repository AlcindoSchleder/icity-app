/**
 * Init main page components.
 *
 * Inicialização dos componentes da página principal
 *
 * @version    1.0.0
 * @package    VocatioTelecom
 * @subpackage js
 * @author     Alcindo Schleder <alcindoschleder@gmail.com>
 *
 */

let StartComponents = function () {

    this._userLocation = {
        coords: {
            lat: 0,
            lng: 0
        },
        error: false,
        msg: ""
    };

    let initComponents = (container) => {
        if (!container) return;
    };
    let _showMapContent = () => {
        const content = "<h2 class='menu-title'><span class='sr-only'>iCity - </span>Localização</h2>\n" +
        "<div id='googleMapUserPos' class='google-map container-fluid'>\n" +
        "</div>";
        const latLng = {
            lat: this._userLocation.coords.lat,
            lng: this._userLocation.coords.lng
        }
        $('.main-content').html(content);

        if ((!this._userLocation) || (this._userLocation.error)) {
            console.error('Erro: Impossivel Criar Mapa= {. Coordenadas não encontrada= {!')
            return;
        };

        const prop = {
            center: this._userLocation.coords,
            zoom: 15,
            mapTypeId:google.maps.MapTypeId.ROADMP
        };
        const mapNode = document.getElementById("googleMapUserPos");
        const googleMap = new google.maps.Map(mapNode, prop);
        const marker = new google.maps.Marker({
            position: this._userLocation.coords, 
            map: googleMap
        });
    };
    return {
        init: (container) => {
            initComponents(container);
        },
        showMap: (pos) => {
            _userLocation = pos;
            _showMapContent();
        }
    };
}();
    
let StartEvents = function () {
    
    let InitComponentsEvents = function () {
        //        $('.menu-nav-bar').on('mouseout', function () {
        //            $('.menu-nav-bar').animate({"width": "0"}, 'slow');
        //        });
        $('.btn-menu').on('click', function () {
            $('.menu-nav-bar').animate({left: 0}, 'slow');
        });
        $('.menu-nav-bar').on('click', function () {
            $('.menu-nav-bar').animate({left: -5000}, 'slow');
        });
        $('.menu-link').on('click', function () {
            let action = $(this).attr('data-action');
           if (StartEvents[action]) {
                StartEvents[action]();
            }
        });
    };
    let showMainContent = function () {
        $('.main-content').html(
            "<h2 class='menu-title'><span class='sr-only'>iCity - </span>Apresentação</h2>\n" +
            "<div class='main-page container'>\n" +
            "</div>"
        );
    };
    let showLocalizationContent = function () {
        IndexController.getLocalization().then(pos => {
            StartComponents.showMap(pos)
        }).catch(posError => {
            console.error('showLocalizationContent: Não posso criar o mapa', posError);
        });
    };
    
    return {
        init: function () {
            InitComponentsEvents();
        },
        showMain: function () {
            showMainContent();
        },
        showLocal: function () {
            showLocalizationContent();
        }
    };
}();

$(document).ready(function () {
    // Portal Settings
    // PSYS.FLAGS.setDebug(); // Uncomment this to turn debug on
    //normalize window.URL
    window.URL || (window.URL = window.webkitURL || window.msURL || window.oURL);
    //normalize window.AudioContext
    // window.AudioContext || (window.AudioContext = window.AudioContext || window.webkitAudioContext);
    //normalize navigator.getUserMedia
    // navigator.getUserMedia || (navigator.getUserMedia = navigator.webkitGetUserMedia || navigator.mozGetUserMedia || navigator.msGetUserMedia);
});

$(window).on('load', function () {
    StartComponents.init(); // starting home page compponents
    StartEvents.init();
});
