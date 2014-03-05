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

function bindSpeciesAutocomplete(selectorID, target, url, lookupListId, lookupListFilterField, lookupListFilterValues, readAuth, max) {
  // inner function to handle a selection of a taxon from the autocomplete
  var handleSelectedTaxon = function(event, data) {
    var name = $('#'+target).attr('name').split(':');
    name[2] = data.id;
    $('#'+target).attr('name', name.join(':')).addClass('required').removeAttr('disabled').removeAttr('readonly');
    if($('#'+target).val() == '') $('#'+target).val(0);
    var parent = $('#'+target).parent();
    if(parent.find('.deh-required').length == 0) parent.append('<span class="deh-required">*</span>');
  };
  
  var extra_params = {
        view : 'detail',
        orderby : 'taxon',
        mode : 'json',
        qfield : 'taxon',
        auth_token: readAuth.auth_token,
        nonce: readAuth.nonce,
        taxon_list_id: lookupListId
  };
  if(typeof lookupListFilterField != 'undefined'){
    extra_params.query = '{"in":{"'+lookupListFilterField+'":'+lookupListFilterValues+'}}';
  };

  // Attach auto-complete code to the input
  ctrl = $('#' + selectorID).autocomplete(url+'/taxa_taxon_list', {
      extraParams : extra_params,
      max : max,
      parse: function(data) {
        var results = [];
        jQuery.each(data, function(i, item) { results[results.length] = {'data' : item, 'result' : item.taxon, 'value' : item.id}; });
        return results;
      },
      formatItem: function(item) { return item.taxon; }
  });
  ctrl.bind('result', handleSelectedTaxon);
  setTimeout(function() { $('#' + ctrl.attr('id')).focus(); });
};

initButtons = function(){
  $('.remove-button').click(function(){
    var myRow = $(this).closest('tr');
    // we leave the field names the same, so that the submission builder can delete the occurrence.
    // need to leave as enabled, so set as readonly.
    myRow.find('input').val('').filter('.occValField').attr('readonly','readonly').removeClass('required');
    myRow.find('.deh-required').remove();
  });

  $('.clear-button').click(function(){
    var myFieldset = $(this).closest('fieldset');
    myFieldset.find('.hasDatepicker').val('').removeClass('required');
    myFieldset.find('.occValField,.smp-input,[name=taxonLookupControl]').val('').attr('disabled','disabled').removeClass('required'); // leave the count fields as are.
    myFieldset.find('table .deh-required').remove();
  });
}


