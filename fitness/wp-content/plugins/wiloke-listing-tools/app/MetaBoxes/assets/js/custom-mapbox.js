jQuery(window).load(function () {
    let $geocorder = document.getElementById('pw-geocoder');
    mapboxgl.accessToken = WILOKE_MAPBOX.api;

    if (jQuery('body').hasClass('rtl')) {
        mapboxgl.setRTLTextPlugin('https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-rtl-text/v0.2.0/mapbox-gl-rtl-text.js');
    }

    let lat = $geocorder.getAttribute('data-lat');
    let lng = $geocorder.getAttribute('data-lng');

    if (typeof lat === 'undefined' || ! lat.length) {
        lat = 21.027763;
        lng = 105.834160;
    } else {
        lat = parseFloat(lat);
        lng = parseFloat(lng);
    }

    let oMap = new mapboxgl.Map({
        container: 'pw-map',
        style: 'mapbox://styles/mapbox/streets-v11',
        center: [
            lng, lat
        ],
        zoom: 20
    });

    let oMapboxGeocoder = {
        accessToken: mapboxgl.accessToken
    };

    let address = $geocorder.getAttribute('data-address');

    if (typeof address !== 'undefined' && address.length) {
        oMapboxGeocoder.address = address;
    }

    let oGeocoder = new MapboxGeocoder(oMapboxGeocoder);

    $geocorder.appendChild(oGeocoder.onAdd(oMap));
    let $lat = jQuery('.pw-map-latitude'),
        $lng = jQuery('.pw-map-longitude'),
        $mapSearch = jQuery('.pw-map-search');

    oGeocoder.on('result', function (oEV) {
        $mapSearch.val(oEV.result.place_name);
        $lat.val(oEV.result.geometry.coordinates[1]);
        $lng.val(oEV.result.geometry.coordinates[0]);
    });
});
