
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
        if(occ['processed']!==true && (occ['ttl_id']==species['id'] || occ['preferred_ttl_id']==species['id']))
          found=true;
      });
    }
    if(found)
      addGridRow(species, speciesTableSelector, true, tabIDX);
  });
}

function addGridRow(species, speciesTableSelector, end, tabIDX){
  var name, val, existing = false;
  if (species.default_common_name!==null) {
    name = species.default_common_name
  } else if (species.preferred_language_iso==='lat') {
    name = '<em>'+species.taxon+'</em>';
  } else {
    name = species.taxon;
  }
  var row = jQuery('<tr id="row-' + species.taxon_meaning_id + '"' + ((jQuery(speciesTableSelector+' tbody').find('tr').length)%2===0 ? '' : ' class="alt-row"') + '/>');
  if(end)
    jQuery(speciesTableSelector+' tbody.occs-body').append(row);
  else
    jQuery(speciesTableSelector+' tbody.occs-body').prepend(row);
  if (typeof indiciaData.existingOccurrences[species.taxon_meaning_id]!=="undefined") {
    existing = true;
    indiciaData.existingOccurrences[species.taxon_meaning_id]['processed']=true;
    jQuery('<td>'+name+'<input type="hidden" id="value:'+species.id+':occid" value="'+indiciaData.existingOccurrences[species.taxon_meaning_id]['o_id']+'" /></td>').appendTo(row);
  } else jQuery('<td>'+name+'</td>').appendTo(row);
  var rowTotal = 0;
  for(var idx = 0; idx < indiciaData.occurrence_attribute.length; idx++) {
    var isNumber = indiciaData.occurrence_attribute_ctrl[idx].attr('class').indexOf('number:true')>=0,
        id = 'value:'+species.id+':occAttr:'+indiciaData.occurrence_attribute[idx],
        name = 'value:'+species.id+':occAttr:'+indiciaData.occurrence_attribute[idx]+':',
        val = '', valId;
    if (typeof indiciaData.occurrence_totals[idx] === "undefined")
    	indiciaData.occurrence_totals[idx] = {};
    if (typeof indiciaData.occurrence_totals[idx][speciesTableSelector] === "undefined")
    	indiciaData.occurrence_totals[idx][speciesTableSelector]=0;
    // find current value if there is one
    var cell = jQuery('<td class="col-'+(idx+1)+(idx % 5 == 0 ? ' first' : '')+'"/>').appendTo(row);
    if (existing) {
      val = indiciaData.existingOccurrences[species.taxon_meaning_id]['value_'+indiciaData.occurrence_attribute[idx]] === null ? '' :
          indiciaData.existingOccurrences[species.taxon_meaning_id]['value_'+indiciaData.occurrence_attribute[idx]];
      if (isNumber && val!=='') {
        rowTotal += parseInt(val);
        indiciaData.occurrence_totals[idx][speciesTableSelector] += parseInt(val);
      }
      valId=indiciaData.existingOccurrences[species.taxon_meaning_id]['a_id_'+indiciaData.occurrence_attribute[idx]];
      if (valId!==null) {
        name = name+indiciaData.existingOccurrences[species.taxon_meaning_id]['a_id_'+indiciaData.occurrence_attribute[idx]];
      }
    }
    indiciaData.occurrence_attribute_ctrl[idx].clone().attr('id', id).attr('name', name).val(val).addClass(isNumber ? 'count-input' : 'non-count-input').appendTo(cell);
  }
  jQuery('<td class="row-total first">'+rowTotal+'</td>').appendTo(row);
  row.find('.count-input').keydown(count_keydown).focus(count_focus).change(input_change).blur(input_blur);
  row.find('.non-count-input').focus(count_focus).change(select_change);
}

// Not all events can be bound using live() - which is deprecated for later versions of jQuery anyway.
// Define event handlers.
// TBC this should be OK to use as is.
function count_keydown (evt) {
  var targetRow = [], targetInput=[], code, attrID, parts=evt.target.id.split(':'), type='value';
  code=parts[2];
  attrID=parts[3];

  // down arrow or enter key
  if (evt.keyCode===13 || evt.keyCode===40) {
    targetRow = jQuery(evt.target).parents('tr').next('tr');
  }
  // up arrow
  if (evt.keyCode===38) {
    targetRow = jQuery(evt.target).parents('tr').prev('tr');
  }
  if (targetRow.length>0) {
    targetInput = targetRow.find('input[id^='+type+'\\:][id$=\\:'+code+'\\:'+attrID+']');
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
    }
    if (jQuery(selector).hasClass('count-input') || jQuery(selector).hasClass('non-count-input')) {
      // need to save the occurrence for the current cell
      // set the taxa_taxon_list_id, which we can extract from part of the id of the input.
      var parts=evt.target.id.split(':');
      jQuery('#ttlid').val(parts[1]);
      // store the actual abundance value we want to save. Use 0 instead of blank
      // since required for deletions
      if (jQuery(selector).hasClass('count-input') && jQuery(selector).val()==='') {
        jQuery(selector).val('0');
      }
      jQuery('#occattr').val(jQuery(selector).val());
      // multiple attributes per occurrence: never delete the occurrence
      // does this cell already have an occurrence?
      if (jQuery('#value\\:'+parts[1] +'\\:occid').length>0) {
        jQuery('#occid').val(jQuery('#value\\:'+parts[1] +'\\:occid').val());
        jQuery('#occid').attr('disabled', false);
      } else {
        // if no existing occurrence, we must not post the occurrence:id field.
        jQuery('#occid').attr('disabled', true);
      }
      parts=jQuery(selector).attr('name').split(':');
      if (parts[4]=='') {
        // by setting the attribute field name to occAttr:n where n is the occurrence attribute id, we will get a new one
        jQuery('#occattr').attr('name', 'occAttr:' + parts[3]);
      } else {
        // by setting the attribute field name to occAttr:n:m where m is the occurrence attribute value id, we will update the existing one
        jQuery('#occattr').attr('name', 'occAttr:' + parts[3] + ':' + parts[4]);
      }
      // store the current cell's ID as a transaction ID, so we know which cell we were updating.
      jQuery('#transaction_id').val(evt.target.id);
      jQuery('#occ-form').submit();
      if (jQuery(selector).val()==='0' || jQuery(selector).val()==='') {
        jQuery(selector).attr('name', evt.target.id+':');
      }
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
  // first add any data recorded, then populate the tables with any blank rows required. There is a heirarchy: if data is in more than one species list, it is added 
  // to the first grid it appears in.
  // note that when added from the list, the ttlid is the preferred one, but if added from the autocomplete it may/probably
  // will not be.
  addSpeciesToGrid(indiciaData.existingOccurrences, indiciaData.speciesList1List, 'table#observation-input1', true, 1);
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
          var query = {"in":{"taxon_meaning_id":indiciaData.allTaxonMeaningIdsAtSample}};
          if(typeof indiciaData.speciesList4FilterField != "undefined")
            query['in'][indiciaData.speciesList4FilterField] = indiciaData.speciesList4FilterValues;
          TaxonData.query = JSON.stringify(query);
          jQuery.ajax({
                'url': indiciaData.indiciaSvc+'index.php/services/data/taxa_taxon_list',
                'data': TaxonData,
                'dataType': 'jsonp',
                'success': function(data) {
              addSpeciesToGrid(indiciaData.existingOccurrences, data, 'table#observation-input4', false, 4);
              // copy across the col totals
              for(var idx = 0; idx < indiciaData.occurrence_attribute.length; idx++) {
                jQuery('table#observation-input4 tfoot .col-total.col-'+(idx+1)).html(typeof indiciaData.occurrence_totals[idx]['table#observation-input4']==="undefined" ? 0 : indiciaData.occurrence_totals[idx]['table#observation-input4']);
              }
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
        var q = indiciaData.speciesList3FilterField;
        if(typeof q != "undefined")
          query = {"in":{q : indiciaData.speciesList3FilterValues}};
        TaxonData.query = JSON.stringify(query);
        jQuery.ajax({
              'url': indiciaData.indiciaSvc+'index.php/services/data/taxa_taxon_list',
              'data': TaxonData,
              'dataType': 'jsonp',
              'success': function(data) {
            addSpeciesToGrid(indiciaData.existingOccurrences, data, 'table#observation-input3', true, 3);
            // copy across the col totals
            for(var idx = 0; idx < indiciaData.occurrence_attribute.length; idx++) {
              jQuery('table#observation-input3 tfoot .col-total.col-'+(idx+1)).html(typeof indiciaData.occurrence_totals[idx]['table#observation-input3']==="undefined" ? 0 : indiciaData.occurrence_totals[idx]['table#observation-input3']);
            }
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
      var q = indiciaData.speciesList2FilterField;
      if(typeof q != "undefined")
        query = {"in":{q : indiciaData.speciesList2FilterValues}};
      TaxonData.query = JSON.stringify(query);
      jQuery.ajax({
            'url': indiciaData.indiciaSvc+'index.php/services/data/taxa_taxon_list',
            'data': TaxonData,
            'dataType': 'jsonp',
            'success': function(data) {
          addSpeciesToGrid(indiciaData.existingOccurrences, data, 'table#observation-input2', true, 2);
          // copy across the col totals
          for(var idx = 0; idx < indiciaData.occurrence_attribute.length; idx++) {
            jQuery('table#observation-input2 tfoot .col-total.col-'+(idx+1)).html(typeof indiciaData.occurrence_totals[idx]['table#observation-input2']==="undefined" ? 0 : indiciaData.occurrence_totals[idx]['table#observation-input2']);
          }
          jQuery('#grid2-loading').remove();
          process3();
        }
      });
    } else process3();
  }; // end of function process2

  // copy across the col totals
  for(var idx = 0; idx < indiciaData.occurrence_attribute.length; idx++) {
    jQuery('table#observation-input1 tfoot .col-total.col-'+(idx+1)).html(typeof indiciaData.occurrence_totals[idx]['table#observation-input1']==="undefined" ? 0 : indiciaData.occurrence_totals[idx]['table#observation-input1']);
  }
  // the main grid is populated with blank rows by the full list by default (the true in the addSpeciesToGrid call)
  // the other 2 grids are to be populated with rows (blank or otherwise) by whatever has been recorded at this site previously.
  // Have to have existing meaning ids rloaded and list 1 completed first.
  process2();

  jQuery('#taxonLookupControlContainer').hide();

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
      if (checkErrors(data)) {
        var selector = '#'+data.transaction_id.replace(/:/g, '\\:');
        jQuery(selector).removeClass('saving');
        // skip deletions
        if (jQuery(selector).val()!=='0' && jQuery(selector).val()!=='') {
          var parts = data.transaction_id.split(':');
          jQuery(selector).attr('name', parts[0]+':'+parts[1]+':'+parts[2]+':'+parts[3]+':'+data.struct.children[0].id);
          if(jQuery('#value\\:'+parts[1]+'\\:occid').length==0)
            jQuery(selector).after('<input type="hidden" id="'+'value:'+parts[1]+':occid" value="'+data.outer_id+'"/>');
        }
        jQuery(selector).removeClass('edited');
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
            'value' : item.taxa_taxon_list_id
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


