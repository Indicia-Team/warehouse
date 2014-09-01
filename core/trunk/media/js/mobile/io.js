app = app || {};
app.io = (function(m, $){
    /*
     * Sending all saved forms.
     * @returns {undefined}
     */
    m.sendAllSavedForms = function() {
        if (navigator.onLine) {
            //todo: might need to improve the iteration of the forms
            var forms = app.storage.get(app.storage.FORMS);
            var key = Object.keys(forms)[0]; //getting the first one of the array
            if (key != null) {
                $.mobile.loading('show');
                _log("Sending form: " + key);
                var onSuccess = function(data){
                    var formStorageId = this.callback_data.formStorageId;
                    _log("SEND - form ajax (success): " + formStorageId);

                    app.storage.removeSavedForm(formStorageId);
                    $(document).trigger('app.form.sentall.success');
                    app.io.sendAllSavedForms();
                };
                m.sendSavedForm(key, onSuccess);
            } else {
                $.mobile.loading('hide');
            }
        } else {
            $.mobile.loading( 'show', {
                text: "Looks like you are offline!",
                theme: "b",
                textVisible: true,
                textonly: true
            });

            setTimeout(function(){
                $.mobile.loading('hide');
            }, 3000);
        }
    };

    /*
     * Sends the saved form
     */
    m.sendSavedForm = function(formStorageId, onSuccess, onError, onSend) {
        _log("SEND - creating the form.");
        var data = new app.storage.getSavedForm(formStorageId);
        var form = {
            'data': data,
            'formStorageId' : formStorageId
        };

        this.postForm(form, onSuccess, onError, onSend)
    };

    /*
     * Submits the form.
     */
    m.postForm = function(form, onSuccess, onError, onSend){
        _log('SEND - Posting a form with AJAX.');
        var data = {};
        if(form.data == null){
            //extract the form data
            form = document.getElementById(form.id);
            data = new FormData(form);
        } else {
            data = form.data;
        }

        $.ajax({
            url : m.getFormURL(),
            type : 'POST',
            data : data,
            callback_data : form,
            cache : false,
            enctype : 'multipart/form-data',
            processData : false,
            contentType : false,
            success: onSuccess || m.onSuccess,
            error: onError || m.onError,
            beforeSend: onSend || m.onSend
        });
    };

    /**
     * Function callback on Successful Ajax form post.
     * @param data
     */
    m.onSuccess = function(data){
        var formStorageId = this.callback_data.formStorageId;
        _log("SEND - form ajax (success): " + formStorageId);

        app.storage.removeSavedForm(formStorageId);
        $(document).trigger('app.form.sent.success', [data]);
    };

    /**
     * Function callback on Error Ajax form post.
     * @param xhr
     * @param ajaxOptions
     * @param thrownError
     */
    m.onError = function (xhr, ajaxOptions, thrownError) {
        _log("SEND - form ajax (ERROR "  + xhr.status+ " " + thrownError +")");
        _log(xhr.responseText);

        $(document).trigger('app.form.sent.error', [xhr, thrownError]);
        //TODO:might be a good idea to add a save option here
    };

    /**
     * Function callback before sending the Ajax form post.
     */
    m.onSend = function () {
        _log("SEND - onSend");
    };

    /**
     * Returns App main form Path.
     * @returns {*}
     */
    m.getFormURL = function(){
        return Drupal.settings.basePath + app.settings('formPath');
    };


    /**
     * Services related functions.
     */
    m.services = {};

    /**
     * Main function to Send/Receive request
     */
    m.services.req = function(url, data, onSuccess, onError) {
        var req = new XMLHttpRequest();
        req.onreadystatechange = function() {
            if (req.readyState == 4) {
                if (req.status == 200) {
                    if(onSuccess != null){
                        onSuccess(JSON.parse(req.responseText));
                    }
                }
                else {
                    if (onError != null){
                        onError(req);
                    }
                }
            }
        };

        if (data != null){
            //post
            req.open('POST', url, true);
            req.setRequestHeader("Content-type", "application/json");
            req.send(JSON.stringify(this.data));
        } else {
            //get
            req.open('GET', url, true);
            req.send();
        }
    };

    return m;
}(app.io || {}, jQuery));