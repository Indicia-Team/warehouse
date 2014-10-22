var changeComplexGridRowCount;

jQuery(document).ready(function($) {
  "use strict"; 
  
  function updateUniqueSelects(table) {
    var
      attrName=table.id.replace('complex-attr-grid-', '').split('-'),
      attrTypeTag=attrName[0], attrId=attrName[1],
      gridDef=indiciaData['complexAttrGrid-'+attrTypeTag+'-'+attrId];
    $.each(gridDef.cols, function(idx, col) {
      if (col.datatype==='lookup' && typeof col.validation!=='undefined' && $.inArray('unique', col.validation)>=0) {
        // got a select column with a unique validation rule defined. Let's strip the used options
        var selects = $(table).find('td:nth-child('+(idx+1) +') select'), values=[];
        // Find the values in use
        $.each(selects, function() {
          if ($(this).val()!=='') {
            values.push($(this).val());
          }
        });
        $.each(selects, function(i, select) {
          $.each($(select).find('option'), function(j, option) {
            if ($(option).attr('value')==='' || $(option).attr('value')===$(select).val() || $.inArray($(option).attr('value'), values)===-1) {
              $(option).show();
            } else {
              $(option).hide();
            }
          });
        });
        
      }
    });
  }
  
  function addRowToTable(table) {
    var
      attrName=table.id.replace('complex-attr-grid-', '').split('-'),
      attrTypeTag=attrName[0], attrId=attrName[1],
      row='<tr>',
      gridDef=indiciaData['complexAttrGrid-'+attrTypeTag+'-'+attrId],
      fieldname;
    
    gridDef.rowCount++; 
    $.each(gridDef.cols, function(idx, def) {
      fieldname = attrTypeTag+"+:"+attrId+"::"+(gridDef.rowCount-1)+":"+idx;
      row += '<td>';
      if (def.datatype==='lookup' && typeof def.control!=="undefined" && def.control==='checkbox_group') {
        var checkboxes=[];
        $.each(indiciaData['tl'+def.termlist_id], function(idx, term) {
          checkboxes.push('<input title="'+term+'" type="checkbox" name="'+fieldname+'[]" value="'+term[0]+':' + term[1] + '">');
        });
        row += checkboxes.join('</td><td>');
      } else if (def.datatype==='lookup') {
        row += '<select name="'+fieldname+'"><option value="">&lt;'+indiciaData.langPleaseSelect+'&gt;</option>';
        $.each(indiciaData['tl'+idx], function(idx, term) {
          row += '<option value="'+term[0]+':' + term[1] + '">'+term[1]+'</option>';
        });
        row += '</select>';
      } else {
        row += '<input type="text" name="'+fieldname+'" id="'+fieldname+'"/>';
      }
      if (typeof def.unit!=="undefined" && def.unit!=="") {
        row += '<span class="unit">'+def.unit+'</span>';
      }
      row += '</td>';
    });
    fieldname = attrTypeTag+"+:"+attrId+"::"+gridDef.rowCount+":deleted";
    row += '<td><input type="hidden" name="'+fieldname+'" value="f" class="delete-flag"/>';
    if (gridDef.rowCountControl==='') {
      row += '<span class="ind-delete-icon"/>';
    }
    row += '</td></tr>';
    $(table).find('tbody').append(row);
    $(table).find('tbody tr:last-child select').change(function() {updateUniqueSelects(table);});
  }
  
  changeComplexGridRowCount = function(countCtrlId, attrTypeTag, attrId) {
    var rowCount=$('#'+countCtrlId).val();
    if ($('#complex-attr-grid-'+attrTypeTag+'-'+attrId+' tbody tr').length>rowCount) {
      $.each($('#complex-attr-grid-'+attrTypeTag+'-'+attrId+' tbody tr'), function(idx, row) {
        // remove only empty rows
        if ($(row).find(":input:visible[value!='']").not(':checkbox').length+$(row).find(":checkbox:checked").length===0) {
          $(row).remove();
        }
      });
      if ($('#complex-attr-grid-'+attrTypeTag+'-'+attrId+' tbody tr').length>rowCount) {
        $('#'+countCtrlId).val($('#complex-attr-grid-'+attrTypeTag+'-'+attrId+' tbody tr').length);
        alert(indiciaData.langCantRemoveEnoughRows);
      }
    }
    if ($('#complex-attr-grid-'+attrTypeTag+'-'+attrId+' tbody tr').length<rowCount) {
      while ($('#complex-attr-grid-'+attrTypeTag+'-'+attrId+' tbody tr').length<rowCount) {
        addRowToTable($('#complex-attr-grid-'+attrTypeTag+'-'+attrId)[0]);
      }
    }
  };
  
  $('table.complex-attr-grid .add-btn').click(function(e) {
    var table=$(e.currentTarget).closest('table')[0];
    addRowToTable(table);
  });

  $('table.complex-attr-grid tbody').click(function(e) {
    // e.target is the actual thing clicked on inside the tbody
    if ($(e.target).hasClass('ind-delete-icon')) {
      var row=$(e.target).closest('tr')[0];
      if(gridDef['deleteRows']) {// option to remove rather than disable row when "x" clicked
         newTarget=$(e.target).closest('table')[0],// find parent table before deleting row
         attrName=table.id.replace('complex-attr-grid-', '').split('-'),
         attrTypeTag=attrName[0], attrId=attrName[1],
         gridDef=indiciaData['complexAttrGrid-'+attrTypeTag+'-'+attrId];
         $(row).remove();
    }
        else
{
      $(row).css('opacity', 0.4);
      $(row).find('input').css('text-decoration', 'line-through');
      $(row).find('.delete-flag').val('t');
      $(row).find('input').not(':hidden').attr('disabled', true);
}
      updateUniqueSelects($(e.target).closest('table')[0]);
    }
  });
  
  $('table.complex-attr-grid select').change(function(e) {
    updateUniqueSelects($(e.target).closest('table')[0]);
  });

});