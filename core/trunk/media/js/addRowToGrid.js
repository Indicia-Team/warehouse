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
 * Helper methods for additional JavaScript functionality required by the species_checklist control.
 */
 
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
    $(taxonCell).before('<td class="ui-state-default remove-row" style="width: 1%">X</td>');
    $(taxonCell).parent().addClass('added-row');
    $(taxonCell).html(label);
    // Replace the tags in the row template with the taxa_taxon_list_ID
    $.each($(row).children(), function(i, cell) {
      cell.innerHTML = cell.innerHTML.replace(/-ttlId-/g, data.id);
    });    
    $(row).find('.add-image-select-species').hide();
    $(row).find('.add-image-link').show();    
    // auto-check the row
    var checkbox=$(row).find('.scPresenceCell input');
    checkbox.attr('checked', 'checked');
    // and rename the controls so they post into the right species record
    checkbox.attr('name', 'sc:' + data.id + '::present');    
    // Finally, a blank row is added for the next record
    makeSpareRow(true); 
  };
  
  // Create an inner function for adding blank rows to the bottom of the grid
  var makeSpareRow = function(scroll) {
    // get a copy of the new row template
    var newRow =$('tr#'+gridId + '-scClonableRow').clone(true);
    // build an auto-complete control for selecting the species to add to the bottom of the grid. 
    // The next line gets a unique id for the autocomplete.
    selectorId = gridId + '-' + $('#' + gridId +' tbody')[0].childElementCount;
    var speciesSelector = '<input type="text" id="' + selectorId + '" />';
    // put this inside the new row template in place of the species label.
    $(newRow).html($(newRow.html().replace('{content}', speciesSelector)));
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

$('.remove-row').live('click', function(e) {
  e.preventDefault();
  // @todo unbind all event handlers
  var row = $(e.target.parentNode);
  if (row.next().find('.file-box').length>0) {
    row.next().remove();
  }
  if (row.hasClass('added-row')) {
    row.remove();
  } else {
    // This was a pre-existing occurrence so we can't just delete the row from the grid.
    row.css('opacity',0.25);
    row.attr('disabled','disabled');  
    // Append an input marking this occurrence as deleted to the table
    row.find('.scPresence').attr('checked',false);
  }
});

/**
 * Click handler for the add image link that is displayed alongside each occurrence row in the grid once 
 * it has been linked to a taxon. Adds a row to the grid specifically to contain a file uploader for images
 * linked to that occurrence.
 * @todo Check why flash and silverlight are not working in the grid, and re-instate in the runtimes if possible
 */
$('.add-image-link').live('click', function(evt) {
  evt.preventDefault();
  var table = evt.target.id.replace('add-images','sc') + ':occurrence_image';
  var ctrlId='container-'+table;
  var colspan = $($(evt.target).parent().parent()).children().length;
  var imageRow = '<tr class="image-row"><td colspan="' + colspan + '">';
  imageRow += '<div class="file-box" id="' + ctrlId + '"></div>';
  imageRow += '</td></tr>';
  imageRow = $(imageRow);
  $($(evt.target).parent().parent()).after(imageRow);
  imageRow.find('div').uploader({
    caption : 'Files',
    maxFileCount : '3',
    autoupload : '1',
    flickr : '',
    uploadSelectBtnCaption : 'Select file(s)',
    startUploadBtnCaption : 'Start upload',
    msgUploadError : 'An error occurred uploading the file.',
    msgFileTooBig : 'The image file cannot be uploaded because it is larger than the maximum file size allowed.',
    runtimes : 'html5,gears,browserplus,html4',
    imagewidth : '250',
    uploadScript : uploadScript,
    destinationFolder : destinationFolder,
    swfAndXapFolder : swfAndXapFolder,
    jsPath : jsPath,
    buttonTemplate : '<div class="indicia-button ui-state-default ui-corner-all" id="{id}"><span>{caption}</span></div>',
    table : table,
    maxUploadSize : '4M'    
  });
  $(evt.target).hide();
});

$('.hide-image-link').live('click', function(evt) {
  evt.preventDefault();
  var ctrlId=(evt.target.id.replace(/^hide\-images/, 'container-sc') + ':occurrence_image').replace(/:/g, '\\:');
  if ($(evt.target).hasClass('images-hidden')) {
    $('#'+ctrlId).show();
    $(evt.target).removeClass('images-hidden');
    $(evt.target).html('hide images');
  } else {
    $('#'+ctrlId).hide();
    $(evt.target).addClass('images-hidden');
    $(evt.target).html('show images');
  }
});