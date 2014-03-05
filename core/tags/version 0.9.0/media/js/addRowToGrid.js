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

var mainSpeciesValue = null, formatter;

//Javascript functions using jQuery now need to be defined inside a "(function ($) { }) (jQuery);" wrapper.
//This means they cannot normally be seen by the outside world, so in order to make a call to one of these 
//functions, we need to assign it to a global variable.

var addRowToGrid, keyHandler, ConvertControlsToPopup, hook_species_checklist_new_row, handleSelectedTaxon, 
    taxonNameBeforeUserEdit,returnPressedInAutocomplete,resetSpeciesTextOnEscape;

(function ($) {
  "use strict";
  
  $(document).ready(function() {
    // prevent validation of the clonable row
    $('.scClonableRow :input').addClass('inactive');
  });
  
  hook_species_checklist_new_row = [];
  
  var resetSpeciesText;
  /*
   * Validator makes sures user cannot enter junk into the taxon cell and continue with submit
   */
  jQuery.validator.addMethod('speciesMustBeFilled',
            function(value, element) {
              var presenceCellInput = $(element).parents('tr:first').find('.scPresenceCell').children(':input');
              if ($(presenceCellInput).val() || !$(element).val()) {
                return true;
              }
            },
          '');
          
  /*
   * A keyboard event handler for the grid.
   */
  keyHandler = function(evt) {
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
    //When the user moves around the grid we need to call the function that copies data into a new row 
    //from the previous row if that option is set to be used on the edit tab.
    //$(this).closest('table').attr('id') gets the gridId for use in the option check.
    if (indiciaData['copyDataFromPreviousRow-'+$(this).closest('table').attr('id')] == true) {
      if (deltaX + deltaY !== 0) {
        changeIn2ndToLastRow(this); 
      }
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
  };  

  // Create an inner function for adding blank rows to the bottom of the grid
  var makeSpareRow = function(gridId, readAuth, lookupListId, url, evt, scroll, keycode, force) {
  
    /**
     * Function fired when return pressed in the species selector - adds a new row and focuses it. Must be enclosed so that
     * it can refer to things like the gridId if there are multiple grids.
     */
    returnPressedInAutocomplete=function(evt) {
      var rows=$(evt.currentTarget).parents('tbody').children(),
          rowIndex=rows.index($(evt.currentTarget).parents('tr')[0]);
      if (rowIndex===rows.length-1) {
        var ctrl=makeSpareRow(gridId, readAuth, lookupListId, url, null, true, 13, true);
        // is return key pressed, if so focus next row
        setTimeout(function() { $(ctrl).focus(); });
      } else {
        // focus the next row
        $(rows[rowIndex+1]).find('td.scTaxonCell input').focus();
        evt.preventDefault();
      }
    };
    
    handleSelectedTaxon = function(event, data, value) {
      var taxonCell, checkbox, rowId, row, label, subSpeciesCellId, regex, deleteAndEditHtml;
      //As soon as the user selects a species, we need to save its id as otherwise the information is lost. 
      //This is used if the user selects a sub-species, but then selects the blank option again, we can then use the main species id
      mainSpeciesValue = value;
      // on picking a result in the autocomplete, ensure we have a spare row
      // clear the event handlers
      $(event.target).unbind('result', handleSelectedTaxon);
      $(event.target).unbind('return', returnPressedInAutocomplete);
      taxonCell=event.target.parentNode; 
      //Create edit icons for taxon cells. Only add the edit icon if the user has this functionality available on the edit tab.
      //Also create Notes and Delete icons when required
      var linkPageIconSource = indiciaData.imagesPath + "nuvola/find-22px.png";
      if (indiciaData['editTaxaNames-'+gridId]==true) {
        deleteAndEditHtml = "<td class='row-buttons'>\n\
            <img class='action-button remove-row' src=" + indiciaData.imagesPath + "nuvola/cancel-16px.png>\n" 
        deleteAndEditHtml += "<img class='action-button edit-taxon-name' src=" + indiciaData.imagesPath + "nuvola/package_editors-16px.png>\n";
        if (indiciaData['includeSpeciesGridLinkPage-'+gridId]==true) {
          deleteAndEditHtml += '<img class="species-grid-link-page-icon" title="'+indiciaData.speciesGridPageLinkTooltip+'" alt="Notes icon" src=' + linkPageIconSource + '>';
        }
        deleteAndEditHtml += "</td>";
      } else {   
        deleteAndEditHtml = "<td class='row-buttons'>\n\
            <img class='action-button action-button remove-row' src=" + indiciaData.imagesPath + "nuvola/cancel-16px.png>\n";
        if (indiciaData['includeSpeciesGridLinkPage-'+gridId]==true) {
          deleteAndEditHtml += '<img class="species-grid-link-page-icon" title="'+indiciaData.speciesGridPageLinkTooltip+'" alt="Notes icon" src=' + linkPageIconSource + '>';
        }
        deleteAndEditHtml += "</td>";
      }
      //Put the edit and delete icons just before the taxon name
      $(taxonCell).before(deleteAndEditHtml);
      // Note case must be colSpan to work in IE!
      $(taxonCell).attr('colSpan',1);
      row=taxonCell.parentNode;
      //Only add this class if the user is adding new taxa, if they are editing existing taxa we don't add the class so that when the delete icon is used the
      //row becomes greyed out instead of deleted.
      if ($(row).hasClass('scClonableRow'))
        $(taxonCell).parent().addClass('added-row');
      $(taxonCell).parent().removeClass('scClonableRow');
      $(taxonCell).parent().find('input,select,textarea').removeClass('inactive');
      // Do we use a JavaScript fn, or a standard template, to format the species label?      
      if ($.isFunction(formatter)) {
        $(taxonCell).html(formatter(data));
      } else {
        // Just a simple PHP template
        label = formatter;
        // replace each field in the label template
        $.each(data, function(field, value) {
          regex = new RegExp('\\{' + field + '\\}', 'g');
          label = label.replace(regex, value === null ? '' : value);
        });
        $(taxonCell).html(label);
      }
      $(row).find('.id-diff').hover(indiciaFns.hoverIdDiffIcon);
      $(row).find('.species-checklist-select-species').hide();
      $(row).find('.add-media-link').show();
      // auto-check the row
      checkbox=$(row).find('.scPresenceCell input.scPresence');
      checkbox.attr('checked', 'checked');
      // store the ttlId 
      checkbox.val(data.id);
      if (indiciaData['subSpeciesColumn-'+gridId]==true) {
        // Setup a subspecies picker if this option is enabled. Since we don't know for sure if this is matching the 
        // last row in the grid (as the user might be typing ahead), use the presence checkbox to extract the row unique ID.
        rowId = checkbox[0].id.match(/sc:([a-z0-9\-]+)/)[1];
        subSpeciesCellId = 'sc:' + rowId + '::occurrence:subspecies';
        createSubSpeciesList(url, data.preferred_taxa_taxon_list_id, data.preferred_name, lookupListId, subSpeciesCellId, readAuth, 0);
      }
      // Finally, a blank row is added for the next record
      makeSpareRow(gridId, readAuth, lookupListId, url, null, true);
      //When user selects a taxon then the new row is created, we want to copy data into that new row from previous row automatically.
      //when the option to do so is set.
      if (indiciaData['copyDataFromPreviousRow-'+gridId] == true) {
        species_checklist_add_another_row(gridId);
      }
      // Allow forms to hook into the event of a new row being added
      $.each(hook_species_checklist_new_row, function(idx, fn) {
        fn(data); 
      });
    };
    //If the user chooses to edit a species on the grid, then immediately 'clicks off'
    //the cell, then we have code that puts the label back to the way it was
    resetSpeciesText = function(event) {
      //only do reset if the autocomplete drop down isn't showing, else we assume the user is still working with the cell
      if ($('.ac_over').length===0) {
        var row = $($(event.target).parents('tr:first')),
            taxonCell=$(row).children('.scTaxonCell'),
            gridId = $(taxonCell).closest('table').attr('id'),
            selectorId = gridId + '-' + indiciaData['gridCounter-'+gridId];
        // remove the current contents of the taxon cell
        $('#'+selectorId).remove();
        // replace with the previous plain text species name
        $(taxonCell).html(taxonNameBeforeUserEdit); 
        var deleteAndEditHtml = "<td class='row-buttons'>\n\
            <img class='action-button remove-row' src=" + indiciaData.imagesPath + "nuvola/cancel-16px.png>\n\
            <img class='edit-taxon-name' src=" + indiciaData.imagesPath + "nuvola/package_editors-16px.png>\n";
        if (indiciaData['includeSpeciesGridLinkPage-'+gridId]==true) {
          var linkPageIconSource = indiciaData.imagesPath + "nuvola/find-22px.png";
          deleteAndEditHtml += '<img class="species-grid-link-page-icon" title="'+indiciaData.speciesGridPageLinkTooltip+'" alt="Notes icon" src=' + linkPageIconSource + '>\n';
        }       
        deleteAndEditHtml += "</td\n";
        $(taxonCell).attr('colSpan',1);
        //Put the edit and delete icons just before the taxon name
        $(taxonCell).before(deleteAndEditHtml);
      }
    }
    //If the user presses escape after choosing to edit a taxon name then set it back to a read-only label
    resetSpeciesTextOnEscape = function(event) {
      if (event.which===27) {
        resetSpeciesText(event);
      }
    }
    
    if (typeof formatter==="undefined" || !$.isFunction(formatter)) {
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
    var extraParams, newRow = $('tr#'+gridId + '-scClonableRow').clone(true), selectorId, speciesSelector, 
        oldName, oldId, ctrl;
    // build an auto-complete control for selecting the species to add to the bottom of the grid.
    // The next line gets a unique id for the autocomplete.
    selectorId = gridId + '-' + indiciaData['gridCounter-'+gridId];
    speciesSelector = '<input type="text" id="' + selectorId + '" class="grid-required {speciesMustBeFilled:true}" />';
    // put this inside the new row template in place of the species label.
    $(newRow).html($(newRow.html().replace('{content}', speciesSelector)));
    // Replace the tags in the row template with a unique row ID
    $.each($(newRow).children(), function(i, cell) {
      $.each($(cell).find('*'), function(idx, child) {
        oldName = $(child).attr('name');
        if (typeof oldName !== "undefined" && oldName.indexOf('-idx-') !== -1) {
          $(child).attr('name', $(child).attr('name').replace(/-idx-/g, indiciaData['gridCounter-'+gridId]));
        }
        oldId = $(child).attr('id');
        if (typeof oldId !== "undefined" && oldId.indexOf('-idx-') !== -1) {
          $(child).attr('id', $(child).attr('id').replace(/-idx-/g, indiciaData['gridCounter-'+gridId]));
        }
      });
    });
    $(newRow).find("[name$='\:sampleIDX']").each(function(idx, field) {
      if (indiciaData['subSamplePerRow-'+gridId]) {
        //Allows a sample to be generated for each occurrence in the grid if required.
        var rowNumber=$(field).attr('name').replace('sc:'+gridId+'-','');
        rowNumber = rowNumber.substring(0,1);
        $(field).val(rowNumber);
      } else {
        $(field).val(typeof indiciaData.control_speciesmap_existing_feature==="undefined" || indiciaData.control_speciesmap_existing_feature===null ?
            indiciaData['gridSampleCounter-'+gridId] : indiciaData.control_speciesmap_existing_feature.attributes.subSampleIndex);
      }
    });
    // add the row to the bottom of the grid
    newRow.appendTo('table#' + gridId +' > tbody').removeAttr('id');
    extraParams = {
      orderby : indiciaData.speciesGrid[gridId].cacheLookup ? 'searchterm_length,original,preferred_taxon' : 'taxon',
      mode : 'json',
      qfield : indiciaData.speciesGrid[gridId].cacheLookup ? 'searchterm' : 'taxon',
      auth_token: readAuth.auth_token,
      nonce: readAuth.nonce,
      taxon_list_id: lookupListId
    };
    if (typeof indiciaData['taxonExtraParams-'+gridId]!=="undefined") { 
      $.extend(extraParams, indiciaData['taxonExtraParams-'+gridId]);
      // a custom query on the list id overrides the standard filter..
      if (typeof extraParams.query!=="undefined" && extraParams.query.indexOf('taxon_list_id')!==-1) {
        delete extraParams.taxon_list_id;
      }
    }
    $(newRow).find('input,select').keydown(keyHandler);
    var autocompleteSettings = getAutocompleteSettings(extraParams, gridId);
    if ($('#' + selectorId).width()<200) {
      autocompleteSettings.width = 200;
    }
    // Attach auto-complete code to the input
    ctrl = $('#' + selectorId).autocomplete(url+'/'+(indiciaData.speciesGrid[gridId].cacheLookup ? 'cache_taxon_searchterm' : 'taxa_taxon_list'), autocompleteSettings);
    ctrl.bind('result', handleSelectedTaxon);
    ctrl.bind('return', returnPressedInAutocomplete);
    // Check that the new entry control for taxa will remain in view with enough space for the autocomplete drop down
    if (scroll && ctrl.offset().top > $(window).scrollTop() + $(window).height() - 180) {
      var newTop = ctrl.offset().top - $(window).height() + 180;
      // slide the body upwards so the grid entry box remains in view, as does the drop down content on the autocomplete for taxa
      $('html,body').animate({scrollTop: newTop}, 500);
    }
    // increment the count so it is unique next time and we can generate unique IDs
    indiciaData['gridCounter-'+gridId]++;
    return ctrl;
  };
  
  addRowToGrid = function(url, gridId, lookupListId, readAuth, formatter) {
    var cacheLookup = indiciaData.speciesGrid[gridId].cacheLookup;
    makeSpareRow(gridId, readAuth, lookupListId, url, null, false);
    //Deal with user clicking on edit taxon icon
    $('.edit-taxon-name').live('click', function(e) {
      if ($('.ac_results:visible').length>0 || !$(e.target).is(':visible')) {
        // don't go into edit mode if they are picking a species name already
        return;
      }
      var row = $($(e.target).parents('tr:first')),
          taxonCell=$(row).children('.scTaxonCell'),
          gridId = $(taxonCell).closest('table').attr('id'),
          selectorId = gridId + '-' + indiciaData['gridCounter-'+gridId],
          taxonTextBeforeUserEdit;
      //When moving into edit mode we need to create an autocomplete box for the user to fill in
      var speciesAutocomplete = '<input type="text" id="' + selectorId + '" class="grid-required ac_input {speciesMustBeFilled:true}" autocomplete="off"/>';
      //remove the edit and delete icons.
      $(e.target).parent().remove();
      taxonNameBeforeUserEdit = $(taxonCell).html();
      // first span should contain the name as it was entered
      taxonTextBeforeUserEdit = $(taxonCell).text().split(' - ')[0];
      //add the autocomplete cell
      $(taxonCell).append(speciesAutocomplete);
      //Adjust the size of the taxon cell to take up its full allocation of space
      $(taxonCell).attr('colSpan',2);
      //Moving into edit mode, we need to clear the static taxon label otherwise
      //the name is shown twice (it is also shown in the autocomplete)
      $(taxonCell).text('');
      $(taxonCell).append(speciesAutocomplete);
      var extraParams = {
        orderby : cacheLookup ? 'searchterm_length,original,preferred_taxon' : 'taxon',
        mode : 'json',
        qfield : cacheLookup ? 'searchterm' : 'taxon',
        auth_token: readAuth.auth_token,
        nonce: readAuth.nonce,
        taxon_list_id: lookupListId
      };
      var autocompleteSettings = getAutocompleteSettings(extraParams, gridId);
      var ctrl = $(taxonCell).children(':input').autocomplete(url+'/'+(cacheLookup ? 'cache_taxon_searchterm' : 'taxa_taxon_list'), autocompleteSettings);
      //put the taxon name into the autocomplete ready for editing
      $('#'+selectorId).val(taxonTextBeforeUserEdit);
      $('#'+selectorId).focus();
      //Set the focus to the end of the string, this isn't elegant, but seems to be quickest way to do this.
      //After we set focus, we add a space to the end of the string to force focus to end, then remove the space
      $('#'+selectorId).val($('#'+selectorId).val() + ' ');
      $('#'+selectorId).val($('#'+selectorId).val().slice(0, -1));
      ctrl.bind('result', handleSelectedTaxon);
      ctrl.bind('return', returnPressedInAutocomplete);
      //bind function so that when user loses focus on the taxon cell immediately after clicking edit, we can reset the cell
      //back to read-only label 
      ctrl.bind('blur', resetSpeciesText);
      ctrl.bind('keydown', resetSpeciesTextOnEscape);
    });
  };
  
  $('.remove-row').live('click', function(e) {
    e.preventDefault();
    // Allow forms to hook into the event of a row being deleted, most likely use would be to have a confirmation dialog
    if (typeof hook_species_checklist_pre_delete_row !== "undefined") {
      if(!hook_species_checklist_pre_delete_row(e)) {
        return;
      }
    }
    var row = $($(e.target).parents('tr:first'));
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
      // Do NOT disable the presence checkbox or the container td, nor the sample Index field if present, otherwise they are not submitted.
      row.find('*:not(.scPresence,.scPresenceCell,.scSample,.scSampleCell)').attr('disabled','disabled');
      row.find('a').remove();
    }
    // Allow forms to hook into the event of a row being deleted
    if (typeof hook_species_checklist_delete_row !== "undefined") {
      hook_species_checklist_delete_row();
    }
  });
  //Open the specified page when the user clicks on the page link icon on a species grid row, use a dirty URL as this will work whether clean urls is on or not
  $('.species-grid-link-page-icon').live('click', function(e) {
    var row = $($(e.target).parents('tr:first'));
    var taxa_taxon_list_id_to_use;
    //We cannot get the taxa_taxon_list_id by simply just getting the presence cell value, as sometimes there is more than one
    //presence cell. This is because there is an extra presence cell that is used to supply a 0 in the $_GET to the submission
    //as a checkbox input type doesn't appear in the $_GET with a 0 value.
    //So we need to actually use the presence cell with a non-zero value.
    row.find('.scPresence').each( function() {
      if ($(this).val()!=0) {
        taxa_taxon_list_id_to_use=$(this).val();
      }
    });
    window.open(indiciaData.rootFolder + '?q=' + indiciaData.speciesGridPageLinkUrl + '&' + indiciaData.speciesGridPageLinkParameter + '=' +  taxa_taxon_list_id_to_use)
  });

  /**
   * Click handler for the add image link that is displayed alongside each occurrence row in the grid once
   * it has been linked to a taxon. Adds a row to the grid specifically to contain a file uploader for images
   * linked to that occurrence.
   */
  $('.add-media-link').live('click', function(evt) {
    evt.preventDefault();
    var table = evt.target.id.replace('add-media','sc') + ':occurrence_medium';
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
          msgUploadError : 'An error occurred uploading the file.',
          msgFileTooBig : 'The image file cannot be uploaded because it is larger than the maximum file size allowed.',
          runtimes : 'html5,flash,silverlight,html4',
          imagewidth : '250',
          uploadScript : indiciaData.uploadSettings.uploadScript,
          destinationFolder : indiciaData.uploadSettings.destinationFolder,
          jsPath : indiciaData.uploadSettings.jsPath,
          table : table,
          maxUploadSize : '4000000', // 4mb
          container: ctrlId,
          autopick: true,
          mediaTypes: indiciaData.uploadSettings.mediaTypes
        };
        if (typeof indiciaData.uploadSettings.resizeWidth!=="undefined") { opts.resizeWidth=indiciaData.uploadSettings.resizeWidth; }
        if (typeof indiciaData.uploadSettings.resizeHeight!=="undefined") { opts.resizeHeight=indiciaData.uploadSettings.resizeHeight; }
        if (typeof indiciaData.uploadSettings.resizeQuality!=="undefined") { opts.resizeQuality=indiciaData.uploadSettings.resizeQuality; }
        if (typeof buttonTemplate!=="undefined") { opts.buttonTemplate=buttonTemplate; }
        if (typeof file_boxTemplate!=="undefined") { opts.file_boxTemplate=file_boxTemplate; }
        if (typeof file_box_initial_file_infoTemplate!=="undefined") { opts.file_box_initial_file_infoTemplate=file_box_initial_file_infoTemplate; }
        if (typeof file_box_uploaded_imageTemplate!=="undefined") { opts.file_box_uploaded_imageTemplate=file_box_uploaded_imageTemplate; }
    imageRow.find('div').uploader(opts);
    $(evt.target).hide();
  });
  
  $('.hide-image-link').live('click', function(evt) {
    evt.preventDefault();
    var ctrlId=(evt.target.id.replace(/^hide\-images/, 'container-sc') + ':occurrence_medium').replace(/:/g, '\\:');
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
   *   ConvertControlsToPopup($('.scComment'), 'Comment', indiciaData.imagesPath + 'nuvola/package_editors-22px.png');
   * });
   *
   * function hook_species_checklist_new_row(data) {
   *   var id='#sc:'+data.id+'::occurrence:comment';
   *   id = id.replace(/:/g, '\\:');
   *   ConvertControlsToPopup($(id), 'Comment', indiciaData.imagesPath + 'nuvola/package_editors-22px.png');
   * }
  */
   ConvertControlsToPopup = function (controls, label, icon) {
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

  };

  RegExp.escape= function(s) {
    return s.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
  };
}) (jQuery);


function createSubSpeciesList(url, selectedItemPrefId, selectedItemPrefName, lookupListId, subSpeciesCtrlId, readAuth, selectedChild) {
  "use strict";
  var subSpeciesData={
    'mode': 'json',
    'nonce': readAuth.nonce,
    'auth_token': readAuth.auth_token,
    'parent_id': selectedItemPrefId,
    'taxon_list_id': lookupListId,
    'name_type': 'L',
    'simplified': 'f'
  }, ctrl=jQuery("#"+subSpeciesCtrlId.replace(/:/g,'\\:'));
  if (ctrl.length>0) {
    jQuery.getJSON(url+'/cache_taxon_searchterm?callback=?', subSpeciesData, 
      function(data) {
        var sspRegexString, epithet, nameRegex;
        //clear the sub-species cell ready for new data
        ctrl.empty();
        // build a regex that can remove the species binomial (plus optionally the subsp rank) from the name, so
        // Adelia decempunctata forma bimaculata can be shown as just bimaculata.
        sspRegexString=RegExp.escape(selectedItemPrefName);
        if (typeof indiciaData.subspeciesRanksToStrip!=="undefined") {
          sspRegexString += "[ ]+" + indiciaData.subspeciesRanksToStrip;
        }
        nameRegex=new RegExp('^'+sspRegexString);
        //Work our way through the sub-species data returned from data services
        jQuery.each(data, function(i, item) {
          epithet = item.preferred_taxon.replace(nameRegex, '');
          if (selectedChild===item.taxa_taxon_list_id) {
            //If we find the sub-species we want to be selected by default then we set the 'selected' attribute on html the option tag
            ctrl.append(jQuery('<option selected="selected"></option>').val(item.taxa_taxon_list_id).html(epithet));
          } else {
            //If we don't want this sub-species to be selected by default then we don't set the 'selected' attribute on html the option tag
            ctrl.append(jQuery("<option></option>").val(item.taxa_taxon_list_id).html(epithet));          
          }
        });
        //If we don't find any sub-species then hide the control
        if (data.length===0) {
          ctrl.hide();
        } else {
          //The selected sub-species might be the first (blank) option if there are sub-species present but
          //we don't know yet which one the user wants.
          //This would occur if the user manually fills in the species and the parent has sub-species
          if (selectedChild===0) {
            ctrl.prepend("<option value='' selected='selected'></option>");
          }
          ctrl.show();
        }
        
      }
    );
  }
}

function SetHtmlIdsOnSubspeciesChange(subSpeciesId) {
  "use strict";
  //We can work out the grid row number we are working with by stripping the sub-species id.
  var presentCellId, presentCellSelector, subSpecieSelectorId, subSpeciesValue, gridRowId = subSpeciesId.match(/\d+\.?\d*/g);
  presentCellId = 'sc:' + gridRowId + '::present';
  //We need to escape certain characters in the html id so we can use it with jQuery.
  presentCellSelector = presentCellId.replace(/:/g,'\\:');
  //If we don't have a taxon id for the parent species saved, then collect it from the html
  if (!mainSpeciesValue) {
    mainSpeciesValue = jQuery("#"+presentCellSelector).val();
  }
  subSpecieSelectorId = subSpeciesId.replace(/:/g,'\\:');
  subSpeciesValue=(jQuery("#"+subSpecieSelectorId).val());
  //If the user has selected the blank sub-species row, then we use the parent species
  if (subSpeciesValue==="") {
    jQuery("#"+presentCellSelector).val(mainSpeciesValue);
  }
  if (subSpeciesValue) {
    jQuery("#"+presentCellSelector).val(subSpeciesValue);
  }
}

//When working with data in individual occurrence attribute columns, we need to get a nice unique clean class for that column
//to work with in selectors we want to use. So we just need to grab the class that starts 'sc'.
function getScClassForColumnCellInput(input) {
  //get the class for the cell
  var classesArray = jQuery(input).attr('class').split(/\s+/),
    //for our purposes we are only interested in the classes beginning sc
    theInputClass =  classesArray.filter(function(value) {
      if (value.substr(0,2)=='sc')
        return value;
    });
  if (theInputClass.length>0) {
    return theInputClass[0];
  } 
  else {
    return false;
  }
}

//When the user edits the second last row, then copy the data from the changed cell
//into the new row.
function changeIn2ndToLastRow(input) {
  //get user specified columns to include in the copy
  var gridId = jQuery(input).closest('table').attr('id'),
      columnsToInclude = indiciaData['previousRowColumnsToInclude-'+gridId].split(",");
  //get rid of all of the spacing and capital letters
  for (i=0; i<columnsToInclude.length;i++) {
    columnsToInclude[i] = 'sc'+columnsToInclude[i].replace(/ /g,'').toLowerCase();
  }
  
  var classToUse = getScClassForColumnCellInput(input),
      $newRow = jQuery('table#'+gridId + ' tr.scClonableRow'),
      //The '.added-row:first' check is there
      //as the user might of added an image-row which we need to ignore
      $previousRow = $newRow.prevAll(".added-row:first");
  //Copy data from the 2nd last row into the new row only if the column 
  //is in the user's options
  if (classToUse && (jQuery.inArray(classToUse.toLowerCase(), columnsToInclude)>-1)) {
    $newRow.find('.'+classToUse).val($previousRow.find('.'+classToUse).val());
  }
}

// This proxies the above method so that it can be called from an event with this set to the input, rather
// than directly passing the input as a parameter.
function changeIn2ndToLastRowProxy() {
  changeIn2ndToLastRow(this);
}

//function to copy the values for a new row from the previous row as the new row is added.
function species_checklist_add_another_row(gridId) {
  //get user specified columns to include in the copy
  var columnsToInclude = indiciaData['previousRowColumnsToInclude-'+gridId].split(",");
  //get rid of all of the spacing and capital letters
  for (i=0; i<columnsToInclude.length;i++) {
    columnsToInclude[i] = 'sc'+columnsToInclude[i].replace(/ /g,'').toLowerCase();
  }
  
  var $newRow = jQuery('table#'+gridId + ' tr.scClonableRow');
  //Get the previous row to the new row
  $previousRow = $newRow.prevAll(".added-row:first");
    
  //cycle through each input element on the new row
  $newRow.find(':input').each(function(){
    //Get a clean class to work with for the column
    var classToUse = getScClassForColumnCellInput(this);
    //Only continue if the column is part of the user's options.
    if (classToUse  && (jQuery.inArray(classToUse.toLowerCase(), columnsToInclude)>-1)) {
      //Bind the cell in the previous cell so that when it is changed the new row will update
      $previousRow.find('.'+classToUse).bind('change', changeIn2ndToLastRowProxy);
      //We set the value for the new row from the previous row if there is a value set on the previous row cell
      //and the user has included that column in their options. (inArray reurns -1 for items not found)
      if ($previousRow.find('.'+classToUse).val() && (jQuery.inArray(classToUse.toLowerCase(), columnsToInclude)>-1)) {
        jQuery(this).val($previousRow.find('.'+classToUse).val());
      }
      //We need to unbind the 3rd last row as we no longer what changes for that cell to affect the last row.
      $previousRow.prevAll(".added-row:first").find('.'+classToUse).unbind('change', changeIn2ndToLastRowProxy);
    }
    
  });
  
}

//function to get settings to setup for an autocomplete cell
function getAutocompleteSettings(extraParams, gridId) {
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
  
  var autocompleterSettingsToReturn = {
    extraParams : extraParams,
    continueOnBlur: true,
    simplify: indiciaData.speciesGrid[gridId].cacheLookup, // uses simplified version of search string in cache to remove errors due to punctuation etc.
    max: indiciaData.speciesGrid[gridId].numValues,
    selectMode: indiciaData.speciesGrid[gridId].selectMode,
    parse: function(data) {
      var results = [], done={};
      jQuery.each(data, function(i, item) {
        // common name can be supplied in a field called common, or default_common_name
        if (indiciaData.speciesGrid[gridId].cacheLookup) {
          item = mapFromCacheTable(item);
        } else {
          item.searchterm = item.taxon;
        }
        // note we track the distinct ttl_id and display term, so we don't output duplicates
        if (!done.hasOwnProperty(item.taxon_meaning_id + '_' + item.display)) {
          results[results.length] =
          {
            'data' : item,
            'result' : item.searchterm,
            'value' : item.id
          };
          done[item.taxon_meaning_id + '_' + item.display]=true;
        }
      });
      return results;
    },
    formatItem: formatter
  };
  return autocompleterSettingsToReturn;
}
