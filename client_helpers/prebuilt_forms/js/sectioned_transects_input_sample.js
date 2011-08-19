function loadSpeciesList() {
  var lastCell=null, lastCellValue = '', submittingSample='';

  /**
   * Creates the sample required for a section, if it does not exist yet.
   * Returns true if a sample was created. False if it already existed.
   */
  function createNewSample(code) {
    if (typeof indiciaData.samples[code] === "undefined") {
      // no sample yet created for this section, so create it.
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
      return true;
    } else {
      return false;
    }
  }

  $.ajax({
    'url': 'http://localhost/indicia/index.php/services/data/taxa_taxon_list',
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
      var name, row, rowclass, val, key, tmp, rowCount=$('table#transect-input tbody').children('tr').length;
      $.each(data, function(idx, species) {
        if (species.language==='lat') {
          name = '<em>'+species.preferred_name+'</em>';
        } else {
          name = '<em>'+species.preferred_name+'</em>';
        }
        if (species.common!==null && species.common!==species.preferred_name)
          name += ' - ' + species.common;
        rowclass = rowCount%2===0 ? '' : ' class="alt-row"';
        row = '<tr id="row-' + species.id + '"' + rowclass + '><td>'+name+'</td>';
        rowCount +=1;
        $.each(indiciaData.sections, function(idx, section) {
          // find current value if there is one - the key is the combination of sample id and ttl id that an existing value would be stored as
          key=indiciaData.samples[section.code] + ':' + species.id;
          row += '<td>';
          if (typeof indiciaData.existingOccurrences[key]!=="undefined") {
            val=indiciaData.existingOccurrences[key]['value'];
            // store the ids of the occurrence and attribute we loaded, so future changes to the cell can overwrite the existing records
            row += '<input type="hidden" id="value-'+species.id+'-'+section.code+'-id" value="'+indiciaData.existingOccurrences[key]['o_id']+'"/>';
            row += '<input type="hidden" id="value-'+species.id+'-'+section.code+'-attrId" value="'+indiciaData.existingOccurrences[key]['a_id']+'"/>';
          } else {
            val='';
          }
          row += '<input class="count-input" id="value-'+species.id+'-'+section.code+'" type="text" value="'+val+'" /></td>';
        });
        row += '</tr>';
        $('table#transect-input tbody#occs-body').append(row);
      });

      $('.count-input').keypress(function (evt) {
        var targetRow = [], code, parts=evt.target.id.split('-');
        code=parts[2];

        // down arrow or enter key
        if (evt.keyCode===13 || evt.keyCode===40) {
          targetRow = $(evt.target).parents('tr').next('tr');
        }
        // up arrow
        if (evt.keyCode===38) {
          targetRow = $(evt.target).parents('tr').prev('tr');
        }
        if (targetRow.length>0) {
          $('#value-' + targetRow[0].id.substr(4) + '-' + code).focus();
        }
        var targetInput = [];
        // right arrow - move to next cell if at end of text
        if (evt.keyCode===39 && evt.target.selectionEnd >= evt.target.textLength) {
          targetInput = $(evt.target).parents('td').next('td').find('input');
          if (targetInput.length===0) {
            // end of row, so move down to next if there is one
            targetRow = $(evt.target).parents('tr').next('tr');
            if (targetRow.length>0) {
              targetRow.find('input.count-input:first').focus();
              return false;
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
              targetRow.find('input:last').focus();
              return false;
            }
          }
        }
        if (targetInput.length > 0) {
          targetInput.focus();
          return false;
        }
      });
      $('.count-input,.smp-input').keyup(function(evt) {
        if (lastCellValue!=$(evt.target).val() && lastCell===evt.target.id) {
          // cell content changed, so mark it with a class
          $(evt.target).addClass('edited');
        }
      });
      $('.count-input,.smp-input').focus(function(evt) {
        if (typeof lastCell!=="undefined" && lastCell!==null && lastCell!==this.id) {
          var selector = '#'+lastCell.replace(/:/g, '\\:');
          if ($(selector).hasClass('edited')) {
            $(selector).addClass('saving');
            if ($(selector).hasClass('count-input')) {
              // check for number input - don't post if not a number
              if (!$(selector).val().match(/^[0-9]*$/)) {
                alert('Please enter a valid number - '+lastCell);
                // use a timer, as refocus during focus not reliable.
                setTimeout("$('#"+lastCell+"').focus(); $('#"+lastCell+"').select()", 100);
                return;
              }
              // need to save the sample/occurrence for the current cell
              // set the taxa_taxon_list_id, which we can extract from part of the id of the input.
              var parts=lastCell.split('-');
              $('#ttlid').val(parts[1]);
              createNewSample(parts[2]);
              if (typeof indiciaData.samples[parts[2]] !== "undefined") {
                $('#occ_sampleid').val(indiciaData.samples[parts[2]]);
              } else {
                alert('sample could not be saved');
              }

              // store the actual abundance value we want to save.
              $('#occattr').val($(selector).val());
              // does this cell already have an occurrence?
              if ($(selector +'-id').length>0) {
                $('#occid').val($(selector +'-id').val());
                $('#occid').attr('disabled', false);
              } else {
                // if no existing occurrence, we must not post the occurrence:id field.
                $('#occid').attr('disabled', true);
              }
              if ($(selector +'-attrId').length===0) {
                // by setting the attribute field name to occAttr:n where n is the occurrence attribute id, we will get a new one
                $('#occattr').attr('name', 'occAttr:' + indiciaData.occAttrId);
              } else {
                // by setting the attribute field name to occAttr:n:m where m is the occurrence attribute value id, we will update the existing one
                $('#occattr').attr('name', 'occAttr:' + indiciaData.occAttrId + ':' + $(selector +'-attrId').val());
              }
              // store the current cell's ID as a transaction ID, so we know which cell we were updating.
              $('#transaction_id').val(lastCell);
              $('#occ-form').submit();
            } else if ($(selector).hasClass('smp-input')) {
              // change to just a sample attribute.
              var parts=lastCell.split(':');
              if (!createNewSample(parts[2])) {
                alert('need to update an attr');
              };
            }
          }
        }
        lastCell=this.id;
        lastCellValue=$(this).val();
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
      var selector = '#'+data.transaction_id.replace(/:/g, '\\:');
      $(selector).removeClass('saving');
      if (checkErrors(data)) {

        if ($(data.transaction_id +'-id').length===0) {
          // this is a new occurrence, so keep a note of the id in a hidden input
          $(selector).after('<input type="hidden" id="'+data.transaction_id +'-id" value="'+data.outer_id+'"/>');
        }
        if ($(selector +'-attrId').length===0) {
          // this is a new attribute, so keep a note of the id in a hidden input
          $(selector).after('<input type="hidden" id="'+data.transaction_id +'-attrId" value="'+data.struct.children[0].id+'"/>');
        }

        $(selector).removeClass('edited');
      }
    }
  });

  jQuery('#smp-form').ajaxForm({
    // must be synchronous, otherwise lastCell could change.
    async: false,
    dataType:  'json',
    complete: function() {
      var selector = '#'+lastCell.replace(/:/g, '\\:');
      $(selector).removeClass('saving');
    },
    success: function(data){
      if (checkErrors(data)) {
        // get the sample code from the id of the cell we are editing, so we can remember the sample id.
        parts = lastCell.split('-');
        if (typeof indiciaData.samples[parts[2]]==="undefined") {
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
          indiciaData.samples[parts[2]] = data.outer_id;
        }
      }
    }
  });
};