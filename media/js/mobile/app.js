app = (function(m, $){
        //GLOBALS
    m.HOME = "";
    m.DEBUG = false;
    m.$ = $;

        //constants:
    m.TRUE = 1;
    m.FALSE = 0;
    m.ERROR = -1;


    /*
        Events from.
        http://jqmtricks.wordpress.com/2014/03/26/jquery-mobile-page-events/
     */
    m.pageEvents = [
        'pagebeforecreate',
        'pagecreate',
        'pagecontainerbeforechange ',
        'pagecontainerbeforetransition',
        'pagecontainerbeforehide',
        'pagecontainerhide',
        'pagecontainerbeforeshow',
        'pagecontainershow',
        'pagecontainertransition',
        'pagecontainerchange',
        'pagecontainerchangefailed',
        'pagecontainerbeforeload',
        'pagecontainerload',
        'pagecontainerloadfailed',
        'pagecontainerremove'
    ];

    /**
     * Init function
     */
    m.initialise = function(){
        _log('App initialised.');

        //todo: needs tidying up
        //Bind JQM page events with page controller handlers
        $(document).on(app.pageEvents.join(' '), function (e, data) {
            var event = e.type;
            var id = null;
            switch(event){
                case 'pagecreate':
                case 'pagecontainerbeforechange':
                    id = data.prevPage != null ? data.prevPage[0].id : e.target.id;
                    break;

                case 'pagebeforecreate':
                    id = e.target.id;
                    break;

                case 'pagecontainershow':
                case 'pagecontainerbeforetransition':
                case 'pagecontainerbeforehide':
                case 'pagecontainerbeforeshow':
                case 'pagecontainertransition':
                case 'pagecontainerhide':
                case 'pagecontainerchangefailed':
                case 'pagecontainerchange':
                    id = data.toPage[0].id;
                    break;

                case 'pagecontainerbeforeload':
                case 'pagecontainerload':
                case 'pagecontainerloadfailed':
                default:
                    break;
            }

              //  var ihd = e.target.id || data.toPage[0].id;
                var controller = app.controller[id];

                //if page has controller and it has an event handler
                if (controller && controller[event]) {
                    controller[event](e, data);
                }
            });
        };

        /*
         * Starts the submission process.
         */
    m.submitRecord = function() {
            _log("DEBUG: SUBMIT - start");
            var processed = false;
            $(document).trigger('app.submitRecord.start');
            setTimeout(function(){
                //validate form
                var invalids = app.form.validate(indiciaData.jQuery);
                if(invalids.length == 0){
                    //validate GPS lock
                    var gps = app.geoloc.validate();
                    switch(gps){
                        case app.TRUE:
                            _log("DEBUG: GPS Validation - accuracy Good Enough");
                            processed = true;
                            if (navigator.onLine) {
                                //Online
                                _log("DEBUG: SUBMIT - online");
                                var onSaveSuccess = function(savedFormId){
                                    //#2 Post the form
                                    app.io.sendSavedForm(savedFormId);
                                };
                                //#1 Save the form first
                                app.form.save('#entry_form', onSaveSuccess);
                            } else {
                                //Offline
                                _log("DEBUG: SUBMIT - offline");
                                $.mobile.loading('show');
                                if (app.form.save('#entry_form') > 0){
                                    $(document).trigger('app.submitRecord.save');
                                } else {
                                    $(document).trigger('app.submitRecord.error');
                                }
                            }
                            break;
                        case app.FALSE:
                            _log("DEBUG: GPS Validation - accuracy " );
                            $(document).trigger('app.geoloc.lock.bad');
                            break;
                        case app.ERROR:
                            _log("DEBUG: GPS Validation - accuracy -1");
                            $(document).trigger('app.geoloc.lock.no');
                            break;
                        default:
                            _log('DEBUG: GPS validation unknown');
                    }
                } else {
                    jQuery(document).trigger('app.form.invalid', [invalids]);
                }
                $(document).trigger('app.submitRecord.end', [processed]);
            }, 20);
        };

    m.initSettings = function(){
        app.storage.set('settings', {});
    };

    m.settings = function(item, data){
        var settings = app.storage.get('settings');
        if (settings == null){
            app.initSettings();
            settings = app.storage.get('settings');
        }

        if(data != null){
            settings[item] = data;
            return app.storage.set('settings', settings);
        } else {
            return (item != undefined) ? settings[item] : settings;
        }
    };

    return m;
}(window.app || {}, jQuery)); //END