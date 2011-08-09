function loadSpeciesList() {
  var lastCell=null, lastCellValue = '';
  $.ajax({
    'url': 'http://localhost/indicia/index.php/services/data/taxa_taxon_list',
    'data': {
        'taxon_list_id': indiciaData.initSpeciesList,
        'auth_token': indiciaData.readAuth.auth_token,
        'nonce': indiciaData.readAuth.nonce,
        'mode': 'json'
    },
    'dataType': 'jsonp',
    'success': function(data) {
      var name, row, rowclass;
      $.each(data, function(idx, species) {
        if (species.language==='lat') {
          name = '<em>'+species.preferred_name+'</em>';
        } else {
          name = '<em>'+species.preferred_name+'</em>';
        }
        if (species.common!==null && species.common!==species.preferred_name)
          name += ' - ' + species.common;
        rowclass = idx%2===0 ? '' : ' class="alt-row"';
        row = '<tr id="row-' + species.id + '"' + rowclass + '><td>'+name+'</td>';
        $.each(indiciaData.sections, function(idx, section) {
          row += '<td><input class="count-input" id="value-'+species.id+'-'+section.code+'" type="text"/></td>';
        });
        row += '</tr>';
        $('table#transect-input tbody').append(row);        
      });
      $('.count-input').keypress(function (evt) {
        var targetRow = [];
        // down arrow or enter key
        if (evt.keyCode===13 || evt.keyCode===40) {
          targetRow = $(evt.target).parents('tr').next('tr');
        }
        // up arrow
        if (evt.keyCode===38) {
          targetRow = $(evt.target).parents('tr').prev('tr');
        }        
        if (targetRow.length>0) {
          $('#value-' + targetRow[0].id.substr(4) + '-S1').focus();
        }
        var targetInput = [];
        // right arrow - move to next cell if at end of text
        if (evt.keyCode===39 && evt.target.selectionEnd >= evt.target.textLength) {
          targetInput = $(evt.target).parents('td').next('td').find('input');
          if (targetInput.length===0) {
            // end of row, so move down to next if there is one
            targetRow = $(evt.target).parents('tr').next('tr');
            if (targetRow.length>0) {
              targetRow.find('input:first').focus();
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
      $('.count-input').focus(function(evt) {
        if (lastCell!==null && lastCellValue !== $('#'+ lastCell).val()) {
          // need to save the sample/occurrence for the current cell
          /* Things we need.          
          Sample id of parent. - indiciaData.transect
          Is there already a sample for this column?
            no? 
              Post one, with a link to the correct location id for the section, looked up from indiciaData.sections.
              then store the sample ID as indiciaData.sectionSamples{code:sample_id}
          yes - get the sample id
          is there already an occurrence for this column?
            no? create one with attribute for abundance.
          need ability to reload and edit a transect walk
          $.post(
          );*/
          // store the abundance Id
          $('#occattr').val($('#'+ lastCell).val());
          // set the taxa_taxon_list_id, which we can extract from part of the id of the input.
          var parts=lastCell.split('-');
          $('#ttlid').val(parts[1]);
          
          if (typeof indiciaData.samples[parts[2]] === "undefined") {
            // no sample yet created for this section, so create it.
            $.each(indiciaData.sections, function(idx, section) {
              if (section.code==parts[2]) {
                $('#smpsref').val(section.centroid_sref);
                $('#smpsref_system').val(section.centroid_sref_system);
                $('#smploc').val(section.id);
                $('#smp-form').submit();
              }
            });
          }
          if (typeof indiciaData.samples[parts[2]] !== "undefined") {
            $('#occ_sampleid').val(indiciaData.samples[parts[2]]);
          } else {
            throw new exception('sample could not be saved');
          }
          
          // store the actual abundance value we want to save.
          $('#occattr').val($('#'+ lastCell).val());
          // does this cell already have an occurrence?
          if ($('#'+lastCell +'-id').length>0) {
            $('#occid').val($('#'+lastCell +'-id').val());
            $('#occid').attr('disabled', false);
          } else {
            // if no existing occurrence, we must not post the occurrence:id field.
            $('#occid').attr('disabled', true);
          }
          if ($('#'+lastCell +'-attrId').length===0) {
            // by setting the attribute field name to occAttr:n where n is the occurrence attribute id, we will get a new one
            $('#occattr').attr('name', 'occAttr:' + indiciaData.occAttrId);
          } else {
            // by setting the attribute field name to occAttr:n:m where m is the occurrence attribute value id, we will update the existing one
            $('#occattr').attr('name', 'occAttr:' + indiciaData.occAttrId + ':' + $('#'+lastCell +'-attrId').val());
          }
          // remember which input is being saved.
          indiciaData.currentInput = lastCell;
          $('#occ-form').submit();
        }
        lastCell = evt.target.id;
        lastCellValue = $(evt.target).val();
      });
    }
  });
  
  jQuery('#occ-form').ajaxForm({ 
    async: true,
    dataType:  'json', 
    success:   function(data){
      if (typeof data.error!=="undefined") {
        alert('An error occured when trying to save the data');
      }
      if ($('#'+indiciaData.currentInput +'-id').length===0) {
        // this is a new occurrence, so keep a note of the id in a hidden input
        $('#'+indiciaData.currentInput ).after('<input type="hidden" id="'+indiciaData.currentInput +'-id" value="'+data.outer_id+'"/>');
      }
      if ($('#'+indiciaData.currentInput +'-attrId').length===0) {
        // this is a new attribute, so keep a note of the id in a hidden input
        $('#'+indiciaData.currentInput).after('<input type="hidden" id="'+indiciaData.currentInput +'-attrId" value="'+data.struct.children[0].id+'"/>');
      }
    }
  });
  
  jQuery('#smp-form').ajaxForm({ 
    async: false,
    dataType:  'json', 
    success:   function(data){
      if (typeof data.error!=="undefined") {
        alert('An error occured when trying to save the data');
      }
      // get the sample code from the id of the cell we are editing, so we can remember the sample id.
      parts = lastCell.split('-');
      indiciaData.samples[parts[2]] = data.outer_id;
    }
  });  
};