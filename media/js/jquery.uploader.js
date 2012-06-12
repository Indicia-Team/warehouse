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
 */

 /**
 * Form submit handler that prevents the user clicking save during an upload
 */
var checkSubmitInProgress = function () {
  if ($('.file-box .progress').length!==0) {
    alert('Please wait till your images have finished uploading before submitting the form.');
    return false;
  }
  return true;
};

 
/**
* Class: uploader
* A jQuery plugin that provides an upload box for multiple images.
*/


(function($) {
  $.fn.uploader = function(options) {
    // Extend our default options with those provided, basing this on an empty object
    // so the defaults don't get changed.
    var opts = $.extend({}, $.fn.uploader.defaults, options), html5OK=false;
    
    if (/Firefox[\/\s](\d+\.\d+)/.test(navigator.userAgent)){ //test for Firefox/x.x or Firefox x.x (ignoring remaining digits);
      var ffversion=new Number(RegExp.$1); // capture x.x portion and store as a number
      if (ffversion>=3.5) {
        // Browser is not FF3.5+, so Html5 is a good runtime as HTML5 resize only works on FF3.5+. 
        html5OK = true;
      }
    } 
    if (!html5OK) {
      // browser not FF3.5+. This replacement does not remove html5 if already the last runtime. 
      opts.runtimes = opts.runtimes.replace('html5,','');
    }
    
    if (typeof opts.jsPath == "undefined") {
      alert('The file_box control requires a jsPath setting to operate correctly. It should point to the URL '+
          'path of the media/js folder.');
    }
    return this.each(function() {
      var uploadSelectBtn='', flickrSelectBtn='', uploadStartBtn='', id=Math.floor((Math.random())*0x10000);
      this.settings = opts;
      if (this.settings.upload) {
        uploadSelectBtn = this.settings.buttonTemplate
            .replace('{caption}', this.settings.uploadSelectBtnCaption)
            .replace('{id}', 'upload-select-btn-' + id);
        if (!this.settings.autoupload) {
          uploadStartBtn = this.settings.buttonTemplate
              .replace('{caption}', this.settings.uploadStartBtnCaption)
              .replace('{id}', 'upload-start-btn-' + id);
        }
      }
      if (this.settings.flickr) {
        flickrSelectBtn = this.settings.buttonTemplate
            .replace('{caption}', this.settings.flickrSelectBtnCaption)
            .replace('{id}', 'flickr-select-btn-' + id);
      }
      $(this).append(this.settings.file_boxTemplate
          .replace('{caption}', this.settings.caption)
          .replace('{uploadSelectBtn}', uploadSelectBtn)
          .replace('{flickrSelectBtn}', flickrSelectBtn)
          .replace('{uploadStartBtn}', uploadStartBtn)
      );
      // Set up a resize object if required
      var resize = (this.settings.resizeWidth!==0 || this.settings.resizeHeight!==0) ?
          { width: this.settings.resizeWidth, height: this.settings.resizeHeight, quality: this.settings.resizeQuality } : null;
      this.uploader = new plupload.Uploader({
        runtimes : this.settings.runtimes,
        container : this.id,
        browse_button : 'upload-select-btn-'+id,
        url : this.settings.uploadScript,
        resize : resize,
        flash_swf_url : this.settings.swfAndXapFolder + 'plupload.flash.swf',
        silverlight_xap_url : this.settings.swfAndXapFolder + 'plupload.silverlight.xap',
        filters : [
          {title : "Image files", extensions : "jpg,gif,png,jpeg"}
        ],
        chunk_size: '1MB',
        // limit the max file size to the Indicia limit, unless it is first resized.
        max_file_size : resize ? '10mb' : plupload.formatSize(this.settings.maxUploadSize)
      });
      
      if (this.settings.autoupload) {
        this.uploader.bind('QueueChanged', function(up) {
          up.start();
        });
      }
      // make the main object accessible
      var div = this;
      
      // load the existing data if there is any
      var existing, uniqueId;
      $.each(div.settings.existingFiles, function(i, file) {
        uniqueId = file.path.split('.')[0];
        uniqueId = uniqueId.replace(/[^a-zA-Z0-9]+/g,'');
        existing = div.settings.file_box_initial_file_infoTemplate.replace(/\{id\}/g, uniqueId)
            .replace(/\{filename\}/g, div.settings.msgExistingImage)
            .replace(/\{filesize\}/g, '')
            .replace(/\{imagewidth\}/g, div.settings.imageWidth);
        $('#' + div.id.replace(/:/g,'\\:') + ' .filelist').append(existing);
        $('#' + uniqueId + ' .progress').remove();
        if (div.settings.finalImageFolderThumbs===undefined) {
          // default thumbnail location if Indicia in charge of images
          var thumbnailfilepath = div.settings.finalImageFolder + 'med-' + file.path;
        } else {
          // overridden thumbnail location
          var thumbnailfilepath = div.settings.finalImageFolderThumbs + file.path;
        }
        var origfilepath = div.settings.finalImageFolder + file.path;
        $('#' + uniqueId + ' .photo-wrapper').append(div.settings.file_box_uploaded_imageTemplate
              .replace(/\{id\}/g, uniqueId)
              .replace(/\{thumbnailfilepath\}/g, thumbnailfilepath)
              .replace(/\{origfilepath\}/g, origfilepath)
              .replace(/\{imagewidth\}/g, div.settings.imageWidth)
              .replace(/\{captionField\}/g, div.settings.table + ':caption:' + uniqueId)
              .replace(/\{captionValue\}/g, file.caption.replace(/\"/g, '&quot;'))
              .replace(/\{pathField\}/g, div.settings.table + ':path:' + uniqueId)
              .replace(/\{pathValue\}/g, file.path)
              .replace(/\{deletedField\}/g, div.settings.table + ':deleted:' + uniqueId)
              .replace(/\{deletedValue\}/g, 'f')
              .replace(/\{isNewField\}/g, 'isNew-' + uniqueId)
              .replace(/\{isNewValue\}/g, 'f')
              .replace(/\{idField\}/g, div.settings.table + ':id:' + uniqueId) 
              .replace(/\{idValue\}/g, file.id) // If ID is set, the picture is uploaded to the server
        );
      });
      
      // Add a box to indicate a file that is added to the list to upload, but not yet uploaded.
      this.uploader.bind('FilesAdded', function(up, files) {
        $(div).parents('form').bind('submit', checkSubmitInProgress);
        // Find any files over the upload limit
        var existingCount = $('#' + div.id.replace(/:/g,'\\:') + ' .filelist').children().length;
        extras = files.splice(div.settings.maxFileCount - existingCount, 9999);
        if (extras.length!==0) {
          alert(div.settings.msgTooManyFiles.replace('[0]', div.settings.maxFileCount));
        }
        $.each(files, function(i, file) {
          $('#' + div.id.replace(/:/g,'\\:') + ' .filelist').append(div.settings.file_box_initial_file_infoTemplate.replace(/\{id\}/g, file.id)
              .replace(/\{filename\}/g, div.settings.msgNewImage)
              .replace(/\{filesize\}/g, plupload.formatSize(file.size))
              .replace(/\{imagewidth\}/g, div.settings.imageWidth)
          );
          // change the file name to be unique
          file.name=plupload.guid() + '.jpg';
          $('#' + file.id + ' .progress-bar').progressbar ({value: 0});
          var msg='Resizing...';
          if (div.settings.resizeWidth===0 || div.settings.resizeHeight===0 || typeof div.uploader.features.jpgresize == "undefined") {
            msg='Uploading...';
          }
          var mediaPath = div.settings.jsPath.substr(0, div.settings.jsPath.length - 3);
          $('#' + file.id + ' .progress-gif').html('<img style="display: inline; margin: 4px;" src="'+ mediaPath +'images/ajax-loader.gif" width="300" height="16" alt="In progress"/>');
          $('#' + file.id + ' .progress-percent').html('<span>'+msg+'</span>');
        });
        
      });
      
      // As a file uploads, update the progress bar and percentage indicator
      this.uploader.bind('UploadProgress', function(up, file) {
        $('#' + file.id + ' .progress-bar').progressbar ('option', 'value', file.percent);
        $('#' + file.id + ' .progress-percent').html('<span>' + file.percent + '% Uploaded...</span>');
      });
      
      this.uploader.bind('Error', function(up, error) {
        if (error.code==-600) {
          alert(div.settings.msgFileTooBig);
        } else {
          alert(error.message);
        }
        $('#' + error.file.id).remove();
      });
      
      // On upload completion, check for errors, and show the uploaded file if OK.
      this.uploader.bind('FileUploaded', function(uploader, file, response) {
        $('#' + file.id + ' .progress').remove();
        // check the JSON for errors
        var resp = eval('['+response.response+']');
        if (resp[0].error) {
          $('#' + file.id).remove();
          alert(div.settings.msgUploadError + ' ' + resp[0].error.message);
        } else {
          var filepath = div.settings.destinationFolder + file.name;
          // Show the uploaded file, and also set the mini-form values to contain the file details.
          $('#' + file.id + ' .photo-wrapper').append(div.settings.file_box_uploaded_imageTemplate
                .replace(/\{id\}/g, file.id)
                .replace(/\{thumbnailfilepath\}/g, filepath)
                .replace(/\{origfilepath\}/g, filepath)
                .replace(/\{imagewidth\}/g, div.settings.imageWidth)
                .replace(/\{captionField\}/g, div.settings.table + ':caption:' + file.id)
                .replace(/\{captionValue\}/g, '')
                .replace(/\{pathField\}/g, div.settings.table + ':path:' + file.id)
                .replace(/\{pathValue\}/g, '')
                .replace(/\{deletedField\}/g, div.settings.table + ':deleted:' + file.id)
                .replace(/\{deletedValue\}/g, 'f')
                .replace(/\{isNewField\}/g, 'isNew-' + file.id)
                .replace(/\{isNewValue\}/g, 't')
                .replace(/\{idField\}/g, div.settings.table + ':id:' + file.id) 
                .replace(/\{idValue\}/g, '') // Set ID to blank, as this is a new record.
          );
          // Copy the path into the hidden path input. Watch colon escaping for jQuery selectors.
          $('#' + div.settings.table.replace(/:/g,'\\:') + '\\:path\\:' + file.id).val(file.name);
        }
        // reset the form handler if this is the last upload in progress
        if ($('.file-box .progress').length===0) {
          $("form").unbind('submit', checkSubmitInProgress);
        }
      });
      
      this.uploader.init();
      
      if (this.settings.useFancybox) {
        // Hack to get fancybox working as a jQuery live, because some of our images load from AJAX calls. 
        // So we temporarily create a dummy link to our image and click it.
        $('a.fancybox').live('click', function() {
          jQuery("body").after('<a id="link_fancybox" style="display: hidden;" href="'+jQuery(this).attr('href')+'"></a>');
          jQuery('#link_fancybox').fancybox(); 
          jQuery('#link_fancybox').click();
          jQuery('#link_fancybox').remove();
          return false;
        });
      }
      
      $('#upload-start-btn-' + id).click(function(e) {
        div.uploader.start();
        e.preventDefault();
      });
      
      $('.delete-file').live('click', function(evt) {
        // if this is a newly uploaded file or still uploading, we can simply delete the div since all that has been done is an upload to the 
        // temp upload folder, which will get purged anyway. isNewField is a hidden input that marks up new and existing files.
        var id=evt.target.id.substr(4);
        if ($('#isNew-'+id).length===0 || $('#isNew-'+id).val()==='t')
          $(evt.target).parents('#'+id).remove();
        else {
          $(evt.target).parents('#'+id).addClass('disabled').css('opacity', 0.5);
          $(evt.target).parents('#'+id).find('.deleted-value').val('t');
          $(evt.target).parents('#'+id+' .progress').remove();
        }
      });
    });
  };
})(jQuery);

/**
 * Main default options for the uploader
 */
$.fn.uploader.defaults = {
  caption : "Files",
  uploadSelectBtnCaption : 'Add File(s)',
  flickrSelectBtnCaption : 'Select photo on Flickr',
  uploadStartBtnCaption : 'Start Upload',
  useFancybox: true,
  imageWidth: 200,
  resizeWidth: 0,
  resizeHeight: 0,
  resizeQuality: 90,
  upload : true,
  flickr : true,
  autoupload : true,
  maxFileCount : 4,
  existingFiles : [],
  buttonTemplate : '<div class="indicia-button ui-state-default ui-corner-all" id="{id}"><span>{caption}</span></div>',
  file_boxTemplate : '<fieldset class="ui-corner-all">\n<legend>{caption}</legend>\n{uploadSelectBtn}\n{flickrSelectBtn}\n<div class="filelist"></div>' +
                 '{uploadStartBtn}</fieldset>',
  file_box_initial_file_infoTemplate : '<div id="{id}" class="ui-widget-content ui-corner-all photo"><div class="ui-widget-header ui-corner-all"><span>{filename} ({filesize})</span> ' +
          '<span class="delete-file ui-state-default ui-widget-content ui-corner-all ui-helper-clearfix" id="del-{id}">X</span></div><div class="progress"><div class="progress-bar" style="width: {imagewidth}px"></div>'+
          '<div class="progress-percent"></div><div class="progress-gif"></div></div><span class="photo-wrapper"></span></div>',
  file_box_uploaded_imageTemplate : '<a class="fancybox" href="{origfilepath}"><img src="{thumbnailfilepath}" width="{imagewidth}"/></a>' +
      '<input type="hidden" name="{idField}" id="{idField}" value="{idValue}" />' +
      '<input type="hidden" name="{pathField}" id="{pathField}" value="{pathValue}" />' +
      '<input type="hidden" name="{deletedField}" id="{deletedField}" value="{deletedValue}" class="deleted-value" />' +
      '<input type="hidden" id="{isNewField}" value="{isNewValue}" />' +
      '<label for="{captionField}">Caption:</label><br/><input type="text" maxlength="100" style="width: {imagewidth}px" name="{captionField}" id="{captionField}" value="{captionValue}"/>',
  msgUploadError : 'An error occurred uploading the file.',
  msgFileTooBig : 'The file is too big to upload. Please resize it then try again.',
  msgTooManyFiles : 'Only [0] files can be uploaded.',
  msgNewImage : 'New image',
  msgExistingImage : 'Existing image',
  uploadScript : 'upload.php',
  destinationFolder : '',
  swfAndXapFolder : '',
  runtimes : 'gears,silverlight,browserplus,html5,flash,html4'
};