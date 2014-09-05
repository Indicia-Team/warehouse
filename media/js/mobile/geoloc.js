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

    /**
     *
     */
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

    /**
     *
     * @returns {*}
     */
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
     * @returns {*}
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

    /**
     *
     */
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

    /**
     *
     * @param position
     */
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
                _log("GPS - Success! Accuracy of " + accuracy + " meters");
                navigator.geolocation.clearWatch(app.geoloc.id);

                //save in storage
                var location = {
                    'lat' : latitude,
                    'lon' : longitude,
                    'acc' : accuracy
                };

                app.settings('location', location);

                $(document).trigger('app.geoloc.lock.ok');
            }
        }
    };

    // Callback if geolocation fails.
    m.onError = function(error) {
        _log("GPS - error");
        $(document).trigger('app.geoloc.lock.error');
    };

    /**
     * @param lat
     * @param lon
     * @param acc
     */
    m.set = function(lat, lon, acc){
        this.latitude = lat;
        this.longitude = lon;
        this.accuracy = acc;
    };

    /**
     *
     * @returns {{lat: *, lon: *, acc: *}}
     */
    m.get = function(){
        return {
            'lat' : this.latitude,
            'lon' : this.longitude,
            'acc' : this.accuracy
        }
    };

    /**
     *
     * @returns {*}
     */
    m.getAccuracy = function(){
        return this.accuracy;
    };

    return m;
})(app.geoloc || {}, app.$ || jQuery);
