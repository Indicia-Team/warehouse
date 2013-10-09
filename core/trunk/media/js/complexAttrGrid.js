$(document).ready(function($) {
  "use strict"; 
  
  $('table.complex-attr-grid .add-btn').click(function(e) {
    var table=$(e.currentTarget).closest('table')[0],
      attrName=table.id.replace('complex-attr-grid-', '').split('-'),
      attrTypeTag=attrName[0], attrId=attrName[1],
      row='<tr>',
      gridDef=indiciaData['complexAttrGrid-'+attrTypeTag+'-'+attrId],
      fieldname;
    
    gridDef.rowCount++; 
    $.each(gridDef.cols, function(name, def) {
      fieldname = attrTypeTag+"+:"+attrId+"::"+gridDef.rowCount+":"+name;
      row += '<td>';
      if (def.datatype==='lookup') {
        row += '<select name="'+fieldname+'"><option value="">'+indiciaData.langPleaseSelect+'</option>';
        $.each(indiciaData['tl'+def.termlist_id], function(idx, term) {
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
    row += '<td><input type="hidden" name="'+fieldname+'" value="f" class="delete-flag"/><span class="ind-delete-icon"/></td></tr>';
    $(table).find('tbody').append(row);
  });
  
  $('table.complex-attr-grid tbody').click(function(e) {
    // e.target is the actual thing clicked on inside the tbody
    if ($(e.target).hasClass('ind-delete-icon')) {
      var row=$(e.target).closest('tr')[0];
      $(row).css('opacity', 0.4);
      $(row).find('input').css('text-decoration', 'line-through');
      $(row).find('.delete-flag').val('t');
      $(row).find('input').not(':hidden').attr('disabled', true);
    }
  });

});