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
var hook_species_checklist_new_row, bindSpeciesAutocomplete;

(function ($) {

"use strict";
  
hook_species_checklist_new_row = [];

bindSpeciesAutocomplete = function(selectorID, url, gridId, lookupListId, readAuth, formatter, duplicateMsg, max) {
  // inner function to handle a selection of a taxon from the autocomplete
  var handleSelectedTaxon = function(event, data) {
    var myClass='scMeaning-'+data.taxon_meaning_id;
    if(jQuery('.'+myClass).not('.deleted-row').length>0){
      alert(duplicateMsg);
      $(event.target).val('');
      return;
    }
    var rows=$('#'+gridId + '-scClonable > tbody > tr');
    var newRows=[];
    rows.each(function(){newRows.push($(this).clone(true))})
    var taxonCell=newRows[0].find('td:eq(1)');
    // Replace the tags in the row template with the taxa_taxon_list_ID
    $.each(newRows, function(i, row) {
      row.addClass('added-row').addClass(myClass).removeClass('scClonableRow').attr('id','');
      $.each(row.children(), function(j, cell) {
        cell.innerHTML = cell.innerHTML.replace(/-idx-/g, indiciaData['speciesGridCounter']);
      }); 
      row.appendTo('#'+gridId);
      row.find('.scOccAttrCell').find('select').addClass('required').after('<span class=\"deh-required\">*</span>');
      row.find('.scOccAttrCell').find('input').not(':hidden').addClass('fillgroup');
      row.find('.scCommentLabelCell').each(function(idx,elem){
          jQuery(this).css('width',jQuery(this).find('label').css('width'));
      });
    }); 
    newRows[0].find('.scPresenceCell input').attr('name', 'sc:' + indiciaData['speciesGridCounter'] + '::present').attr('checked','checked').val(data.id);
    // Allow forms to hook into the event of a new row being added
    $.each(hook_species_checklist_new_row, function(idx, fn) {
      fn(data); 
    });
    indiciaData['speciesGridCounter']++;
    $(event.target).val('');
    formatter(data,taxonCell);
  };

    // Attach auto-complete code to the input
  var ctrl = $('#' + selectorID).autocomplete(url+'/taxa_taxon_list', {
      extraParams : {
        view : 'detail',
        orderby : 'taxon',
        mode : 'json',
        qfield : 'taxon',
        auth_token: readAuth.auth_token,
        nonce: readAuth.nonce,
        taxon_list_id: lookupListId
      },
      max : max,
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
  setTimeout(function() { $('#' + ctrl.attr('id')).focus(); });
}

$('.remove-row').live('click', function(e) {
  e.preventDefault();
  // Allow forms to hook into the event of a row being deleted, most likely use would be to have a confirmation dialog
  if (typeof hook_species_checklist_pre_delete_row !== "undefined") {
    if(!hook_species_checklist_pre_delete_row(e)) return;
  }
  // @todo unbind all event handlers
  var row = $(e.target.parentNode);
  var numRows = $(e.target).attr('rowspan');
  if (row.hasClass('added-row')) {
    for(var i=1;i<numRows;i++) row.next().remove();
    row.remove();
  } else {
    // This was a pre-existing occurrence so we can't just delete the row from the grid. Grey it out
    // Use the presence checkbox to remove the taxon, even if the checkbox is hidden.
    // Hide the checkbox so this can't be undone
    row.find('.scPresence').attr('checked',false).css('display','none');
    var considerRow = row;
    for(var i=0;i<numRows;i++){
      // disable or remove all other active controls from the row.
      // Do NOT disable the presence checkbox or the container td, otherwise it is not submitted.
      considerRow.addClass('deleted-row').css('opacity',0.25);
      considerRow.find('*:not(.scPresence,.scPresenceCell)').attr('disabled','disabled').removeClass('required ui-state-error').filter('input,select').val('').width('');
      considerRow.find('a').remove();
      considerRow.find('.deh-required,.inline-error').remove();
      considerRow= considerRow.next();
    }
  }
  if (typeof hook_species_checklist_delete_row !== "undefined") {
	  hook_species_checklist_delete_row();
  }
});

}) (jQuery);
