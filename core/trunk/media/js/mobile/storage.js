app = app || {};
app.storage = (function(m, $){
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