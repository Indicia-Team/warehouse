app = app || {};
app.storage = (function(m, $){
    m.FORM_COUNT_KEY = "form_count";
    m.FORM_KEY = "form_";
    m.PIC_KEY = "_pic_";

    m.hasSpace = function(size){
        return localStorageHasSpace(size);
    };

    /**
     *
     * @param item
     */
    m.get = function(item){
        var data = localStorage.getItem(item);
        data = JSON.parse(data);
        return data;
    };

    /**
     *
     * @param item
     */
    m.set = function(item, data){
        data = JSON.stringify(data);
        return localStorage.setItem(item, data);
    };

    /**
     *
     * @param item
     */
    m.remove = function(item){
        return localStorage.removeItem(item);
    };

    /*
     * Saves the form
     * Returns 1 if save is successful, else 0 if error.
     */
    m.saveForm = function() {
        _log("FORM.");
        var input_array = [];
        var input_key = {};
        var name, value, type, id, needed;

        //INPUTS
        //TODO: add support for all input cases; use switch
        //TODO: do not hardcode the form's name
        var pic_count = 0;
        var file_storage_status = 1; //if localStorage has little space it becomes 0
        var form = $('form');
        form.find('input').each(function(index, input) {
            name = $(input).attr("name");
            value = $(input).attr('value');
            type = $(input).attr('type');
            id = $(input).attr('id');
            needed = true; //if the input is empty, no need to send it

            if ($(input).attr('type') == "checkbox") {
                //checkbox
                if(!$(input).is(":checked"))
                    needed = false;
            } else if (type == "text"){
                //text
                value = $(input).val();

            } else if (type == "radio"){
                //radio
                if(!$(input).is(":checked"))
                    needed = false;
            } else if (type == "file" && id != null) {
                //file
                var key = Date.now() + "_" + $(input).val().replace("C:\\fakepath\\", "");
                value = key;
                var file = $(input).prop("files")[0];
                app.storage.saveFile(key, file);
            }
            if (needed){
                input_array.push({
                    "name" : name,
                    "value" : value,
                    "type" : type
                });
            }
        });

        //return if unsuccessful file saving
        if (file_storage_status == 0){
            return 0;
        }

        //TEXTAREAS
        form.find('textarea').each(function(index, textarea) {
            name = $(textarea).attr('name');
            value = $(textarea).val();
            type = "textarea";

            input_array.push({
                "name" : name,
                "value" : value,
                "type" : type
            });
        });


        //SELECTS
        form.find("select").each(function(index, select) {
            name = $(select).attr('name');
            value = $(select).find(":selected").val();
            type = "select";

            input_array.push({
                "name" : name,
                "value" : value,
                "type" : type
            });
        });

        //form counter
        var form_count = this.get(this.FORM_COUNT_KEY);
        if (form_count != null) {
            _log("FORM - incrementing form counter");
            this.set(this.FORM_COUNT_KEY, ++form_count);
        } else {
            _log("FORM - setting up form counter for the first time");
            form_count = 1;
            this.set(this.FORM_COUNT_KEY, form_count);
        }

        input_array_string = input_array;
        _log("FORM - saving the form into storage");
        try{
            this.set(this.FORM_KEY + form_count, input_array_string);
        } catch (e){
            _log("FORM - ERROR while saving the form");
            _log(e);
            return 0;
        }

        return 1;
    };

    m.saveFile = function(key, file){
        if (file != null) {
            _log("FORM - working with " + file.name);
            if (!app.storage.hasSpace(file.size)){
                return file_storage_status = 0;
            }

            var reader = new FileReader();
            //#2
            reader.onload = function() {
                _log("FORM - resizing file");
                var image = new Image();
                //#4
                image.onload = function(e){
                    var width = image.width;
                    var height = image.height;

                    //resizing
                    var res;
                    if (width > height){
                        res = width / app.image.MAX_IMG_WIDTH;
                    } else {
                        res = height / app.image.MAX_IMG_HEIGHT;
                    }

                    width = width / res;
                    height = height / res;

                    var canvas = document.createElement('canvas');
                    canvas.width = width;
                    canvas.height = height;

                    var imgContext = canvas.getContext('2d');
                    imgContext.drawImage(image, 0, 0, width, height);

                    var shrinked = canvas.toDataURL(file.type);
                    try {
                        _log("FORM - saving file in storage ("
                            + (shrinked.length / 1024) + "KB)" );

                        app.storage.set(key,  shrinked); //stores the image to localStorage
                    }
                    catch (e) {
                        _log("FORM - saving file in storage failed: " + e);
                    }
                };
                //#3
                image.src = reader.result;
            };
            //1#
            reader.readAsDataURL(file);
        }
    };



    /*
     * Checks if it is possible to store some sized data in localStorage.
     */
    function localStorageHasSpace (size){
        var taken = JSON.stringify(localStorage).length;
        var left = 1024 * 1024 * 5 - taken;
        if ((left - size) > 0)
            return 1;
        else
            return 0;
    }

    return m;
}(app.storage || {}, jQuery));

/*##############
 ## HELPER  ####
 ##############*/

/*
 * Converts DataURI object to a Blob
 * @param {type} form_count
 * @param {type} pic_count
 * @param {type} file
 * @returns {undefined}
 */
function dataURItoBlob (dataURI, file_type) {
    var binary = atob(dataURI.split(',')[1]);
    var array = [];
    for (var i = 0; i < binary.length; i++) {
        array.push(binary.charCodeAt(i));
    }
    return new Blob([new Uint8Array(array)], {
        type : file_type
    });
}