app = app || {};
app.storage = (function(m, $){
    m.FORM_COUNT = "form_count";
    m.FORM = "form_";
    m.PIC = "_pic_";

    m.totalFormFiles = 0;

    m.FORMS = "forms";
    m.FORMS_DATA = "data";
    m.FORMS_FILES = "files";
    m.FORMS_SETTINGS = "settings";
    m.FORMS_LASTID = "lastId";

    m.totalFormFiles = 0;

    /**
     *
     * @returns {*}
     */
    m.init = function(){
        var forms = m.get(m.FORMS);
        if (forms == null){
            forms = {};
            forms[m.FORMS_SETTINGS] = {};
            forms[m.FORMS_SETTINGS][m.FORMS_LASTID] = 0;
            m.set(m.FORMS, forms);
            return forms;
        }
        return null;
    };

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

    /**
     *
     * @param item
     */
    m.tmpGet = function(item){
        var data = sessionStorage.getItem(item);
        data = JSON.parse(data);
        return data;
    };

    /**
     *
     * @param item
     */
    m.tmpSet = function(item, data){
        data = JSON.stringify(data);
        return sessionStorage.setItem(item, data);
    };

    /**
     *
     * @param item
     */
    m.tmpRemove = function(item){
        return sessionStorage.removeItem(item);
    };

    /*
     * Saves the form.
     * Returns the savedFormId of the saved form, otherwise an app.ERROR.
     */
    m.saveForm = function(formId, onSuccess){
        _log("FORM.");
        var forms = m.get(m.FORMS);
        if (forms == null) forms = m.init();

        //get new form ID
        var savedFormId = ++forms[m.FORMS_SETTINGS][m.FORMS_LASTID];

        //INPUTS
        var form = $(formId);
        var onSaveAllFilesSuccess = function(files_array){
            //get all the inputs/selects/textboxes into array
            var form_array = m.saveFormData(form);

            //merge files and the rest of the inputs
            form_array = form_array.concat(files_array);

            _log("FORM - saving the form into storage");
            try{
                forms[savedFormId] = form_array;
                m.set(m.FORMS, forms);
            } catch (e){
                _log("FORM - ERROR while saving the form");
                _log(e);
                return app.ERROR;
            }
            if(onSuccess != null){
                onSuccess(savedFormId);
            }
        };

        m.saveAllFiles(form, onSaveAllFilesSuccess);
        return app.TRUE;
    };

    /**
     * Saves all the files in the provided form.
     * @param form
     * @param onSaveAllFilesSuccess
     */
    m.saveAllFiles = function(form, onSaveAllFilesSuccess){
        //init files creation
        var files = [];
        form.find('input').each(function(index, input) {
            if ($(input).attr('type') == "file" && input.files.length > 0) {
                files.push({
                    'file' : $(input).prop("files")[0],
                    'input_field_name' : $(input).attr("name"),
                });
            }
        });

        //recursive calling to save all the images
        saveAllFilesRecursive(files, null);
        function saveAllFilesRecursive(files, files_array){
            files_array = files_array || [];

            //recursive files saving
            if(files.length > 0){
                var file_info = files.pop();
                //get next file in file array
                var file = file_info['file'];
                var value = Date.now() + "_" + file['name'];
                var name = file_info['input_field_name'];

                //recursive saving of the files
                var onSaveSuccess = function(){
                    files_array.push({
                        "name" : name,
                        "value" : value,
                        "type" : 'file'
                    });
                    saveAllFilesRecursive(files, files_array, onSaveSuccess);
                };
                m.saveFile(value, file, onSaveSuccess);
            } else {
                onSaveAllFilesSuccess(files_array);
            }
        }
    };

    /**
     * Transforms and resizes an image file into a string and saves it in the
     * storage.
     * @param key
     * @param file
     * @param onSaveSuccess
     * @returns {number}
     */
    m.saveFile = function(key, file, onSaveSuccess){
        if (file != null) {
            _log("FORM - working with " + file.name);
            //todo: not to hardcode the size
            if (!app.storage.hasSpace(file.size/4)){
                return file_storage_status = app.ERROR;
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
                        onSaveSuccess();
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

    /**
     * Formats all form data (appart from files) into a form_array that it returns.
     * @param form
     * @returns {Array}
     */
    m.saveFormData = function(form) {
        //extract form data
        var form_array = [];
        var name, value, type, id, needed;

        form.find('input').each(function(index, input) {
            name = $(input).attr("name");
            value = $(input).attr('value');
            type = $(input).attr('type');
            id = $(input).attr('id');
            needed = true; //if the input is empty, no need to send it

            switch(type){
                case "checkbox":
                    needed = $(input).is(":checked");
                    break;
                case "text":
                    value = $(input).val();
                    break;
                case "radio":
                    needed = $(input).is(":checked");
                    break;
                case "button":
                case "file":
                    needed = false;
                    //do nothing as the files are all saved
                    break;
                case "hidden":
                    break;
                default:
                    _log("Error, unknown input type: " + type);
                    break;
            }

            if (needed){
                if(value != ""){
                    form_array.push({
                        "name" : name,
                        "value" : value,
                        "type" : type
                    });
                }
            }
        });

        //TEXTAREAS
        form.find('textarea').each(function(index, textarea) {
            name = $(textarea).attr('name');
            value = $(textarea).val();
            type = "textarea";

            if(value != ""){
                form_array.push({
                    "name" : name,
                    "value" : value,
                    "type" : type
                });
            }
        });

        //SELECTS
        form.find("select").each(function(index, select) {
            name = $(select).attr('name');
            value = $(select).find(":selected").val();
            type = "select";

            if(value != ""){
                form_array.push({
                    "name" : name,
                    "value" : value,
                    "type" : type
                });
            }
        });

        return form_array;
    };

    /**
     * Removes a saved form from the storage.
     * @param formStorageId
     */
    m.removeSavedForm = function(formStorageId){
        if(formStorageId == null) return;

        _log("SEND - cleaning up");
        var forms = m.get(m.FORMS);

        //clean files
        var input = {};
        for (var i = 0; i < forms[formStorageId].length; i++){
            input = forms[formStorageId][i];
            if(input['type'] == 'file'){
                app.storage.remove(input['value']);
            }
        }
        //remove form and save
        delete forms[formStorageId];
        app.storage.set(m.FORMS, forms);
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