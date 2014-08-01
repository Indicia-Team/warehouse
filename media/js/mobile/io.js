app = app || {};
app.io = (function(m, $){
    /*
     * Sending all saved forms.
     * @returns {undefined}
     */
    m.sendAllSavedForms = function() {
        if (navigator.onLine) {
            var count = app.storage.get(app.storage.FORM_COUNT_KEY);
            if (count > 0) {
                $.mobile.loading('show');
                _log("Sending form: " + count);
                m.sendSavedForm();
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
     * Sends the form recursively
     */
    m.sendSavedForm = function() {
        _log("SEND");
        var formsCount = app.storage.get(app.storage.FORM_COUNT_KEY);
        //send the last form
        if (formsCount != null && formsCount > 0) {
            //Send form
            _log("SEND - creating the form.");
            var data = new FormData();
            var files_clean = [];
            //files to clean afterwards
            var input_array = app.storage.get(app.storage.FORM_KEY + formsCount);

            for (var k = 0; k < input_array.length; k++) {
                if (input_array[k].type == "file") {
                    var pic_file = app.storage.get(input_array[k].value);
                    if (pic_file != null) {
                        _log("SEND - attaching '" + input_array[k].value + "' to " + input_array[k].name);
                        files_clean.push(input_array[k].value);
                        var type = pic_file.split(";")[0].split(":")[1];
                        var extension = type.split("/")[1];
                        data.append(input_array[k].name, dataURItoBlob(pic_file, type), "pic." + extension);
                    } else {
                        _log("SEND - " + input_array[k].value + " is " + pic_file);
                    }
                } else {
                    var name = input_array[k].name;
                    var value = input_array[k].value;
                    data.append(name, value);
                }
            }

            //AJAX POST
            //TODO: reuse submitForm() function
            _log("SEND - form ajax");
            $.ajax({
                url : m.getFormURL(),
                type : 'POST',
                data : data,
                cache : false,
                enctype : 'multipart/form-data',
                processData : false,
                contentType : false,
                success:function(data){
                    _log("SEND - form ajax (success):");
                    _log(data);

                    //clean
                    _log("SEND - cleaning up");
                    app.storage.remove(app.storage.FORM_KEY + formsCount);
                    app.storage.set(app.storage.FORM_COUNT_KEY, --formsCount);
                    for (var j = 0; j < files_clean.length; j++){
                        app.storage.remove(files_clean[j]);

                    }
                    $(document).trigger('app.form.sentall.success');
                    app.io.sendAllSavedForms();
                },
                error: function (xhr, ajaxOptions, thrownError) {
                    _log("SEND - form ajax (ERROR "  + xhr.status+ " " + thrownError +")");
                    _log(xhr.responseText);

                    $.mobile.loading('hide');
                    var message = "<center><h3>Sorry!</h3></center>" +
                        "<p>" + xhr.status+ " " + thrownError + "</p><p>" + xhr.responseText + "</p>";
                    app.navigation.popup(message, true);
                    $('#app-popup').popup().popup('open');
                }
            });
        }
    };

    /*
     * Submits the form.
     */
    m.submitForm = function(form_id, onSend){
        var form = document.getElementById(form_id);
        var data = new FormData(form);
        $.ajax({
            url : m.getFormURL(),
            type : 'POST',
            data : data,
            cache : false,
            enctype : 'multipart/form-data',
            processData : false,
            contentType : false,
            success: function(data){
                _log("SEND - form ajax (success):");
                $(document).trigger('app.form.sent.success', [data]);
            },
            error: function (xhr, ajaxOptions, thrownError) {
                _log("SEND - form ajax (ERROR "  + xhr.status+ " " + thrownError +")");
                _log(xhr.responseText);

                $(document).trigger('app.form.sent.error', [xhr, thrownError]);
                //TODO:might be a good idea to add a save option here
            },
            beforeSend: onSend
        });
    };

    /**
     * Returns App main form Path.
     * @returns {*}
     */
    m.getFormURL = function(){
        return Drupal.settings.basePath + app.settings('formPath');
    };

    return m;
}(app.io || {}, jQuery));