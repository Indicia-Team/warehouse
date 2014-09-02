app = app || {};
app.form = (function(m, $){
    m.MULTIPLE_GROUP_KEY = "multiple_"; //to separate a grouped input

    m.INPUT_KEYS = {
        'APPNAME_KEY' : 'appname',
        'APPSECRET_KEY' : 'appsecret',
        'WEBSITEID_KEY' : 'website_id',
        'SURVEYID_KEY' : 'survey_id',
        'SREF_KEY' : 'sample:entered_sref',
        'TAXON_KEY' : 'occurrence:taxa_taxon_list_id',
        'DATE_KEY' : 'sample:date'
    };

    m.COUNT = "form_count";
    m.STORAGE = "form_";
    m.PIC = "_pic_";

    m.totalFiles = 0;

    m.FORMS = "forms";
    m.DATA = "data";
    m.FILES = "files";
    m.SETTINGS = "formSettings";
    m.LASTID = "lastId";

    /**
     *
     * @returns {*}
     */
    m.init = function(){
        var settings = m.getSettings();
        if (settings == null){
            settings = {};
            settings[m.LASTID] = 0;
            m.setSettings(settings);
            return settings;
        }
        return null;
    };

    m.setSettings = function(settings){
        app.storage.set(m.SETTINGS, settings);
    };

    m.initSettings = function(){
        var settings = {};
        settings[m.LASTID] = 0;
        m.setSettings(settings);
        return settings;
    };

    m.getSettings = function(){
        var settings = app.storage.get(m.SETTINGS) || m.initSettings();
        return settings;
    };

    m.setForms = function(forms){
        app.storage.set(m.FORMS, forms);
    };

    /*
     * Saves the form.
     * Returns the savedFormId of the saved form, otherwise an app.ERROR.
     */
    m.save = function(formId, onSuccess){
        _log("FORM.");
        var forms = this.getAllSaved();

        //get new form ID
        var settings = m.getSettings();
        var savedFormId = ++settings[m.LASTID];

        //INPUTS
        var form = $(formId);
        var onSaveAllFilesSuccess = function(files_array){
            //get all the inputs/selects/textboxes into array
            var form_array = m.saveData(form);

            //merge files and the rest of the inputs
            form_array = form_array.concat(files_array);

            _log("FORM - saving the form into storage");
            try{
                forms[savedFormId] = form_array;
                m.setForms(forms);
                m.setSettings(settings);
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
                    'input_field_name' : $(input).attr("name")
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
    m.saveData = function(form) {
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

    m.getAllSaved = function(){
        return app.storage.get(m.FORMS) || {};
    };

    m.getSaved = function(formStorageId){
        var forms = this.getAllSaved();
        return forms[formStorageId];
    };

    m.getSavedData = function(formStorageId){
        var data = new FormData();

        //Extract data from storage
        var savedForm = m.getSaved(formStorageId);
        for (var k = 0; k < savedForm.length; k++) {
            if (savedForm[k].type == "file") {
                var pic_file = app.storage.get(savedForm[k].value);
                if (pic_file != null) {
                    _log("SEND - attaching '" + savedForm[k].value + "' to " + savedForm[k].name);
                    var type = pic_file.split(";")[0].split(":")[1];
                    var extension = type.split("/")[1];
                    data.append(savedForm[k].name, dataURItoBlob(pic_file, type), "pic." + extension);
                } else {
                    _log("SEND - " + savedForm[k].value + " is " + pic_file);
                }
            } else {
                var name = savedForm[k].name;
                var value = savedForm[k].value;
                data.append(name, value);
            }
        }
        return data;
    };

    /**
     * Removes a saved form from the storage.
     * @param formStorageId
     */
    m.removeSaved = function(formStorageId){
        if(formStorageId == null) return;

        _log("SEND - cleaning up");
        var forms = m.getAllSaved();

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
     * Form validation.
     * Note: uses old jQuery
     */
    m.validate = function($){
        var invalids = [];

        var tabinputs = $('#entry_form').find('input,select,textarea').not(':disabled,[name=],.scTaxonCell,.inactive');
        var tabtaxoninputs = $('#entry_form .scTaxonCell').find('input,select').not(':disabled');
        if (tabinputs.length>0){
            tabinputs.each(function(index){
                if (!$(this).valid()){
                    var found = false;

                    //this is necessary to check if there was an input with
                    //the same name in the invalids array, if found it means
                    //this new invalid input belongs to the same group and should
                    //be ignored.
                    for (var i = 0; i < invalids.length; i++){
                        if (invalids[i].name == (app.form.MULTIPLE_GROUP_KEY + this.name)){
                            found = true;
                            break;
                        } if (invalids[i].name == this.name) {
                            var new_id = (this.id).substr(0, this.id.lastIndexOf(':'));
                            invalids[i].name = app.form.MULTIPLE_GROUP_KEY + this.name;
                            invalids[i].id = new_id;
                            found = true;
                            break;
                        }
                    }
                    //save the input as a invalid
                    if (!found)
                        invalids.push({ "name" :this.name, "id" : this.id });
                }
            });
        }

        if (tabtaxoninputs.length>0) {
            tabtaxoninputs.each(function(index){
                invalids.push({ "name" :this.name, "id" : this.id });
            });
        }

        //constructing a response about invalid fields to the user
        if (invalids.length > 0){
            return invalids;
        }
        return [];
    };

    return m;
}(app.form || {}, app.$ || jQuery));