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
var addRowToGridSequence = 1000; // this should be more than the length of the initial taxon list
function bindSpeciesAutocomplete(selectorID, url, gridId, lookupListId, readAuth, formatter) {
  // inner function to handle a selection of a taxon from the autocomplete
  var handleSelectedTaxon = function(event, data) {
    addRowToGridSequence++;
    var newRow1 =$('tr#'+gridId + '-scClonableRow1').clone(true);
    var newRow2 =$('tr#'+gridId + '-scClonableRow2').clone(true);
    var taxonCell=newRow1.find('td:eq(1)');
    newRow1.addClass('added-row').removeClass('scClonableRow').attr('id','');
    newRow2.addClass('added-row').removeClass('scClonableRow').attr('id','').addClass('scMeaning-'+data.taxon_meaning_id);
    // Replace the tags in the row template with the taxa_taxon_list_ID
    $.each(newRow2.children(), function(i, cell) {
      cell.innerHTML = cell.innerHTML.replace(/-ttlId-:/g, data.id+':y'+addRowToGridSequence);
    }); 
    // auto-check the row
    newRow2.find('.scPresenceCell input').attr('name', 'sc:' + data.id + ':y'+addRowToGridSequence+':present').attr('checked', 'checked');
    newRow2.find('.scCount').addClass('required').attr('min',1).after('<span class=\"deh-required\">*</span>');
    newRow2.find('.scOccAttrCell').find('select').not('.scUnits').addClass('required').width('auto').after('<span class=\"deh-required\">*</span>');
    newRow2.find('.scUnits').width('auto');
    newRow1.appendTo('#'+gridId);
    newRow2.appendTo('#'+gridId);
    if($('tr#'+gridId + '-scClonableRow3').length>0){
        var newRow3 =$('tr#'+gridId + '-scClonableRow3').clone(true);
        newRow3.addClass('added-row').removeClass('scClonableRow').attr('id','').addClass('scMeaning-'+data.taxon_meaning_id);
        // Replace the tags in the row template with the taxa_taxon_list_ID
        $.each(newRow3.children(), function(i, cell) {
          cell.innerHTML = cell.innerHTML.replace(/-ttlId-:/g, data.id+':y'+addRowToGridSequence);
        }); 
        // Allow forms to hook into the event of a new row being added
        newRow3.appendTo('#'+gridId);
    }
    // Allow forms to hook into the event of a new row being added
    if (typeof hook_check_no_obs !== "undefined") {
  	  hook_check_no_obs();
    }
    $(event.target).val('');
    formatter(data,taxonCell);
  };

    // Attach auto-complete code to the input
  ctrl = $('#' + selectorID).autocomplete(url+'/taxa_taxon_list', {
      extraParams : {
        view : 'detail',
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
  if (row.hasClass('added-row')) {
    row.next().remove();
    if($(e.target).attr('rowspan') > 2)
        row.next().remove();
    row.remove();
  } else {
    // This was a pre-existing occurrence so we can't just delete the row from the grid. Grey it out
    nextRow = row.next();
    row.css('opacity',0.25);
    nextRow.css('opacity',0.25);
    // Use the presence checkbox to remove the taxon, even if the checkbox is hidden.
    nextRow.find('.scPresence').attr('checked',false);
    // Hide the checkbox so this can't be undone
    nextRow.find('.scPresence').css('display','none');
    // disable or remove all other active controls from the row.
    // Do NOT disable the presence checkbox or the container td, otherwise it is not submitted.
    nextRow.find('*:not(.scPresence,.scPresenceCell)').attr('disabled','disabled').removeClass('required').filter('input,select').val('');
    nextRow.find('a').remove();
    nextRow.find('.deh-required').remove();
    if($(e.target).attr('rowspan') > 2) {
        // this extra row only has the optional comment field on it.
        nextRow = nextRow.next();
        nextRow.css('opacity',0.25);
        // disable or remove all active controls from the row.
        nextRow.find('*').attr('disabled','disabled').filter('input').val('');
    }
  }
  if (typeof hook_check_no_obs !== "undefined") {
	  hook_check_no_obs();
  }
});
