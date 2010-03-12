/**
 * Indicia, the OPAL Online Recording Toolkit.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses/gpl.html.
 *
 * @package	Media
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * JQuery flickr interaction widget for Indicia.
 */

 (function($) {
  $.extend({
    indiciaFlickr: new function() {

      this.defaults = {
        panelClass: "ui-widget ui-widget-content ui-corner-all flickr-panel",
        headerClass: "",
        photoClass: "ui-corner-all ui-widget-content photo",
        selectedClass: "ui-state-active",
        hoverClass: "ui-state-hover",
        buttonClass: "ui-corner-all ui-state-default indicia-button",
        photoSize: "m",
        largePhotoSize: "b",
        limit: 10,
        imageTableName: 'occurrence_image',
        autoPopulateControls: true,
        assocDate: 'sample:date',
        assocSref: 'sample:entered_sref',
        assocSrefSystem: "sample\:entered_sref_system",
      };

      /**
      * Constructor.
      *
      * param options - associative array of config options. Possible values are:
      * 	panelClass - css class(es) to apply to the panel, space separated.
      * 	headerClass - css class(es) to apply to the header bar, space separated.
      * 	photoClass - css class(es) to apply to the photo box, space separated.
      *		selectedClass - css class(es) applied to the selected photo.
      *		hoverClass - css class(es) applied to the photo or button the mouse is over.
      *		buttonClass - css applied to the buttons.
      *   photoSize - size of photo to display, defaults to m. Possible values are.
      *      s	small square 75x75
      *	     t	thumbnail, 100 on longest side
      *      m	small, 240 on longest side
      *      -	medium, 500 on longest side
      *      b	large, 1024 on longest side (only exists for very large original images)
      *   largePhotoSize - size of photo to display in zoom mode, defaults to b.
      *   limit - Maximum number of photos to return (sorted by date, most recent first).
      *   detailsInputName - name of the hidden control that stores the selected photo information (to be posted with
      *      the form).
      *   autoPopulateControls - true means that when a photo is selected, its attributes will
      *      be used to populate the date and spatial reference controls on the page.
      *   assocDate - id of the associated date control that will be populated.
      *   assocSref - id of the associated spatial reference control that will be populated.
      *   assocSrefSystem - id of the associated spatial reference system control that will be populated.
      */
      this.construct = function(options) {
        this.settings = {};
        // Build the caption names - the user can override these if required but would not be expected.
        $.indiciaFlickr.defaults.pathInputName = $.indiciaFlickr.defaults.imageTableName + ':path';
        $.indiciaFlickr.defaults.captionInputName = $.indiciaFlickr.defaults.imageTableName + ':caption';
        $.indiciaFlickr.defaults.detailsInputName = $.indiciaFlickr.defaults.imageTableName + ':external_details';

        // Extend with defaults and options
        $.extend(this.settings, $.indiciaFlickr.defaults, options);


        var div=this;
        this.proxy_url = 'http://localhost/indicia/client_helpers/flickr_proxy.php';

        // Make the photoSize into something that can be consistently inserted in a url.
        if (this.settings.photoSize=='-') {
          this.settings.photoSize='';
        } else {
          this.settings.photoSize='_' + this.settings.photoSize;
        }
        if (this.settings.largePhotoSize=='-') {
          this.settings.largePhotoSize='';
        } else {
          this.settings.largePhotoSize='_' + this.settings.largePhotoSize;
        }

        var content='<div class="'+this.settings.headerClass+'">';
        content += '<label for="flickr_search" class="auto">Search my photos:</label>';
        content += '<input name="flickr_search" type="text" id="f-search" />';
        content += '<button id="f-dosearch" class="' + this.settings.buttonClass + '">Search</button>';
        content += '<label for="f-set" class="auto">or browse photo set:</label>';
        content += '<select name="f-set" id="f-set"><option value="-">&lt;select photo set&gt;</option></select>';
        content += '</div>';
        content += '<input type="hidden" name="' + this.settings.detailsInputName + '" id="' + this.settings.detailsInputName + '"  />';
        content += '<input type="hidden" name="' + this.settings.captionInputName + '" id="' + this.settings.captionInputName + '"  />';
        content += '<input type="hidden" name="' + this.settings.pathInputName + '" id="' + this.settings.pathInputName + '"  />';
        $(this).html(content);
        // hover effect on search button
        $('#f-dosearch').hover(
          function() { $(this).addClass(div.settings.hoverClass); },
          function() { $(this).removeClass(div.settings.hoverClass); }
        );
        // Search button click runs search
        $('#f-dosearch').click( function() {
          doSearch(div);
          return false;
        });
        // Enter in search box also runs search
        $('#f-search').keypress(function (e) {
          if (e.which == 13) {
            doSearch(div);
          }
        });
        // Select a photo set loads it
        $('#f-set').change(function() {
            selectSet(div);
          }
        );

        $.get(this.proxy_url, {
            "method": 'flickr.photosets.getList',
            "arguments": '{}'
          },
          function(response) {
            // convert JSON text to an object
            response=eval("(" + response + ")");
            $.each(response.photosets.photoset, function(photoset_id) {
              // this refers to the photoset in the each loop.
              $('select#f-set').append('<option value="'+this.id+'">'+this.title+'</option>');
            });
          }
        );

        $(this).addClass(this.settings.panelClass);
      };

      /**
       * Perform a search against the Flickr API
       */
      function doSearch(div) {
        var searchText = $('#f-search').attr('value');
        makeRequest(div, 'photos', {
          "method": 'flickr.photos.search',
          "arguments": '{"user_id":"me","extras":"date_taken,geo","text":"' + searchText +
               '","per_page":"'+div.settings.limit+'"}'
        });
      };


      /**
       * Get the contents of a selected photo set.
       */
      function selectSet(div) {
        var setId = $('#f-set').attr('value');
        if (setId!='-') {
          makeRequest(div, 'photoset', {
            "method": 'flickr.photosets.getPhotos',
            "arguments": '{"photoset_id":"' + setId + '","extras":"date_taken,geo","per_page":"'+div.settings.limit+'"}'
          });
        } else {
          clear();
        }
      };


      /**
       * Perform a generic request against the Flickr API to get a list of photos.
       */
      function makeRequest(div, setName, command) {
        // Send a search request
        $.get(div.proxy_url, command,
          function(response) {
            clear(div);
            var json;
            // convert JSON text to an object
            response=eval("(" + response + ")");
            $.each(response[setName].photo, function(photo_id) {
              // this refers to the photo in the each loop. The div includes an anchored image, with fancybox applied,
              // plus a caption, date and select button. In addition, a hidden input contains the photo's data to use when
              // it is selected.
              $(div).append('<div class="f-photo ' + div.settings.photoClass+'"><a class="fancybox" href="http://farm'+
                    this.farm+'.static.flickr.com/'+this.server+'/'+ this.id+'_'+this.secret+div.settings.largePhotoSize + '.jpg">' +
                    '<img alt="'+this.title+'" src="http://farm'+this.farm+'.static.flickr.com/'+this.server+'/'+
                    this.id+'_'+this.secret+div.settings.photoSize+'.jpg" /></a><p>'+this.title+'<br/>' + this.datetaken + '</p>'+
                    '<button class="f-select ' + div.settings.buttonClass + '">Select</button>'+
                    '<input type="hidden" />'+
                    '</div>');
              hidden='{"flickr":{"title":"'+this.title+'","farm":"'+this.farm+'","server":"'+this.server+
                    '","id":"'+this.id+'","secret":"'+this.secret+'"}}|'+
                    this.datetaken+'|'+this.latitude+'|'+this.longitude
              $('div.f-photo input').attr('value',hidden);
            });
            $('div.f-photo button').hover(
              function() { $(this).addClass(div.settings.hoverClass); },
              function() { $(this).removeClass(div.settings.hoverClass); }
            );
            jQuery('a.fancybox').fancybox();
            // Click event handler for selection of a photo
            $('button.f-select').click(
              function() {
                try {
                  // grab the photo data, from the input that is a sibling of this button
                  var data=$(this).siblings('input').attr('value').split('|');
                  var photo=data[0]; // this is the JSON snippet describing the photo
                  obj=eval("(" + photo + ")");

                  $('#' + div.settings.detailsInputName.replace(':','\\:')).attr('value', photo);
                  $('#' + div.settings.captionInputName.replace(':','\\:')).attr('value', obj.flickr.title);
                  $('#' + div.settings.pathInputName.replace(':','\\:')).attr('value', 'http://farm'+obj.flickr.farm+
                      '.static.flickr.com/'+obj.flickr.server+'/'+ obj.flickr.id+'_'+obj.flickr.secret+'.jpg');
                  if (div.settings.autoPopulateControls) {
                    $('#' + div.settings.assocDate.replace(':','\\:')).attr('value', data[1].substr(0,10)); // this is the date token
                    $('#' + div.settings.assocSref.replace(':','\\:')).attr('value', data[2]+', '+data[3]); // this is the lat and long token
                    $('#' + div.settings.assocSrefSystem.replace(':','\\:')).attr('value', '4326'); // this is the lat and long token
                    $('#' + div.settings.assocSref.replace(':','\\:')).change();
                  }

                  clear(div);
                  // Put a thumbnail for the selected image into the div.
                  $(div).append('<a class="f-photo fancybox" href="http://farm'+
                      obj.flickr.farm+'.static.flickr.com/'+obj.flickr.server+'/'+ obj.flickr.id+'_'+obj.flickr.secret+
                      div.settings.largePhotoSize+'.jpg">' +
                      '<img alt="'+obj.flickr.title+'" src="http://farm'+obj.flickr.farm+'.static.flickr.com/'+obj.flickr.server+'/'+
                      obj.flickr.id+'_'+obj.flickr.secret + '_t.jpg" />'+
                      '</div>');
                  jQuery('a.fancybox').fancybox();
                } finally {
                  return false;
                }
              }
            );
          }
        );
      };


      /**
      * Reset the contents of the div.
      */
      function clear(div) {
        $('.f-photo').remove();
        $('input#'+div.settings.detailsInputName).attr('value', '');
        $('select#f-set').attr('value', '-');
      };

    }
  });

 /**
  * Extend the function object.
  */
  $.fn.extend({
    indiciaFlickr: $.indiciaFlickr.construct
  });

})(jQuery);
