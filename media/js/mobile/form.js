app = app || {};
app.form = (function(m, $){
    m.MULTIPLE_GROUP_KEY = "multiple_"; //to separate a grouped input
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