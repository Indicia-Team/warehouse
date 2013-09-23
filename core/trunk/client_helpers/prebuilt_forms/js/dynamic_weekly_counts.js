jQuery(document).ready(function($) {
  "use strict";

  if ($('input[name=sample\\:date]').length>0) {
    alert('Please remove the date control from this form - the date is calculated from the grid weeks.');
  }
  function totaliseRow(row) {
    var max=0;
    $(row).find('.weeks').html($(row).find('input').filter(function() {
      return parseInt($(this).val(), 10) > 0;
    }).length);
    $.each($(row).find('td input'), function(idx, input) {
      if (parseInt($(input).val(), 10) > max) {
        max=parseInt($(input).val(), 10);
      } 
    });
    $(row).find('.max').html(max);
  }
  
  function totaliseCol(cell) {
    $('.species-totals .' + cell.className).html($('.' + cell.className + ' input').filter(function() {
      return parseInt($(this).val(), 10) > 0;
    }).length);
  }
  
  $('.count-input').change(function(e) {
    var data={}, ctrl = e.target, cell = $(ctrl).parents('td')[0], row = $(ctrl).parents('tr')[0];
    // strip non-numerics
    $(ctrl).val($(ctrl).val().replace(/[^\d]/g, ''));
    // store all ctrl values in a json field. Otherwise there are too many to post.
    $.each($('.count-input[value!=""]'), function(idx, ctrl) {
      data[ctrl.id]=$(ctrl).val();
    });
    $('#table-data').val(JSON.stringify(data));
    // add up totals
    totaliseCol(cell);
    totaliseRow(row);
  });
  
  // fill in initial totals
  $.each($('#weekly-counts-grid tbody tr'), function (idx, row) {
    totaliseRow(row);
  });
  $.each($('#weekly-counts-grid tbody tr:first-child td'), function (idx, cell) {
    if (cell.className.substr(0,4)==='col-') {
      totaliseCol(cell);
    }
  });
  
  /*
   * A keyboard event handler for the grid.
   */
  var keyHandler = function(e) {
    var rows, row, rowIndex, cells, cell, cellIndex, caretPos, ctrl = e.target, deltaX = 0, deltaY = 0,
      isTextbox=ctrl.nodeName.toLowerCase() === 'input' && $(ctrl).attr('type') === 'text';
    if ((e.keyCode >= 37 && e.keyCode <= 40) || e.keyCode === 9) {
      rows = $(ctrl).parents('tbody').children();
      row = $(ctrl).parents('tr')[0];
      rowIndex = rows.index(row);
      cells = $(ctrl).parents('tr').children();
      cell = $(ctrl).parents('td')[0];
      cellIndex = cells.index(cell);
      if (isTextbox) {
        if (typeof ctrl.selectionStart !== 'undefined') {
          caretPos = ctrl.selectionStart;
        } else {  // Internet Explorer before version 9
          var inputRange = ctrl.createTextRange();
          // Move selection start to 0 position
          inputRange.moveStart('character', -ctrl.value.length);
          // The caret position is selection length
          caretPos = inputRange.text.length;
        }
      }
    }
    switch (e.keyCode) {
      case 9:
        // tab direction depends on shift key and occurs irrespective of caret
        deltaX = e.shiftKey ? -1 : 1;
        break;
      case 37: // left. Caret must be at left of text in the box
        if (!isTextbox || caretPos === 0) {
          deltaX = -1;
        }
        break;
      case 38: // up
        if (rowIndex > 0) {
          deltaY = -1;
        }
        break;
      case 39: // right
        if (!isTextbox || caretPos >= $(ctrl).val().length) {
          deltaX = 1;
        }
        break;
      case 40: // down. 
        if (rowIndex < rows.length-1) {
          deltaY = 1;
        }
        break;
    }    
    if (deltaX !== 0) {
      var inputs = $(cell).closest('table').find(':input:visible');
      // timeout necessary to allow keyup to occur on correct control
      setTimeout(function() {
        inputs.eq(inputs.index(ctrl) + deltaX).focus();
      }, 200);
      e.preventDefault();      
      return false;
    }
    if (deltaY !== 0) {
      $(rows[rowIndex+deltaY]).find('td.' + cell.className + ' input').focus();
    }
  };
  
  $('#weekly-counts-grid').keydown(keyHandler);

});