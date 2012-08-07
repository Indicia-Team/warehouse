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
 * formatter - The taxon label template, OR a JavaScript function that takes an item returned by the web service 
 * search for a species when adding rows to the grid, and returns a formatted taxon label. Overrides the label 
 * template and controls the appearance of the species name both in the autocomplete for adding new rows, plus for 
  the newly added rows.
 */

var selectVal = null, cacheLookup=false;

/**
 * A keyboard event handler for the grid.
 */
function keyHandler(evt) {
  var rows, row, rowIndex, cells, cell, cellIndex, caretPos, ctrl = this, deltaX = 0, deltaY = 0,
    isTextbox=this.nodeName.toLowerCase() === 'input' && $(this).attr('type') === 'text',
    isSelect = this.nodeName.toLowerCase() === 'select';
  if ((evt.keyCode >= 37 && evt.keyCode <= 40) || evt.keyCode === 9) {
    rows = $(this).parents('tbody').children();
    row = $(this).parents('tr')[0];
    rowIndex = rows.index(row);
    cells = $(this).parents('tr').children();
    cell = $(this).parents('td')[0];
    cellIndex = cells.index(cell);
    if (isTextbox) {
      if (typeof this.selectionStart !== 'undefined') {
        caretPos = this.selectionStart;
      } else {  // Internet Explorer before version 9
        var inputRange = this.createTextRange();
        // Move selection start to 0 position
        inputRange.moveStart('character', -this.value.length);
        // The caret position is selection length
        caretPos = inputRange.text.length;
      }
    }
  }
  switch (evt.keyCode) {
    case 9: 
      // tab direction depends on shift key and occurs irrespective of caret
      deltaX = evt.shiftKey ? -1 : 1;
      break;
    case 37: // left. Caret must be at left of text in the box
      if (!isTextbox || caretPos === 0) {
        deltaX = -1;
      }
      break;
    case 38: // up. Doesn't work in select as this changes the value
      if (!isSelect && rowIndex > 0) {
        deltaY = -1;
      }
      break;
    case 39: // right
      if (!isTextbox || caretPos >= $(this).val().length) {
        deltaX = 1;
      }
      break;
    case 40: // down. Doesn't work in select as this changes the value
      if (!isSelect && rowIndex < rows.length-1) { 
        deltaY = 1;
      }
      break;
  }
  if (deltaX !== 0) {
    var inputs = $(this).closest('table').find(':input:visible');
    // timeout necessary to allow keyup to occur on correct control
    setTimeout(function() {
      inputs.eq(inputs.index(ctrl) + deltaX).focus();
    }, 200);
    evt.preventDefault();
    // see https://bugzilla.mozilla.org/show_bug.cgi?id=291082 - preventDefault bust in FF
    // so reset the value as arrow keys change the value
    if (isSelect) {
      var select=this, val = $(this).val();
      setTimeout(function() {
        $(select).val(val);
      });
    }
    return false;
  }
  if (deltaY !== 0) {
    $(rows[rowIndex+deltaY]).find('td[headers=' + $(cell).attr('headers') + '] input').focus();
  }
}
    
function addRowToGrid(url, gridId, lookupListId, readAuth, formatter, useLookupCache) {
  cacheLookup = typeof useLookupCache !== 'undefined' ? useLookupCache : false;
  // inner function to handle a selection of a taxon from the autocomplete
  var handleSelectedTaxon = function(event, data, value) {
    // on picking a result in the autocomplete, ensure we have a spare row
    // clear the event handlers
    $(event.target).unbind('result', handleSelectedTaxon);
    $(event.target).unbind('return', returnPressedInAutocomplete);
    var taxonCell=event.target.parentNode;
    $(taxonCell).before('<td class="ui-state-default remove-row" style="width: 1%">X</td>');
    // Note case must be colSpan to work in IE!
    $(taxonCell).attr('colSpan',1);
    var row=taxonCell.parentNode;
    $(taxonCell).parent().addClass('added-row');
    $(taxonCell).parent().removeClass('scClonableRow');
    // Do we use a JavaScript fn, or a standard template, to format the species label?
    if ($.isFunction(formatter)) {
      $(taxonCell).html(formatter(data));
    } else {
      // Just a simple PHP template
      var label = formatter;
      // replace each field in the label template
      $.each(data, function(field, value) {
        regex = new RegExp('\\{' + field + '\\}', 'g');
        label = label.replace(regex, value === null ? '' : value);
      });
      $(taxonCell).html(label);
    }
    // Replace the tags in the row template with a rowId consisting of the taxa_taxon_list_ID
    // plus a suffix so that the same taxa may be recorded more than once with 
    // differing attributes.
    var rowId = -1;
    $.each($(row).children(), function(i, cell) {
      $.each($(cell).find('*'), function(idx, child) {
        var oldName, oldId;
        oldName = $(child).attr('name');
        if (typeof oldName !== "undefined" && oldName.indexOf('-ttlId-') !== -1) {
          // Update the name attribute if it contains the replacement tag
          if (rowId === -1) {
            rowId = getRowId(data.id, 'name');
          }
          $(child).attr('name', $(child).attr('name').replace(/-ttlId-/g, rowId));
          }          
        oldId = $(child).attr('id');
        if (typeof oldId !== "undefined" && oldId.indexOf('-ttlId-') !== -1) {
          // Update the id attribute if it contains the replacement tag
          if (rowId === -1) {
            rowId = getRowId(data.id, 'id');
          }          
          $(child).attr('id', $(child).attr('id').replace(/-ttlId-/g, rowId)); 
        }
      });
    });
    $(row).find('.add-image-select-species').hide();
    $(row).find('.add-image-link').show();    
    // auto-check the row
    var checkbox=$(row).find('.scPresenceCell input');
    checkbox.attr('checked', 'checked');
    // and name the control so it posts into the right species record
    if (rowId == -1) {
      rowId = getRowId(data.id, 'name');
    }
    checkbox.attr('name', 'sc:' + rowId + '::present');
    // Finally, a blank row is added for the next record
    makeSpareRow(null, true);
    // Allow forms to hook into the event of a new row being added
    if (typeof hook_species_checklist_new_row !== "undefined") {
      hook_species_checklist_new_row(data);
    }
  };
  
  /**
   * Determines next available rowId for taxon defined by ttlId by searching
   * the attr of existing controls.
   */
  var getRowId = function(ttlId, attr) {
    var rowId, suffix = -1;
    do {
      suffix++;
      rowId = ttlId + '_' + suffix;
    }
    while ($('[' + attr + '^="sc:' + rowId + '"]').length !== 0);
    return rowId;
  }

  /**
   * Ensure field names are consistent independent of whether we are using cached data
   * or not.
   */
  var mapFromCacheTable = function(item) {
    item.common = item.default_common_name;
    item.preferred_name = item.preferred_taxon;
    item.taxon = item.original;
    item.id = item.taxa_taxon_list_id;
    return item;
  };
  
  /**
   * Function fired when return pressed in the species selector - adds a new row and focuses it.
   */
  var returnPressedInAutocomplete=function(evt) {
    var rows=$(evt.currentTarget).parents('tbody').children(),
        rowIndex=rows.index($(evt.currentTarget).parents('tr')[0]);
    if (rowIndex===rows.length-1) {
      var ctrl=makeSpareRow(null, true, 13, true);
      // is return key pressed, if so focus next row
      setTimeout(function() { $(ctrl).focus(); });
    } else {
      // focus the next row
      $(rows[rowIndex+1]).find('td.scTaxonCell input').focus();
      evt.preventDefault();
    }
  };
  
  // Create an inner function for adding blank rows to the bottom of the grid
  var makeSpareRow = function(evt, scroll, keycode, force) {
    if (!$.isFunction(formatter)) {
      // provide a default format function
      formatter = function(item) {
        return item.taxon;
      };
    }
    // only add a spare row if none already exist, or forced to do so
    if ($('table#'+gridId + ' tr.scClonableRow').length>=1 && !force) {
      return;
    }
    // get a copy of the new row template
    var extraParams, newRow = $('tr#'+gridId + '-scClonableRow').clone(true);
    // build an auto-complete control for selecting the species to add to the bottom of the grid. 
    // The next line gets a unique id for the autocomplete.
    selectorId = gridId + '-' + $('#' + gridId +' tbody')[0].children.length;
    var speciesSelector = '<input type="text" id="' + selectorId + '" class="grid-required" />';
    // put this inside the new row template in place of the species label.
    $(newRow).html($(newRow.html().replace('{content}', speciesSelector)));
    // add the row to the bottom of the grid
    newRow.appendTo('table#' + gridId +' > tbody').removeAttr('id');
    extraParams = {
      orderby : cacheLookup ? 'original' : 'taxon',
      mode : 'json',
      qfield : cacheLookup ? 'searchterm' : 'taxon',
      auth_token: readAuth.auth_token,
      nonce: readAuth.nonce,
      taxon_list_id: lookupListId
    };
    if (typeof indiciaData['taxonExtraParams-'+gridId]!=="undefined") {
      $.extend(extraParams, indiciaData['taxonExtraParams-'+gridId]);
    }
    $(newRow).find('input,select').keydown(keyHandler);
    // Attach auto-complete code to the input
    ctrl = $('#' + selectorId).autocomplete(url+'/'+(cacheLookup ? 'cache_taxon_searchterm' : 'taxa_taxon_list'), {
      extraParams : extraParams,
      continueOnBlur: true,
      simplify: cacheLookup, // uses simplified version of search string in cache to remove errors due to punctuation etc.
      parse: function(data) {
        var results = [];
        jQuery.each(data, function(i, item) {
          // common name can be supplied in a field called common, or default_common_name
          if (cacheLookup) {
            item = mapFromCacheTable(item);
          } else {
            item.searchterm = item.taxon;
          }
          results[results.length] =
          {
            'data' : item,
            'result' : item.searchterm,
            'value' : item.id
          };
        });
        return results;
      },
      formatItem: formatter
    });
    ctrl.bind('result', handleSelectedTaxon);
    ctrl.bind('return', returnPressedInAutocomplete);
    // Check that the new entry control for taxa will remain in view with enough space for the autocomplete drop down
    if (scroll && ctrl.offset().top > $(window).scrollTop() + $(window).height() - 180) {
      var newTop = ctrl.offset().top - $(window).height() + 180;
      // slide the body upwards so the grid entry box remains in view, as does the drop down content on the autocomplete for taxa
      $('html,body').animate({scrollTop: newTop}, 500);       
    }
    return ctrl;
  };
  
  makeSpareRow(null, false);
}

$('.remove-row').live('click', function(e) {
  e.preventDefault();
  // Allow forms to hook into the event of a row being deleted, most likely use would be to have a confirmation dialog
  if (typeof hook_species_checklist_pre_delete_row !== "undefined") {
    if(!hook_species_checklist_pre_delete_row(e)) return;
  }
  var row = $(e.target.parentNode);
  if (row.next().find('.file-box').length>0) {
    // remove the uploader row
    row.next().remove();
  }
  if (row.hasClass('added-row')) {
    row.remove();
  } else {
    // This was a pre-existing occurrence so we can't just delete the row from the grid. Grey it out
    row.css('opacity',0.25);
    // Use the presence checkbox to remove the taxon, even if the checkbox is hidden.
    row.find('.scPresence').attr('checked',false);
    // Hide the checkbox so this can't be undone
    row.find('.scPresence').css('display','none');
    // disable or remove all other active controls from the row.
    // Do NOT disable the presence checkbox or the container td, otherwise it is not submitted.
    row.find('*:not(.scPresence,.scPresenceCell)').attr('disabled','disabled');
    row.find('a').remove();
  }
  // Allow forms to hook into the event of a row being deleted
  if (typeof hook_species_checklist_delete_row !== "undefined") {
    hook_species_checklist_delete_row();
  }
});

/**
 * Click handler for the add image link that is displayed alongside each occurrence row in the grid once 
 * it has been linked to a taxon. Adds a row to the grid specifically to contain a file uploader for images
 * linked to that occurrence.
 */
$('.add-image-link').live('click', function(evt) {
  evt.preventDefault();
  var table = evt.target.id.replace('add-images','sc') + ':occurrence_image';
  var ctrlId='container-'+table+'-'+Math.floor((Math.random())*0x10000);
  var colspan = $($(evt.target).parent().parent()).children().length;
  var imageRow = '<tr class="image-row"><td colspan="' + colspan + '">';
  imageRow += '<div class="file-box" id="' + ctrlId + '"></div>';
  imageRow += '</td></tr>';
  imageRow = $(imageRow);
  $($(evt.target).parent().parent()).after(imageRow);
  var opts={
    caption : 'Files',
    autoupload : '1',
    flickr : '',
    uploadSelectBtnCaption : 'Select file(s)',
    startUploadBtnCaption : 'Start upload',
    msgUploadError : 'An error occurred uploading the file.',
    msgFileTooBig : 'The image file cannot be uploaded because it is larger than the maximum file size allowed.',
    runtimes : 'html5,silverlight,flash,gears,browserplus,html4',
    imagewidth : '250',
    uploadScript : uploadSettings.uploadScript,
    destinationFolder : uploadSettings.destinationFolder,
    swfAndXapFolder : uploadSettings.swfAndXapFolder,
    jsPath : uploadSettings.jsPath,    
    table : table,
    maxUploadSize : '4000000', // 4mb
    container: ctrlId
  };
  if (typeof uploadSettings.resizeWidth!="undefined") opts.resizeWidth=uploadSettings.resizeWidth;
  if (typeof uploadSettings.resizeHeight!="undefined") opts.resizeHeight=uploadSettings.resizeHeight;
  if (typeof uploadSettings.resizeQuality!="undefined") opts.resizeQuality=uploadSettings.resizeQuality;
  if (typeof buttonTemplate!="undefined") opts.buttonTemplate=buttonTemplate;
  if (typeof file_boxTemplate!="undefined") opts.file_boxTemplate=file_boxTemplate;
  if (typeof file_box_initial_file_infoTemplate!="undefined") opts.file_box_initial_file_infoTemplate=file_box_initial_file_infoTemplate;
  if (typeof file_box_uploaded_imageTemplate!="undefined") opts.file_box_uploaded_imageTemplate=file_box_uploaded_imageTemplate;
  imageRow.find('div').uploader(opts);
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

/**
 * Method to assist with converting a control in the grid into a popup link. Example usage:
 * jQuery(document).ready(function() {
 *   ConvertControlsToPopup($('.scComment'), 'Comment', Drupal.settings.basePath + '/sites/all/modules/iform/media/images/nuvola/package_editors-22px.png');
 * });
 *
 * function hook_species_checklist_new_row(data) {
 *   var id='#sc:'+data.id+'::occurrence:comment';
 *   id = id.replace(/:/g, '\\:');
 *   ConvertControlsToPopup($(id), 'Comment', Drupal.settings.basePath + '/sites/all/modules/iform/media/images/nuvola/package_editors-22px.png');
 * }
*/
function ConvertControlsToPopup(controls, label, icon) {
  var identifier;
  $.each(controls, function(i, input) {
    if ($(input).parents('.scClonableRow').length===0) {
      // make a unique id for the item which is jQuery safe.
      identifier = input.id.replace(/:/g, '-');
      $(input).after('<div style="display: none;" id="hide-' + identifier + '"><div id="anchor-' + identifier + '"></div></div>');
      $(input).after('<a href="#anchor-' + identifier + '" id="click-' + identifier + '">' +
          '<img src="' + icon + '" width="22" height="22" alt="Show ' + label + '" /></a>');
      $('#anchor-' + identifier).append('<label>'+label+':</label><br/>');
      $('#anchor-' + identifier).append(input);
      $('#anchor-' + identifier).append('<br/><input type="button" value="Close" onclick="$.fancybox.close();" class="ui-state-default ui-corner-all" />');
      // make sure the input shows, though at this stage it is in a hidden div. @todo This is a bit of a nasty hack, 
      // would rather obay CSS precedence rules but !important is getting in the way.
      $(input).css('cssText', 'display: inline !important');
      $('#click-' + identifier).fancybox({titleShow: false, showCloseButton: false});
    }
  });
  
}