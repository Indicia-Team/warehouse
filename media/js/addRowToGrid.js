function addRowToGrid(url, gridId, lookupListId, readAuth, labelTemplate) {
	
  // inner function to handle a selection of a taxon from the autocomplete
  var handleSelectedTaxon = function(event, data) {
    // on picking a result in the autocomplete, ensure we have a spare row
    var label = labelTemplate;
    // replace each field in the label template
    $.each(data, function(field, value) {
      regex = new RegExp('\\{' + field + '\\}', 'g');
      label = label.replace(regex, value===null ? '' : value);
    });
    // clear the event handler
    $(event.target).unbind('result', handleSelectedTaxon);
    var taxonCell=event.target.parentNode;
    $(taxonCell).attr('colspan',1);
    var row=taxonCell.parentNode;
    $(taxonCell).before('<td class="ui-state-default remove-row">X</td>');
    $(taxonCell).html(label);
    // Replace the tags in the row template with the taxa_taxon_list_ID
    row.innerHTML = row.innerHTML.replace(/\{ttlId\}/g, data.id);
	$(row).find('.add-image-link').show();
    // auto-check the row
    var checkbox=$(row).find('.scPresenceCell input');
    checkbox.attr('checked', 'checked');
    // and rename the controls so they post into the right species record
    checkbox.attr('name', 'sc:' + data.id + '::present');
    $(row).find('.remove-row').click(function(e) {
        e.preventDefault();
        // @todo unbind all event handlers
        row = $(e.target.parentNode);
        row.remove();
      });
    // Finally, a blank row is added for the next record
    makeSpareRow(true); 
  };
  
  // Create an inner function for adding blank rows to the bottom of the grid
  var makeSpareRow = function(scroll) {
    // get a copy of the new row template
    var newRow =$('tr#'+gridId + '-scClonableRow').clone(true);
    // build an auto-complete control for selecting the species to add to the bottom of the grid
    selectorId = gridId + '-' + $('#' + gridId +' tbody')[0].childElementCount;
    var speciesSelector = '<input type="text" id="' + selectorId + '" />';
    // put this inside the new row template in place of the species label.
    $(newRow).html($(newRow.html().replace('{content\}', speciesSelector)));
    // add the row to the bottom of the grid
    newRow.appendTo('table#' + gridId +' tbody').removeAttr('id');
  
    // Attach auto-complete code to the input
    ctrl = $('#' + selectorId).autocomplete(url+'/taxa_taxon_list', {
      extraParams : {
        orderby : 'taxon',
        mode : 'json',
        qfield : 'taxon',
        auth_token: readAuth.auth_token,
        nonce: readAuth.nonce,
        taxon_list_id: lookupListId
      },
      parse: function(data) {
        var results = [];
        jQuery.each(data, function(i, item) {
          results[results.length] =
          {
            'data' : item,
            'result' : item.taxon,
            'value' : item.id
          };
        });
        return results;
      },
      formatItem: function(item) {
        return item.taxon;
      }
    });
    ctrl.bind('result', handleSelectedTaxon);
    ctrl.focus();    
    // Check that the new entry control for taxa will remain in view with enough space for the autocomplete drop down
    if (scroll && ctrl.offset().top > $(window).scrollTop() + $(window).height() - 180) {
      var newTop = ctrl.offset().top - $(window).height() + 180;
      // slide the body upwards so the grid entry box remains in view, as does the drop down content on the autocomplete for taxa
      $('html,body').animate({scrollTop: newTop}, 500);       
    }
  };
  
  makeSpareRow(false);
}

$('.add-image-link').live('click', function(evt) {
  evt.preventDefault();
  var ctrlId='file-box-'+evt.target.id;
  ctrlId = ctrlId.replace(/:/g,'-');
  colspan = $($(evt.target).parent().parent()).children().length;
  var imageRow = '<tr><td colspan="' + colspan + '">';
  imageRow += '<div class="file-box" id="' + ctrlId + '"></div>';
  imageRow += '</td></tr>';
  $($(evt.target).parent().parent()).after(imageRow);
  $('#' + ctrlId).uploader({
    caption : 'Files',
	  //id : 'occurrence_image-',
	  //upload : '1',
	  maxFileCount : '4',
	  autoupload : '1',
	  flickr : '',
	  uploadSelectBtnCaption : 'Select file(s)',
	  //flickrSelectBtnCaption : 'Choose photo from Flickr',
	  startUploadBtnCaption : 'Start upload',
	  msgUploadError : 'An error occurred uploading the file.',
	  msgFileTooBig : 'The image file cannot be uploaded because it is larger than the maximum file size allowed.',
	  runtimes : 'html5,silverlight,flash,gears,browserplus,html4',
	  imagewidth : '250',
	  uploadScript : '/bioblitz/sites/all/modules/iform/client_helpers/upload.php',
	  destinationFolder : '/bioblitz/sites/all/modules/iform/client_helpers/upload/',
	  swfAndXapFolder : 'sites/all/modules/iform/client_helpers/plupload/',
	  buttonTemplate : '<div class="indicia-button ui-state-default ui-corner-all" id="{id}"><span>{caption}</span></div>',
	  table : evt.target.id.replace('images','sc') + ':occurrence_image',
	  maxUploadSize : '1048576',
	  jsPath : 'sites/all/modules/iform/media/js/'
	  //codeGenerated : 'all',
	  //label : 'Upload your photos',
	  //tabDiv : 'species'
  });
  $(evt.target).hide();
});