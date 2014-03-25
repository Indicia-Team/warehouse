/**
 * Updates the sample for a section, including attributes.
 */
function saveSample(code) {
  var parts, id;
  jQuery('#smpid').val(indiciaData.samples[code]);
  jQuery.each(indiciaData.sections, function(idx, section) {
    if (section.code==code) {
      // copy the fieldname and value into the sample submission form for each sample custom attribute
      jQuery.each(jQuery('.smpAttr-' + section.code), function(idx, src) {
        parts=src.id.split(':');
        parts.pop();
        id=parts.join('\\:');
        jQuery('#'+id).val(jQuery(src).val());
        jQuery('#'+id).attr('name', jQuery(src).attr('name'));
      });
      jQuery('#smpsref').val(section.centroid_sref);
      jQuery('#smpsref_system').val(section.centroid_sref_system);
      jQuery('#smploc').val(section.id);
      jQuery('#smp-form').submit();
    }
  });
}

jQuery(document).ready(function() {
  jQuery('#imp-location').change(function(evt) {
    jQuery('#entered_sref').val(indiciaData.sites[evt.target.value].centroid_sref);
    jQuery('#entered_sref_system').val(indiciaData.sites[evt.target.value].centroid_sref_system);
  });
});

function getTotal(cell) {
  var row=jQuery(cell).parents('tr:first')[0];
  var table=jQuery(cell).closest('table')[0];
  // get the total for the row
  var total=0, cellValue;
  jQuery.each(jQuery(row).find('.count-input'), function(idx, cell) {
    cellValue = parseInt(jQuery(cell).val());
    if (!isNaN(cellValue)) {
      total += cellValue;
    }
  });
  jQuery(row).find('.row-total').html(total);
  // get the total for the column
  var matches = jQuery(cell).parents('td:first')[0].className.match(/col\-\d+/);
  var colidx = matches[0].substr(4);
  total = 0;
  jQuery.each(jQuery(cell).closest('table').find('.occs-body').find('.col-'+colidx+' .count-input'), function(idx, collCell) {
    cellValue = parseInt(jQuery(collCell).val());
    if (!isNaN(cellValue)) {
      total += cellValue;
    }
  });
  jQuery(table).find('td.col-total.col-'+colidx).html(total);
}

function addSpeciesToGrid(occurrenceSpecies, taxonList, speciesTableSelector, force, tabIDX){
  // this function is given a list of species from the occurrences and if they are in the taxon list
  // adds them to a table in the order they are in that taxon list
  // any that are left are swept up by another function.
  jQuery.each(taxonList, function(idx, species) {
    var found = force;
    if(!found){
      jQuery.each(occurrenceSpecies, function(idx, occ){
        // taxonList may or may not be preferred
        // Occ has both a ttl_id and a preferred
        if(occ['processed']!==true && occ['taxon_meaning_id']===species['taxon_meaning_id'])
          found=true;
      });
    }
    if (found) {
      addGridRow(species, speciesTableSelector, true, tabIDX);
    }
  });
}

function addGridRow(species, speciesTableSelector, end, tabIDX){
  var name, val;
  if (species.default_common_name!==null) {
    name = species.default_common_name
  } else if (species.preferred_language_iso==='lat') {
    name = '<em>'+species.taxon+'</em>';
  } else {
    name = species.taxon;
  }
  var rowCount = jQuery(speciesTableSelector+' tbody').find('tr').length;
  var rowclass = rowCount%2===0 ? '' : ' class="alt-row"';
  var row = jQuery('<tr id="row-' + species.taxon_meaning_id + '"' + rowclass + '/>');
  jQuery('<td>'+name+'</td>').appendTo(row);
  var rowTotal = 0;
  var isNumber = indiciaData.occurrence_attribute_ctrl[tabIDX].attr('class').indexOf('number:true')>=0; // TBD number:true
  jQuery.each(indiciaData.sections, function(idx, section) {
    if (typeof section.total==="undefined") {
      section.total = [];
    }
    if (typeof section.total[speciesTableSelector]==="undefined") {
        section.total[speciesTableSelector]=0;
    }
    // find current value if there is one - the key is the combination of sample id and ttl meaning id that an existing value would be stored as
    var key=indiciaData.samples[section.code] + ':' + species.taxon_meaning_id;
    var cell = jQuery('<td class="col-'+(idx+1)+(idx % 5 == 0 ? ' first' : '')+'"/>').appendTo(row);
    // row += '<input class="count-input" id="value:'+species.id+':'+section.code+'" type="text" value="'+val+'" /></td>';
    // actual control has to be first in cell for cursor keys to work.
    var myCtrl = indiciaData.occurrence_attribute_ctrl[tabIDX].clone();
    myCtrl.appendTo(cell);
    if (typeof indiciaData.existingOccurrences[key]!=="undefined") {
      indiciaData.existingOccurrences[key]['processed']=true;
      val = indiciaData.existingOccurrences[key]['value_'+indiciaData.occurrence_attribute[tabIDX]] === null ? '' : indiciaData.existingOccurrences[key]['value_'+indiciaData.occurrence_attribute[tabIDX]];
      if (isNumber && val!=='') {
        rowTotal += parseInt(val);
        section.total[speciesTableSelector] += parseInt(val);
      }
      // need to use existing species ttlid (which may or may not be preferred)
      myCtrl.attr('id', 'value:'+indiciaData.existingOccurrences[key]['ttl_id']+':'+section.code).attr('name', '');
      jQuery('<input type="hidden" id="value:'+indiciaData.existingOccurrences[key]['ttl_id']+':'+section.code+':attrId" value="'+indiciaData.occurrence_attribute[tabIDX]+'"/>').appendTo(cell);
      // store the ids of the occurrence and attribute we loaded, so future changes to the cell can overwrite the existing records
      jQuery('<input type="hidden" id="value:'+indiciaData.existingOccurrences[key]['ttl_id']+':'+section.code+':id" value="'+indiciaData.existingOccurrences[key]['o_id']+'"/>').appendTo(cell);
      jQuery('<input type="hidden" id="value:'+indiciaData.existingOccurrences[key]['ttl_id']+':'+section.code+':attrValId" value="'+indiciaData.existingOccurrences[key]['a_id_'+indiciaData.occurrence_attribute[tabIDX]]+'"/>').appendTo(cell);
    } else {
      // this is always the preferred when generated from full list, may be either if from autocomplete.
      myCtrl.attr('id', 'value:'+species.id+':'+section.code).attr('name', '');
      jQuery('<input type="hidden" id="value:'+species.id+':'+section.code+':attrId" value="'+indiciaData.occurrence_attribute[tabIDX]+'"/>').appendTo(cell);
      val='';
    }
    if(isNumber) myCtrl.addClass('count-input');
    else myCtrl.addClass('non-count-input');
    myCtrl.val(val);
  });
  if(isNumber) jQuery('<td class="row-total first">'+rowTotal+'</td>').appendTo(row);
  if(end) {
    jQuery(speciesTableSelector+' tbody.occs-body').append(row);
  } else {
    jQuery(speciesTableSelector+' tbody.occs-body').prepend(row);
  }
  row.find('.count-input').keydown(count_keydown).focus(count_focus).change(input_change).blur(input_blur);
  row.find('input.non-count-input').keydown(count_keydown).focus(count_focus).change(input_change).blur(input_blur);
  row.find('select.non-count-input').focus(count_focus).change(select_change);
}

function smp_keydown(evt) {
  var targetRow = [], targetInput=[], code, parts=evt.target.id.split(':'), type='smpAttr';
  code=parts[2];
  if (evt.keyCode===13 || evt.keyCode===40) {
    targetRow = jQuery(evt.target).parents('tr').next('tr');
    if (targetRow.length===0) {
      // moving out of sample attributes area into next tbody for counts
      targetRow = jQuery(evt.target).parents('tbody').next('tbody').find('tr:first');
      type='value';
    }
    if (targetRow.length>0) {
      targetInput = targetRow.find('input[id^='+type+'\\:][id$=\\:'+code+']');
    }
  }

  if (evt.keyCode===39 && evt.target.selectionEnd >= evt.target.value.length) {
    targetInput = jQuery(evt.target).parents('td').next('td').find('input');
    if (targetInput.length===0) {
      // end of row, so move down to next if there is one
      targetRow = jQuery(evt.target).parents('tr').next('tr');
      if (targetRow.length===0) {
        // moving out of sample attributes area into next tbody for counts
        targetRow = jQuery(evt.target).parents('tbody').next('tbody').find('tr:first');
      }
      if (targetRow.length>0) {
        targetInput = targetRow.find('input:visible:first');
      }
    }
  }
  // left arrow - move to previous cell if at start of text
  if (evt.keyCode===37 && evt.target.selectionStart === 0) {
    targetInput = jQuery(evt.target).parents('td').prev('td').find('input');
    if (targetInput.length===0) {
      // before start of row, so move up to previous if there is one
      targetRow = jQuery(evt.target).parents('tr').prev('tr');
      if (targetRow.length>0) {
        targetInput = targetRow.find('input:visible:last');
      }
    }
  }
  if (targetInput.length > 0) {
    jQuery(targetInput).get()[0].focus();
    return false;
  }
}

// Not all events can be bound using live() - which is deprecated for later versions of jQuery anyway.
// Define event handlers.
// TBC this should be OK to use as is.
function count_keydown (evt) {
  var targetRow = [], targetInput=[], code, parts=evt.target.id.split(':'), type='value';
  code=parts[2]; // holds the section code

  // down arrow or enter key
  if (evt.keyCode===13 || evt.keyCode===40) {
    targetRow = jQuery(evt.target).parents('tr').next('tr');
  }
  // up arrow
  if (evt.keyCode===38) {
    targetRow = jQuery(evt.target).parents('tr').prev('tr');
    if (targetRow.length===0) {
      // moving out of counts area into previous tbody for sample attributes
      targetRow = jQuery(evt.target).parents('tbody').prev('tbody').find('tr:last');
      type='smpAttr';
    }
  }
  if (targetRow.length>0) {
    targetInput = targetRow.find('input[id^='+type+'\\:][id$=\\:'+code+']');
  }
  // right arrow - move to next cell if at end of text
  if (evt.keyCode===39 && evt.target.selectionEnd >= evt.target.value.length) {
    targetInput = jQuery(evt.target).parents('td').next('td').find('input');
    if (targetInput.length===0) {
      // end of row, so move down to next if there is one
      targetRow = jQuery(evt.target).parents('tr').next('tr');
      if (targetRow.length>0) {
        targetInput = targetRow.find('input:visible:first');
      }
    }
  }
  // left arrow - move to previous cell if at start of text
  if (evt.keyCode===37 && evt.target.selectionStart === 0) {
    targetInput = jQuery(evt.target).parents('td').prev('td').find('input');
    if (targetInput.length===0) {
      // before start of row, so move up to previous if there is one
      targetRow = jQuery(evt.target).parents('tr').prev('tr');
      if (targetRow.length===0) {
        // moving out of counts area into previous tbody for sample attributes
        targetRow = jQuery(evt.target).parents('tbody').prev('tbody').find('tr:last');
      }
      if (targetRow.length>0) {
        targetInput = targetRow.find('input:visible:last');
      }
    }
  }
  if (targetInput.length > 0) {
    jQuery(targetInput).get()[0].focus();
    return false;
  }
};

function count_focus (evt) {
  // select the row
  var matches = jQuery(evt.target).parents('td:first')[0].className.match(/col\-\d+/);
  var colidx = matches[0].substr(4);
  jQuery(evt.target).parents('table:first').find('.table-selected').removeClass('table-selected');
  jQuery(evt.target).parents('table:first').find('.ui-state-active').removeClass('ui-state-active');
  jQuery(evt.target).parents('tr:first').addClass('table-selected');
  jQuery(evt.target).parents('table:first').find('tbody .col-'+colidx).addClass('table-selected');
  jQuery(evt.target).parents('table:first').find('thead .col-'+colidx).addClass('ui-state-active');
};

function input_change (evt) {
  jQuery(evt.target).addClass('edited');
};

function select_change (evt) {
  jQuery(evt.target).addClass('edited');
  input_blur(evt);
};

function input_blur (evt) {
  var selector = '#'+evt.target.id.replace(/:/g, '\\:');
  indiciaData.currentCell = evt.target.id;
  getTotal(evt.target);
  if (jQuery(selector).hasClass('edited')) {
    jQuery(selector).addClass('saving');
    if (jQuery(selector).hasClass('count-input')) {
      // check for number input - don't post if not a number
      if (!jQuery(selector).val().match(/^[0-9]*$/)) {
        alert('Please enter a valid number - '+evt.target.id);
        // use a timer, as refocus during blur not reliable.
        setTimeout("jQuery('#"+evt.target.id+"').focus(); jQuery('#"+evt.target.id+"').select()", 100);
        return;
      }
    } else {
      jQuery(selector).val(jQuery(selector).val().toUpperCase());
    }
    if (jQuery(selector).hasClass('count-input') || jQuery(selector).hasClass('non-count-input')) {
      // need to save the occurrence for the current cell
      // set the taxa_taxon_list_id, which we can extract from part of the id of the input.
      var parts=evt.target.id.split(':');
      jQuery('#ttlid').val(parts[1]);
      if (typeof indiciaData.samples[parts[2]] !== "undefined") {
        jQuery('#occ_sampleid').val(indiciaData.samples[parts[2]]);
      } else {
        alert('Occurrence could not be saved because of a missing sample ID');
        return;
      }

      // store the actual abundance value we want to save.
      jQuery('#occzero').val('f');
      jQuery('#occdeleted').val('f');
      if (jQuery(selector).val()==='0') {
        jQuery('#occzero').val('t');
      }
      if (jQuery(selector).val()==='') {
        jQuery('#occdeleted').val('t');
        jQuery('#occattr').val('0');
      } else {
        jQuery('#occattr').val(jQuery(selector).val());
      }
      // does this cell already have an occurrence?
      if (jQuery(selector +'\\:id').length>0) {
        jQuery('#occid').val(jQuery(selector +'\\:id').val());
        jQuery('#occid').attr('disabled', false);
        jQuery('#occSensitive').attr('disabled', true); // existing ID - leave sensitivity as is
      } else {
        // if no existing occurrence, we must not post the occurrence:id field.
        jQuery('#occid').attr('disabled', true);
        jQuery('#occSensitive').attr('disabled', false); // new data - use location sensitivity
      }
      if (jQuery(selector +'\\:attrValId').length===0) {
        // by setting the attribute field name to occAttr:n where n is the occurrence attribute id, we will get a new one
        jQuery('#occattr').attr('name', 'occAttr:' + jQuery(selector +'\\:attrId').val());
      } else {
        // by setting the attribute field name to occAttr:n:m where m is the occurrence attribute value id, we will update the existing one
        jQuery('#occattr').attr('name', 'occAttr:' + jQuery(selector +'\\:attrId').val() + ':' + jQuery(selector +'\\:attrValId').val());
      }
      // store the current cell's ID as a transaction ID, so we know which cell we were updating.
      jQuery('#transaction_id').val(evt.target.id);
      if (jQuery(selector +'\\:id').length>0 || jQuery('#occdeleted').val()==='f') {
        jQuery('#occ-form').submit();
      }
      // if deleting, then must remove the occurrence ID
      if (jQuery('#occdeleted').val()==='t') {
        jQuery(selector +'\\:id').remove();
        jQuery(selector +'\\:attrValId').remove();
      }
    } else if (jQuery(selector).hasClass('smp-input')) {
      // change to just a sample attribute.
      var parts=evt.target.id.split(':');
      saveSample(parts[2]);
    }
  }
};

function loadSpeciesList() {
  // redo alt-row classes
  var redo_alt_row = function (table) {
    var rowCount = 0;
    jQuery(table + ' tbody').find('tr').each(function(){
      if(rowCount%2===0)
        jQuery(this).removeClass('alt-row');
      else
        jQuery(this).addClass('alt-row');
      rowCount++;
    });
  }

  indiciaData.currentCell=null;
  // first add any data recorded, then populate the tables with any blank rows required. There is a hierarchy: if data is in more than one species list, it is added 
  // to the first grid it appears in.
  // note that when added from the list, the ttlid is the preferred one, but if added from the autocomplete it may/probably
  // will not be.
  var list = indiciaData.startWithCommonSpecies ? indiciaData.speciesList1SubsetList : indiciaData.speciesList1List;
  addSpeciesToGrid(indiciaData.existingOccurrences, list, 'table#transect-input1', true, 1);
  // get all taxon meanings recorded on this transect
  var process2 = function () {
    var process3 = function () {
      var process4 = function () {
        if(indiciaData.speciesList4>0){
          var TaxonData = {
                'taxon_list_id': indiciaData.speciesList4,
                'preferred': 't',
                'auth_token': indiciaData.readAuth.auth_token,
                'nonce': indiciaData.readAuth.nonce,
                'mode': 'json',
                'allow_data_entry': 't',
                'view': 'cache',
                'orderby': 'taxonomic_sort_order'
          };
          var query = {};
          if(!indiciaData.speciesList4Force)
            query = {"in":{"taxon_meaning_id":indiciaData.allTaxonMeaningIdsAtTransect}};
          if(typeof indiciaData.speciesList4FilterField != "undefined") {
            if(typeof query['in'] == 'undefined') query = {"in":{}};
            query['in'][indiciaData.speciesList4FilterField] = indiciaData.speciesList4FilterValues;
          }
          TaxonData.query = JSON.stringify(query);
          jQuery.ajax({
                'url': indiciaData.indiciaSvc+'index.php/services/data/taxa_taxon_list',
                'data': TaxonData,
                'dataType': 'jsonp',
                'success': function(data) {
              addSpeciesToGrid(indiciaData.existingOccurrences, data, 'table#transect-input4', indiciaData.speciesList4Force, 4);
              // copy across the col totals
              jQuery.each(indiciaData.sections, function(idx, section) {
                jQuery('table#transect-input4 tfoot .col-total.col-'+(idx+1)).html(typeof section.total['table#transect-input4']==="undefined" ? 0 : section.total['table#transect-input4']);
              });
              jQuery('#grid4-loading').remove();
            }
          });
        }
      }; // end of function process4
      if(indiciaData.speciesList3>0){
        var TaxonData = {
              'taxon_list_id': indiciaData.speciesList3,
              'preferred': 't',
              'auth_token': indiciaData.readAuth.auth_token,
              'nonce': indiciaData.readAuth.nonce,
              'mode': 'json',
              'allow_data_entry': 't',
              'view': 'cache',
              'orderby': 'taxonomic_sort_order'
        };
        var query = {};
        if(!indiciaData.speciesList3Force)
          query = {"in":{"taxon_meaning_id":indiciaData.allTaxonMeaningIdsAtTransect}};
        if(typeof indiciaData.speciesList3FilterField != "undefined") {
          if(typeof query['in'] == 'undefined') query = {"in":{}};
          query['in'][indiciaData.speciesList3FilterField] = indiciaData.speciesList3FilterValues;
        }
        TaxonData.query = JSON.stringify(query);
        jQuery.ajax({
              'url': indiciaData.indiciaSvc+'index.php/services/data/taxa_taxon_list',
              'data': TaxonData,
              'dataType': 'jsonp',
              'success': function(data) {
            addSpeciesToGrid(indiciaData.existingOccurrences, data, 'table#transect-input3', indiciaData.speciesList3Force, 3);
            // copy across the col totals
            jQuery.each(indiciaData.sections, function(idx, section) {
              jQuery('table#transect-input3 tfoot .col-total.col-'+(idx+1)).html(typeof section.total['table#transect-input3']==="undefined" ? 0 : section.total['table#transect-input3']);
            });
            jQuery('#grid3-loading').remove();
            process4();
          }
        });
      } else process4();
    }; // end of function process3
    if(indiciaData.speciesList2>0){
      var TaxonData = {
            'taxon_list_id': indiciaData.speciesList2,
            'preferred': 't',
            'auth_token': indiciaData.readAuth.auth_token,
            'nonce': indiciaData.readAuth.nonce,
            'mode': 'json',
            'allow_data_entry': 't',
            'view': 'cache',
            'orderby': 'taxonomic_sort_order'
      };
      var query = {};
      if(!indiciaData.speciesList2Force)
        query = {"in":{"taxon_meaning_id":indiciaData.allTaxonMeaningIdsAtTransect}};
      if(typeof indiciaData.speciesList2FilterField != "undefined") {
        if(typeof query['in'] == 'undefined') query = {"in":{}};
        query['in'][indiciaData.speciesList2FilterField] = indiciaData.speciesList2FilterValues;
      }
      TaxonData.query = JSON.stringify(query);
      jQuery.ajax({
            'url': indiciaData.indiciaSvc+'index.php/services/data/taxa_taxon_list',
            'data': TaxonData,
            'dataType': 'jsonp',
            'success': function(data) {
          addSpeciesToGrid(indiciaData.existingOccurrences, data, 'table#transect-input2', indiciaData.speciesList2Force, 2);
          // copy across the col totals
          jQuery.each(indiciaData.sections, function(idx, section) {
            jQuery('table#transect-input2 tfoot .col-total.col-'+(idx+1)).html(typeof section.total['table#transect-input2']==="undefined" ? 0 : section.total['table#transect-input2']);
          });
          jQuery('#grid2-loading').remove();
          process3();
        }
      });
    } else process3();
  }; // end of function process2

  // copy across the col totals
  jQuery.each(indiciaData.sections, function(idx, section) {
    jQuery('table#transect-input1 tfoot .col-total.col-'+(idx+1)).html(typeof section.total['table#transect-input1']==="undefined" ? 0 : section.total['table#transect-input1']);
  });
  // the main grid is populated with blank rows by the full list by default (the true in the addSpeciesToGrid call)
  // the other 2 grids are to be populated with rows (blank or otherwise) by whatever has been recorded at this site previously.
  // Have to have existing meaning ids rloaded and list 1 completed first.
  process2();
  if (!indiciaData.startWithCommonSpecies)
    jQuery('#taxonLookupControlContainer').hide();
  jQuery('#listSelect').change(function(evt) {
    jQuery('#taxonLookupControlContainer').show();
    jQuery('#listSelectMsg').empty().append('Please Wait...');
    jQuery('table#transect-input1 .table-selected').removeClass('table-selected');
    jQuery('table#transect-input1 .ui-state-active').removeClass('ui-state-active');
    // first remove all blank rows.
    jQuery('table#transect-input1 .occs-body').find('tr').each(function(idx, row){
      if(jQuery(row).find('input').not(':hidden').not('[value=]').length == 0)
        jQuery(row).remove();
    });
    switch(jQuery(this).val()){
      case 'filled':
        redo_alt_row('table#transect-input1');
        break;
      case 'full':
        jQuery('#taxonLookupControlContainer').hide();
        // want them in the order of speciesList1List: push onto top new or existing rows in reverse order.
        // leaves any not on list at bottom.
        for(var i=indiciaData.speciesList1List.length-1; i>=0; i--){
          if(jQuery('#row-'+indiciaData.speciesList1List[i]['taxon_meaning_id']).length==0)
            addGridRow(indiciaData.speciesList1List[i], 'table#transect-input1', false, 1);
          else
            jQuery('table#transect-input1 tbody.occs-body').prepend(jQuery('#row-'+indiciaData.speciesList1List[i]['taxon_meaning_id']));
        }
        redo_alt_row('table#transect-input1');
        break;
      case 'common':
        for(var i=indiciaData.speciesList1List.length-1; i>=0; i--){
          if(jQuery('#row-'+indiciaData.speciesList1List[i]['taxon_meaning_id']).length>0)
            jQuery('table#transect-input1 tbody.occs-body').prepend(jQuery('#row-'+indiciaData.speciesList1List[i]['taxon_meaning_id']));
          else {
            for(var j=0; j<indiciaData.speciesList1SubsetList.length; j++){
              if(indiciaData.speciesList1List[i]['taxon_meaning_id'] == indiciaData.speciesList1SubsetList[j]['taxon_meaning_id']) {
                addGridRow(indiciaData.speciesList1List[i], 'table#transect-input1', false, 1);
                break;
              }
            }
          }
        }
        redo_alt_row('table#transect-input1');
        break;
      case 'here':
        for(var j=0; j<indiciaData.allTaxonMeaningIdsAtTransect.length; j++){
          var last = false, me;
          // we assume that existing data in grid is in taxanomic order
          if(jQuery('#row-'+indiciaData.allTaxonMeaningIdsAtTransect[j]).length==0){ // not on list already
            for(var i=0; i<indiciaData.speciesList1List.length; i++){
              if(indiciaData.allTaxonMeaningIdsAtTransect[j] == indiciaData.speciesList1List[i].taxon_meaning_id) {
                addGridRow(indiciaData.speciesList1List[i], 'table#transect-input1', false, 1);
                if(last) jQuery('#row-'+indiciaData.speciesList1List[i]['taxon_meaning_id']).insertAfter(last)
                break;
              } else {
                me = jQuery('#row-'+indiciaData.speciesList1List[i]['taxon_meaning_id']);
                if(me.length>0) last = me;
              }
            }
          }
        }
        redo_alt_row('table#transect-input1');
        break;
      case 'mine':
        // get all species on samples that I have recorded.
        if(indiciaData.easyLogin === true){
          // here we just get the occurrences I have created.
          jQuery.ajax({
               'url': indiciaData.indiciaSvc+'index.php/services/data/occurrence',
               'data': {
                 'created_by_id': indiciaData.UserID,
                 'auth_token': indiciaData.readAuth.auth_token,
                 'nonce': indiciaData.readAuth.nonce,
                 'mode': 'json',
                 'view': 'detail'
               },
               'dataType': 'jsonp',
               'success': function(odata) {
                   for(var j=0; j<odata.length; j++){
                     var last = false, me;
                     // we assume that existing data in grid is in taxanomic order
                     if(jQuery('#row-'+odata[j]['taxon_meaning_id']).length==0){ // not on list already
                       for(var i=0; i<indiciaData.speciesList1List.length; i++){
                         if(odata[j].taxon_meaning_id == indiciaData.speciesList1List[i].taxon_meaning_id) {
                           addGridRow(indiciaData.speciesList1List[i], 'table#transect-input1', false, 1);
                           if(last)
                             jQuery('#row-'+indiciaData.speciesList1List[i]['taxon_meaning_id']).insertAfter(last);
                           break;
                         } else {
                           me = jQuery('#row-'+indiciaData.speciesList1List[i]['taxon_meaning_id']);
                           if(me.length>0) last = me;
                         }
                       }
                     }
                   }
                   redo_alt_row('table#transect-input1');
               }});
        } else {
          jQuery.ajax({
            'url': indiciaData.indiciaSvc+'index.php/services/data/sample_attribute_value',
            'data': {
              'sample_attribute_id': indiciaData.CMSUserAttrID,
              'raw_value': indiciaData.CMSUserID,
              'auth_token': indiciaData.readAuth.auth_token,
              'nonce': indiciaData.readAuth.nonce,
              'mode': 'json'
            },
            'dataType': 'jsonp',
            'success': function(savdata) {
                // next get all transect section subsamples
                var sampleList = [];
                for(var i=0; i<savdata.length; i++)
                  sampleList.push(savdata[i].sample_id);
                jQuery.ajax({
                    'url': indiciaData.indiciaSvc+'index.php/services/data/sample',
                    'data': {
                      'query': JSON.stringify({'in': {'parent_id': sampleList}}),
                      'auth_token': indiciaData.readAuth.auth_token,
                      'nonce': indiciaData.readAuth.nonce,
                      'mode': 'json',
                      'view': 'detail'
                    },
                    'dataType': 'jsonp',
                    'success': function(ssdata) {
                    // finally get all occurrences
                    var subSampleList = [];
                    for(var i=0; i<ssdata.length; i++)
                      subSampleList.push(ssdata[i].id);
                      jQuery.ajax({
                          'url': indiciaData.indiciaSvc+'index.php/services/data/occurrence',
                          'data': {
                              'query': JSON.stringify({'in': {'sample_id': subSampleList}}),
                              'auth_token': indiciaData.readAuth.auth_token,
                              'nonce': indiciaData.readAuth.nonce,
                              'mode': 'json',
                              'view': 'detail'
                          },
                          'dataType': 'jsonp',
                          'success': function(odata) {
                              for(var j=0; j<odata.length; j++){
                                var last = false, me;
                                // we assume that existing data in grid is in taxanomic order
                                if(jQuery('#row-'+odata[j]['taxon_meaning_id']).length==0){ // not on list already
                                  for(var i=0; i<indiciaData.speciesList1List.length; i++){
                                    if(odata[j].taxon_meaning_id == indiciaData.speciesList1List[i].taxon_meaning_id) {
                                      addGridRow(indiciaData.speciesList1List[i], 'table#transect-input1', false, 1);
                                      if(last)
                                        jQuery('#row-'+indiciaData.speciesList1List[i]['taxon_meaning_id']).insertAfter(last)
                                      break;
                                    } else {
                                      me = jQuery('#row-'+indiciaData.speciesList1List[i]['taxon_meaning_id']);
                                      if(me.length>0) last = me;
                                    }
                                  }
                                }
                              }
                              redo_alt_row('table#transect-input1');
                      }});
                }});
          }});
        }
        break;
    }
    jQuery('#listSelectMsg').empty();
  });

  jQuery('.smp-input').keydown(smp_keydown).change(input_change).blur(input_blur).focus(count_focus);

  function checkErrors(data) {
    if (typeof data.error!=="undefined") {
      if (typeof data.errors!=="undefined") {
        jQuery.each(data.errors, function(idx, error) {
          alert(error);
        });
      } else {
        alert('An error occured when trying to save the data');
      }
      // data.transaction_id stores the last cell at the time of the post.
      var selector = '#'+data.transaction_id.replace(/:/g, '\\:');
      jQuery(selector).focus();
      jQuery(selector).select();
      return false;
    } else {
      return true;
    }
  }

  jQuery('#occ-form').ajaxForm({
    async: true,
    dataType:  'json',
    success:   function(data, status, form){
      var selector = '#'+data.transaction_id.replace(/:/g, '\\:');
      jQuery(selector).removeClass('saving');
      if (checkErrors(data)) {
        if(jQuery(selector).val() != '') { // if the selector is blank, we are deleting the entry, so we do not want to add the id and attrValId fields (they will have just been removed!)
          if (jQuery(selector +'\\:id').length===0) {
            // this is a new occurrence, so keep a note of the id in a hidden input
            jQuery(selector).after('<input type="hidden" id="'+data.transaction_id +':id" value="'+data.outer_id+'"/>');
          }
          if (jQuery(selector +'\\:attrValId').length===0) {
            // this is a new attribute, so keep a note of the id in a hidden input
            jQuery(selector).after('<input type="hidden" id="'+data.transaction_id +':attrValId" value="'+data.struct.children[0].id+'"/>');
          }
        }
        jQuery(selector).removeClass('edited');
      }
    }
  });

  jQuery('#smp-form').ajaxForm({
    // must be synchronous, otherwise currentCell could change.
    async: false,
    dataType:  'json',
    complete: function() {
      var selector = '#'+indiciaData.currentCell.replace(/:/g, '\\:');
      jQuery(selector).removeClass('saving');
    },
    success: function(data){
      if (checkErrors(data)) {
        // get the sample code from the id of the cell we are editing.
        var parts = indiciaData.currentCell.split(':');
        // we cant just check if we are going to create new attributes and fetch in this case to get the attribute ids -
        // there is a possibility we have actually deleted an existing attribute, in which the id must be removed. This can only be
        // found out by going to the database. We can't keep using the deleted attribute as it stays deleted (ie does not undelete)
        // if a new value is saved into it.
        jQuery.each(jQuery('.smpAttr-'+parts[2]), function(idx, input) {
          // an attr value that is not saved yet is of form smpAttr:attrId, whereas one that is saved
          // is of form smpAttr:attrId:attrValId. Wo we can count colons to know if it exists already.
          if (jQuery(input).attr('name').split(':').length<=2) {
            jQuery(input).removeClass('edited'); // deliberately left in place for changed old attributes.
          }
        });
        // We need to copy over the information so that future changes update the existing record rather than
        // create new ones, or creates a new one if we have deleted the attribute
        // The response from the warehouse (data parameter) only includes the IDs of the attributes it created.
        // We need all the attributes.
        jQuery.getJSON(indiciaData.indiciaSvc + "index.php/services/data/sample_attribute_value" +
              "?mode=json&view=list&sample_id=" + data.outer_id + "&auth_token=" + indiciaData.readAuth.auth_token + "&nonce=" + indiciaData.readAuth.nonce + "&callback=?", function(data) {
              // There is a possibility that we have just deleted an attribute (in which case it will not be in the data), so reset all the names first.
                jQuery.each(data, function(idx, attr) {
                  jQuery('#smpAttr\\:'+attr.sample_attribute_id+'\\:'+parts[2]).attr('name', 'smpAttr:'+attr.sample_attribute_id+(parseInt(attr.id)==attr.id ? ':'+attr.id : ''));
                  // we know - parts[2] = S2
                  // attr.sample_attribute_id & attr.id
                  // src control id=smpAttr:1:S2 (smpAttr:sample_attribute_id:sectioncode)
                  // need to change src control name to
                });
              }
        );
      }
    }
  });
};
// autocompletes assume ID
function bindSpeciesAutocomplete(selectorID, tableSelectorID, url, lookupListId, lookupListFilterField, lookupListFilterValues, readAuth, duplicateMsg, max, tabIDX) {
  // inner function to handle a selection of a taxon from the autocomplete
  var handleSelectedTaxon = function(event, data) {
    if(jQuery('#row-'+data.taxon_meaning_id).length>0){
      alert(duplicateMsg);
      jQuery(event.target).val('');
      return;
    }
    addGridRow(data, tableSelectorID, true, tabIDX);
    jQuery(event.target).val('');
    var table = jQuery(tableSelectorID);
    table.parent().find('.sticky-header').remove();
    table.find('thead.tableHeader-processed').removeClass('tableHeader-processed');
    table.removeClass('tableheader-processed');
    table.addClass('sticky-enabled');
    if(typeof Drupal.behaviors.tableHeader == 'object') // Drupal 7
      Drupal.behaviors.tableHeader.attach(table.parent());
    else // Drupal6 : it is a function
      Drupal.behaviors.tableHeader(table.parent());
  };
  var extra_params = {
        view : 'cache',
        orderby : 'taxon',
        mode : 'json',
        qfield : 'taxon',
        auth_token: readAuth.auth_token,
        nonce: readAuth.nonce,
        taxon_list_id: lookupListId
  };
  if(typeof lookupListFilterField != "undefined"){
    extra_params.query = '{"in":{"'+lookupListFilterField+'":'+JSON.stringify(lookupListFilterValues)+"}}";
  };

  // Attach auto-complete code to the input
  var ctrl = jQuery('#' + selectorID).autocomplete(url+'/taxa_taxon_list', {
      extraParams : extra_params,
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
  setTimeout(function() { jQuery('#' + ctrl.attr('id')).focus(); });
}

function bindRecorderNameAutocomplete(attrID, userID, baseurl, surveyID, token, nonce) {
  jQuery('#smpAttr\\:'+attrID).autocomplete(baseurl+'/index.php/services/report/requestReport', {
      extraParams : {
        mode : 'json',
        report : 'reports_for_prebuilt_forms/UKBMS/ukbms_recorder_names.xml',
        reportSource : 'local',
        qfield : 'name',
        auth_token: token,
        attr_id : attrID,
        survey_id : surveyID,
        user_id : userID,
        nonce: nonce
      },
      max: 50,
      mustMatch : false,
      parse: function(data) {
        var results = [];
        jQuery.each(data, function(i, item) {
          results[results.length] = {'data' : item,'result' : item.name,'value' : item.name};
        });
        return results;
      },
      formatItem: function(item) {return item.name;}
  });
};


