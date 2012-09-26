/**
 * Creates the sample required for a section, if it does not exist yet. Otherwise updates it.
 */
function createNewSample(code, force) {
  if (typeof indiciaData.samples[code] !== "undefined") {
    // if saving a change to an occurrence and the sample already exists, don't update it.
    if (!force) {
      return;
    }
    $('#smpid').val(indiciaData.samples[code]);
  } else {
    $('#smpid').val('');
  }
  
  $.each(indiciaData.sections, function(idx, section) {
    if (section.code==code) {
      // copy the fieldname and value into the sample submission form for each sample custom attribute
      $.each($('.smpAttr-' + section.code), function(idx, src) {
        $('#'+src.id.substr(0, src.id.length-3).replace(/:/g, '\\:')).val($(src).val());
        $('#'+src.id.substr(0, src.id.length-3).replace(/:/g, '\\:')).attr('name', $(src).attr('name'));
      });
      $('#smpsref').val(section.centroid_sref);
      $('#smpsref_system').val(section.centroid_sref_system);
      $('#smploc').val(section.id);
      $('#smp-form').submit();
    }
  });
}

$(document).ready(function() {
  $('#imp-location').change(function(evt) {
    $('#entered_sref').val(indiciaData.sites[evt.target.value].centroid_sref);
    $('#entered_sref_system').val(indiciaData.sites[evt.target.value].centroid_sref_system);
  });
});

function getTotal(cell) {
  var row=$(cell).parents('tr:first')[0]
  // get the total for the column
  var total=0, cellValue;
  $.each($(row).find('.count-input'), function(idx, cell) {
    cellValue = parseInt($(cell).val());
    if (!isNaN(cellValue)) {
      total += cellValue;
    }
  });
  $(row).find('.row-total').html(total);
  // get the total for the row
  var matches = $(cell).parents('td:first')[0].className.match(/col\-\d/);
  var colidx = matches[0].substr(4);
  total = 0;
  $.each($(cell).parents('table:first tbody').find('.col-'+colidx+' .count-input'), function(idx, collCell) {
    cellValue = parseInt($(collCell).val());
    if (!isNaN(cellValue)) {
      total += cellValue;
    }
  });
  $('td.col-total.col-'+colidx).html(total);
}

function loadSpeciesList() {
  var currentCell=null, submittingSample='';

  $.ajax({
    'url': indiciaData.indiciaSvc+'index.php/services/data/taxa_taxon_list',
    'data': {
        'taxon_list_id': indiciaData.initSpeciesList,
        'preferred': 't',
        'auth_token': indiciaData.readAuth.auth_token,
        'nonce': indiciaData.readAuth.nonce,
        'mode': 'json',
        'allow_data_entry': 't'
    },
    'dataType': 'jsonp',
    'success': function(data) {
      var name, row, rowclass, val, key, tmp, rowTotal, rowCount=$('table#transect-input tbody').children('tr').length;
      $.each(data, function(idx, species) {
        if (species.common!==null) {
          name = species.common
        } else if (species.language==='lat') {
          name = '<em>'+species.preferred_name+'</em>';
        } else {
          name = species.preferred_name;
        }
        rowclass = rowCount%2===0 ? '' : ' class="alt-row"';
        row = '<tr id="row-' + species.id + '"' + rowclass + '><td>'+name+'</td>';
        rowCount +=1;
        rowTotal = 0;
        $.each(indiciaData.sections, function(idx, section) {
          if (typeof section.total==="undefined") {
            section.total = 0;
          }
          // find current value if there is one - the key is the combination of sample id and ttl id that an existing value would be stored as
          key=indiciaData.samples[section.code] + ':' + species.id;
          row += '<td class="col-'+(idx+1)+'">';
          if (typeof indiciaData.existingOccurrences[key]!=="undefined") {
            val = indiciaData.existingOccurrences[key]['value'] === null ? '' : indiciaData.existingOccurrences[key]['value'];
            if (val!=='') {
              rowTotal += parseInt(val);
              section.total += parseInt(val);
            }
            // store the ids of the occurrence and attribute we loaded, so future changes to the cell can overwrite the existing records
            row += '<input type="hidden" id="value:'+species.id+':'+section.code+':id" value="'+indiciaData.existingOccurrences[key]['o_id']+'"/>';
            row += '<input type="hidden" id="value:'+species.id+':'+section.code+':attrId" value="'+indiciaData.existingOccurrences[key]['a_id']+'"/>';
          } else {
            val='';
          }
          row += '<input class="count-input" id="value:'+species.id+':'+section.code+'" type="text" value="'+val+'" /></td>';
        });
        row += '<td class="row-total">'+rowTotal+'</td>';
        row += '</tr>';
        $('table#transect-input tbody#occs-body').append(row);
      });
      // copy across the col totals
      $.each(indiciaData.sections, function(idx, section) {
        $('tfoot .col-total.col-'+(idx+1)).html(section.total);
      });

      $('.count-input').keydown(function (evt) {
        var targetRow = [], code, parts=evt.target.id.split(':');
        code=parts[2];

        // down arrow or enter key
        if (evt.keyCode===13 || evt.keyCode===40) {
          targetRow = $(evt.target).parents('tr').next('tr');
        }
        // up arrow
        if (evt.keyCode===38) {
          targetRow = $(evt.target).parents('tr').prev('tr');
        }
        var targetInput = [];
        if (targetRow.length>0) {
          targetInput = $('#value\\:' + targetRow[0].id.substr(4) + '\\:' + code);
        }        
        // right arrow - move to next cell if at end of text
        if (evt.keyCode===39 && evt.target.selectionEnd >= evt.target.value.length) {
          targetInput = $(evt.target).parents('td').next('td').find('input');
          if (targetInput.length===0) {
            // end of row, so move down to next if there is one
            targetRow = $(evt.target).parents('tr').next('tr');
            if (targetRow.length>0) {
              targetInput = targetRow.find('input.count-input:first');
            }
          }
        }
        // left arrow - move to previous cell if at start of text
        if (evt.keyCode===37 && evt.target.selectionStart === 0) {
          targetInput = $(evt.target).parents('td').prev('td').find('input');
          if (targetInput.length===0) {
            // before start of row, so move up to previous if there is one
            targetRow = $(evt.target).parents('tr').prev('tr');
            if (targetRow.length>0) {
              targetInput = targetRow.find('input:last');
            }
          }
        }
        if (targetInput.length > 0) {
          $(targetInput).get()[0].focus();
          return false;
        }
      });
      $('.count-input').focus(function(evt) {
        // select the row
        var matches = $(evt.target).parents('td:first')[0].className.match(/col\-\d/);
        var colidx = matches[0].substr(4);
        $(evt.target).parents('table:first').find('.table-selected').removeClass('table-selected');
        $(evt.target).parents('table:first').find('.ui-state-active').removeClass('ui-state-active');
        $(evt.target).parents('tr:first').addClass('table-selected');
        $(evt.target).parents('table:first').find('tbody .col-'+colidx).addClass('table-selected');
        $(evt.target).parents('table:first').find('thead .col-'+colidx).addClass('ui-state-active');
      });
      $('.count-input,.smp-input').change(function(evt) {
        $(evt.target).addClass('edited');
      });
      $('.count-input,.smp-input').blur(function(evt) {        
        var selector = '#'+evt.target.id.replace(/:/g, '\\:');
        currentCell = evt.target.id;
        getTotal(evt.target);
        if ($(selector).hasClass('edited')) {
          $(selector).addClass('saving');
          if ($(selector).hasClass('count-input')) {
            // check for number input - don't post if not a number
            if (!$(selector).val().match(/^[0-9]*$/)) {
              alert('Please enter a valid number - '+evt.target.id);
              // use a timer, as refocus during blur not reliable.
              setTimeout("$('#"+evt.target.id+"').focus(); $('#"+evt.target.id+"').select()", 100);
              return;
            }
            // need to save the sample/occurrence for the current cell
            // set the taxa_taxon_list_id, which we can extract from part of the id of the input.
            var parts=evt.target.id.split(':');
            $('#ttlid').val(parts[1]);
            createNewSample(parts[2], false);
            if (typeof indiciaData.samples[parts[2]] !== "undefined") {
              $('#occ_sampleid').val(indiciaData.samples[parts[2]]);
            } else {
              alert('Occurrence could not be saved because of a missing sample ID');
              return;
            }

            // store the actual abundance value we want to save.
            $('#occattr').val($(selector).val());
            // does this cell already have an occurrence?
            if ($(selector +'\\:id').length>0) {
              $('#occid').val($(selector +'\\:id').val());
              $('#occid').attr('disabled', false);
            } else {
              // if no existing occurrence, we must not post the occurrence:id field.
              $('#occid').attr('disabled', true);
            }
            if ($(selector +'\\:attrId').length===0) {
              // by setting the attribute field name to occAttr:n where n is the occurrence attribute id, we will get a new one
              $('#occattr').attr('name', 'occAttr:' + indiciaData.occAttrId);
            } else {
              // by setting the attribute field name to occAttr:n:m where m is the occurrence attribute value id, we will update the existing one
              $('#occattr').attr('name', 'occAttr:' + indiciaData.occAttrId + ':' + $(selector +'\\:attrId').val());
            }
            // store the current cell's ID as a transaction ID, so we know which cell we were updating.
            $('#transaction_id').val(evt.target.id);
            $('#occ-form').submit();
          } else if ($(selector).hasClass('smp-input')) {
            // change to just a sample attribute.
            var parts=evt.target.id.split(':');
            createNewSample(parts[2], true);
          }
        }
      });
    }
  });

  function checkErrors(data) {
    if (typeof data.error!=="undefined") {
      if (typeof data.errors!=="undefined") {
        $.each(data.errors, function(idx, error) {
          alert(error);
        });
      } else {
        alert('An error occured when trying to save the data');
      }
      // data.transaction_id stores the last cell at the time of the post.
      var selector = '#'+data.transaction_id.replace(/:/g, '\\:');
      $(selector).focus();
      $(selector).select();
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
        $(selector).removeClass('saving');
        if ($(data.transaction_id +'\\:id').length===0) {
          // this is a new occurrence, so keep a note of the id in a hidden input
          $(selector).after('<input type="hidden" id="'+data.transaction_id +'\\:id" value="'+data.outer_id+'"/>');
        }
        if ($(selector +'\\:attrId').length===0) {
          // this is a new attribute, so keep a note of the id in a hidden input
          $(selector).after('<input type="hidden" id="'+data.transaction_id +'\\:attrId" value="'+data.struct.children[0].id+'"/>');
        }

        $(selector).removeClass('edited');
      }
    }
  });

  jQuery('#smp-form').ajaxForm({
    // must be synchronous, otherwise currentCell could change.
    async: false,
    dataType:  'json',
    complete: function() {
      var selector = '#'+currentCell.replace(/:/g, '\\:');
      $(selector).removeClass('saving');
    },
    success: function(data){
      if (checkErrors(data)) {
        // get the sample code from the id of the cell we are editing, so we can remember the sample id.
        parts = currentCell.split(':');
        // remember the ID
        indiciaData.samples[parts[2]] = data.outer_id;
        // find out if any of our sample controls in the grid are for new attribute values
        var needIdsForNewAttrs = false;
        $.each($('.smpAttr-'+parts[2]), function(idx, input) {
          // an attr value that is not saved yet is of form smpAttr:attrId, whereas one that is saved
          // is of form smpAttr:attrId:attrValId. Wo we can count colons to know if it exists already.
          if ($(input).attr('name').split(':').length<=2) {
            needIdsForNewAttrs = true;
            $(input).removeClass('edited');
          }
        });
        if (needIdsForNewAttrs) {
          // this is a new sample. So we need to copy over the information so that future changes update the existing record rather than
          // create new ones. The response from the warehouse only includes the IDs of the attributes it created.
          var children=[], query;
          $.each(data.struct.children, function(idx, child) {
            children.push(child.id);
          });
          query = encodeURIComponent('{"in":{"id":['+children.join(',')+']}}');
          $.getJSON(indiciaData.indiciaSvc + "index.php/services/data/sample_attribute_value" +
              "?mode=json&view=list&query=" + query + "&auth_token=" + indiciaData.readAuth.auth_token + "&nonce=" + indiciaData.readAuth.nonce + "&callback=?", function(data) {
                $.each(data, function(idx, attr) {
                  $('#smpAttr\\:'+attr.sample_attribute_id+'\\:'+parts[2]).attr('name', 'smpAttr:'+attr.sample_attribute_id+':'+attr.id);
                  // we know - parts[2] = S2
                  // attr.sample_attribute_id & attr.id
                  // src control id=smpAttr:1:S2 (smpAttr:sample_attribute_id:sectioncode)
                  // need to change src control name to
                });
              }
          );
        }
      }
    }
  });
};