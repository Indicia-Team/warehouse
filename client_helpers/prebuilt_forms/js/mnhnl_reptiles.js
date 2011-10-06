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
function addRowToGrid(url, gridId, lookupListId, readAuth, formatter) {
	
  // inner function to handle a selection of a taxon from the autocomplete
  var handleSelectedTaxon = function(event, data) {
    addRowToGridSequence++;
    // on picking a result in the autocomplete, ensure we have a spare row
    // clear the event handler
    $(event.target).unbind('result', handleSelectedTaxon);
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
        label = label.replace(regex, value===null ? '' : value);
      });
      $(taxonCell).html(label);
    }
    // Replace the tags in the row template with the taxa_taxon_list_ID
    $.each($(row).children(), function(i, cell) {
      cell.innerHTML = cell.innerHTML.replace(/-ttlId-:/g, data.id+':y'+addRowToGridSequence);
    }); 
    $(row).find('.add-image-select-species').hide();
    $(row).find('.add-image-link').show();    
    // auto-check the row
    var checkbox=$(row).find('.scPresenceCell input');
    checkbox.attr('checked', 'checked');
    // and rename the controls so they post into the right species record
    checkbox.attr('name', 'sc:' + data.id + ':y'+addRowToGridSequence+':present');    
    // Finally, a blank row is added for the next record
    makeSpareRow(true, formatter);
    // Allow forms to hook into the event of a new row being added
    if (typeof hook_species_checklist_new_row !== "undefined") {
      hook_species_checklist_new_row(data);
    }
  };
  
  // Create an inner function for adding blank rows to the bottom of the grid
  var makeSpareRow = function(scroll) {
    if (!$.isFunction(formatter)) {
      // provide a default format function
      formatter = function(item) {
        return item.taxon;
      };
    } 
    // get a copy of the new row template
    var newRow =$('tr#'+gridId + '-scClonableRow').clone(true);
    // build an auto-complete control for selecting the species to add to the bottom of the grid. 
    // The next line gets a unique id for the autocomplete.
    selectorId = gridId + '-' + $('#' + gridId +' tbody')[0].childElementCount;
    var speciesSelector = '<input type="text" id="' + selectorId + '" class="grid-required" />';
    // put this inside the new row template in place of the species label.
    $(newRow).html($(newRow.html().replace('{content}', speciesSelector)));
    // add the row to the bottom of the grid
    newRow.appendTo('table#' + gridId +' > tbody').removeAttr('id');
  
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
      formatItem: formatter
    });
    ctrl.bind('result', handleSelectedTaxon);
    setTimeout(function() { $('#' + ctrl.attr('id')).focus(); });
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
  // Allow forms to hook into the event of a row being deleted, most likely use would be to have a confirmation dialog
  if (typeof hook_species_checklist_pre_delete_row !== "undefined") {
    if(!hook_species_checklist_pre_delete_row(e)) return;
  }
  // @todo unbind all event handlers
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

function ViewAllLuxembourg(lat, long, zoom){
	var div = jQuery('#map')[0];
	var center = new OpenLayers.LonLat(long, lat);
	center.transform(div.map.displayProjection, div.map.projection);
	div.map.setCenter(center, zoom);
};
function ZoomToDataExtent(layer){if(layer.features.length > 0) layer.map.zoomToExtent(layer.getDataExtent())};
