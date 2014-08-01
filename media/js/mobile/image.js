app = app || {};
app.image = (function(m, $){
    m.MAX_IMG_HEIGHT = 800;
    m.MAX_IMG_WIDTH = 800;

    m.setImage = function(input, output){
        var img_holder = 'sample-image-placeholder';
//            var sample_tmpl = '<div id="sample-image">' +
//                '<div id="' + img_holder + '"></div>' +
//                '<div id="sample-image-picker"></div>' +
//                '</div>';
        // var sample_tmpl = '<div id="sample-image-picker"></div>';

        var upload = $(input);
        var holder = $('#' + img_holder);

        if (typeof window.FileReader === 'undefined') {
            return false;
        }

        // upload.before(sample_tmpl);
        //$('#sample-image-picker').append(upload);
        $('#photo').append('<div id="' + img_holder + '"></div>');

        $('#' + img_holder).on('click', function(){
            upload.click();
        });

        upload.change(function (e) {
            e.preventDefault();
            var file = this.files[0];
            var reader = new FileReader();

            reader.onload = function (event) {
                var img = new Image();
                img.src = event.target.result;
                // note: no onload required since we've got the dataurl...I think! :)
                if (img.width > 560) { // holder width
                    img.width = 560;
                }
                $('#sample-image-placeholder').empty().append(img);
                $('#' + img_holder).css('border', '0px');
                //$('#' + img_holder).css('background-color', 'transparent');
                $('#' + img_holder).css('background-image', 'none');
            };
            reader.readAsDataURL(file);

            return false;
        });

    };

    return m;
}(app.image || {}, jQuery));

