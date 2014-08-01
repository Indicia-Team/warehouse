app = app || {};

app.geoloc = (function(m, $){
    m.TIMEOUT = 120000;
    m.HIGH_ACCURACY = true;
    m.GPS_ACCURACY_LIMIT = 26000;

    m.latitude = null;
    m.longitude = null;
    m.accuracy = -1;

    m.start_time = 0;
    m.tries = 0;
    m.id = 0;
    m.map = null;

    m.start = function(){
        switch (this.run()){
            case app.TRUE:
                _log("GPS - Success! Accuracy of " + this.getAccuracy() + " meters");
                $(document).trigger('app.geoloc.lock.ok');
                break;
            case app.FALSE:
                _log("GPS - start");
                $(document).trigger('app.geoloc.lock.start');
                break;
            case app.ERROR:
                _log("GPS - error, no gps support!");
                $(document).trigger('app.geoloc.noGPS');
                break;
            default:
        }
    };

    m.run = function(){
        // Early return if geolocation not supported.
        if(!navigator.geolocation) {
            return app.ERROR;
        }

        //stop any other geolocation service started before
        navigator.geolocation.clearWatch(this.id);

        //check if the lock is acquired and the accuracy is good enough
        var accuracy = app.geoloc.getAccuracy();
        if ((accuracy > -1) && (accuracy < this.GPS_ACCURACY_LIMIT)){
            return app.TRUE;
        }

        this.start_time = new Date().getTime();
        this.tries = (this.tries == 0) ? 1 : this.tries +  1;

        // Request geolocation.
        this.id = app.geoloc.watchPosition();
        return app.FALSE;
    };

    /*
     * Validates the current GPS lock quality
     */
    m.validate = function(){
        var accuracy = this.getAccuracy();
        if ( accuracy == -1 ){
            //No GPS lock yet
            return app.ERROR;

        } else if (accuracy > this.GPS_ACCURACY_LIMIT){
            //Geolocated with bad accuracy
            return app.FALSE;

        } else {
            //Geolocation accuracy is good enough
            return app.TRUE;
        }
    };

    m.watchPosition = function(){
        // Geolocation options.
        var options = {
            enableHighAccuracy: app.geoloc.HIGH_ACCURACY,
            maximumAge: 0,
            timeout: app.geoloc.TIMEOUT
        };

        navigator.geolocation.watchPosition(this.onSuccess,
            this.onError, options);
    };

    m.onSuccess = function(position) {
        //timeout
        var current_time = new Date().getTime();
        if ((current_time - app.geoloc.start_time) > app.geoloc.TIMEOUT){
            //stop everything
            navigator.geolocation.clearWatch(app.geoloc.id);
            _log("GPS - timeout");
            $(document).trigger('app.geoloc.lock.timeout');
        }

        var latitude  = position.coords.latitude;
        var longitude = position.coords.longitude;
        var accuracy = position.coords.accuracy;

        //set for the first time
        var prev_accuracy = app.geoloc.getAccuracy();
        if (prev_accuracy == -1){
            prev_accuracy = accuracy + 1;
        }

        //only set it up if the accuracy is increased
        if (accuracy > -1 && accuracy < prev_accuracy){
            app.geoloc.set(latitude, longitude, accuracy);
            _log("GPS - setting accuracy of " + accuracy + " meters" );
            if (accuracy < app.geoloc.GPS_ACCURACY_LIMIT){
                navigator.geolocation.clearWatch(app.geoloc.id);
                _log("GPS - Success! Accuracy of " + accuracy + " meters");
                $(document).trigger('app.geoloc.lock.ok');
            }
        }
    };

    // Callback if geolocation fails.
    m.onError = function(error) {
        _log("GPS - error");
        $(document).trigger('app.geoloc.lock.error');
    };

    m.set = function(lat, lon, acc){
        this.latitude = lat;
        this.longitude = lon;
        this.accuracy = acc;

        $('#imp-sref').val(lat + ', ' + lon);
        $('#sref_accuracy').val(acc);
    };

    m.getAccuracy = function(){
        return this.accuracy;
    };

    /**
     * Mapping
     */
    m.initializeMap = function() {
        _log("initialising map");
        //todo: add checking
        var mapCanvas = $('#map-canvas')[0];
        var mapOptions = {
            zoom: 5,
            center: new google.maps.LatLng(57.686988, -14.763319),
            zoomControl: true,
            zoomControlOptions: {
                style: google.maps.ZoomControlStyle.SMALL
            },
            panControl: false,
            linksControl: false,
            streetViewControl: false,
            overviewMapControl: false,
            scaleControl: false,
            rotateControl: false,
            mapTypeControl: true,
            mapTypeControlOptions: {
                style: google.maps.MapTypeControlStyle.HORIZONTAL_BAR
            },
            styles: [
                {"featureType": "landscape",
                    "stylers": [
                        {"hue": "#FFA800"},
                        {"saturation": 0},
                        {"lightness": 0},
                        {"gamma": 1}
                    ]
                },
                {"featureType": "road.highway",
                    "stylers": [
                        {"hue": "#53FF00"},
                        {"saturation": -73},
                        {"lightness": 40},
                        {"gamma": 1}
                    ]
                },
                {"featureType": "road.arterial",
                    "stylers": [
                        {"hue": "#FBFF00"},
                        {"saturation": 0},
                        {"lightness": 0},
                        {"gamma": 1}
                    ]
                },
                {"featureType": "road.local",
                    "stylers": [
                        {"hue": "#00FFFD"},
                        {"saturation": 0},
                        {"lightness": 30},
                        {"gamma": 1}
                    ]
                },
                {"featureType": "water",
                    "stylers": [
                        {"saturation": 43},
                        {"lightness": -11},
                        {"hue": "#0088ff"}
                    ]
                },
                {"featureType": "poi",
                    "stylers": [
                        {"hue": "#679714"},
                        {"saturation": 33.4},
                        {"lightness": -25.4},
                        {"gamma": 1}
                    ]
                }
            ]
        };
        this.map = new google.maps.Map(mapCanvas ,mapOptions);
        var marker = new google.maps.Marker({
            position: new google.maps.LatLng(-25.363, 131.044),
            map: app.geoloc.map,
            icon: 'http://maps.google.com/mapfiles/marker_green.png',
            draggable:true
        });
        marker.setVisible(false);

        var update_timeout = null; //to clear changing of marker on double click
        google.maps.event.addListener(this.map, 'click', function(event) {
            //have to wait for double click
            update_timeout = setTimeout(function () {
                var latLng = event.latLng;
                marker.setPosition(latLng);
                marker.setVisible(true);
                app.geoloc.set(latLng.lat(), latLng.lng(), 1);
            }, 200);
        });

        //removes single click action
        google.maps.event.addListener(this.map, 'dblclick', function(event) {
            clearTimeout(update_timeout);
        });

        google.maps.event.addListener(marker, 'dragend', function(){
            var latLng = marker.getPosition();
            app.geoloc.set(latLng.lat(), latLng.lng(), 1);
        });

        //Set map centre
        if(this.latitude != null && this.longitude != null){
            var latLong = new google.maps.LatLng(this.latitude, this.longitude);
            app.geoloc.map.setCenter(latLong);
            app.geoloc.map.setZoom(15);
        } else if (navigator.geolocation) {
            //Geolocation
            var options = {
                enableHighAccuracy: true,
                maximumAge: 60000,
                timeout: 5000
            };

            navigator.geolocation.getCurrentPosition(function(position) {
                var latLng = new google.maps.LatLng(position.coords.latitude,
                    position.coords.longitude);
                app.geoloc.map.setCenter(latLng);
                app.geoloc.map.setZoom(15);
            }, null, options);
        }

        this.fixTabMap("#sref-opts", '#map');

        //todo: create event
        $('#sref-opts').enableTab(1);
    };

    /**
     * Fix one tile rendering in jQuery tabs
     * @param tabs
     * @param mapTab
     */
    m.fixTabMap = function(tabs, mapTab){
        $(tabs).on("tabsactivate.googleMap", function(event, ui){
                //check if this is a map tab
                if(ui['newPanel']['selector'] == mapTab){
                    google.maps.event.trigger( app.geoloc.map, 'resize' );
                    if(app.geoloc.latitude != null && app.geoloc.longitude != null){
                        var latLong = new google.maps.LatLng(app.geoloc.latitude,
                            app.geoloc.longitude);

                        app.geoloc.map.setCenter(latLong);
                        app.geoloc.map.setZoom(15);
                    }
                    $(tabs).off("tabsactivate.googleMap");
                }
            }
        );
    };

    /**
     *
     * @param sref
     * @param gref
     */
    m.translateGridRef = function(gref, sref){
        var val = $(gref).val();
        var gridref = OsGridRef.parse(val);
        if(!isNaN(gridref.easting) && !isNaN(gridref.northing)){
            var latLon = OsGridRef.osGridToLatLon(gridref);
            $(sref).val(latLon.lat + ', ' + latLon.lon);
        }
        //todo: set accuracy dependant on Gref

    };

    return m;
})(app.geoloc || {}, app.$ || jQuery);
