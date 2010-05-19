function addRowToGrid(url, gridId, lookupListId, readAuth, labelTemplate) {
	
  // inner function to handle a selection of a taxon from the autocomplete
  var handleSelectedTaxon = function(event, data) {
    // on picking a result in the autocomplete, ensure we have a spare row
    var label = labelTemplate;
    // replace each field in the label template
    $.each(data, function(field, value) {
      regex = new RegExp('\\{' + field + '\\}', 'g');
      label = label.replace(regex, value==null ? '' : value);
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
    makeSpareRow(); 
  };
  
  // Create an inner function for adding blank rows to the bottom of the grid
  var makeSpareRow = function() {
    // get a copy of the new row template
    var newRow =$('tr#'+gridId + '-scClonableRow').clone(true);
    // build an auto-complete control for selecting the species to add to the bottom of the grid
    selectorId = gridId + '-' + $('#' + gridId +' tbody')[0].childElementCount;
    var speciesSelector = '<input type="text" id="' + selectorId + '" />'
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
        return item.taxon; alert('here');
      }
    });
    ctrl.bind('result', handleSelectedTaxon);
  }
  
  makeSpareRow();
}
