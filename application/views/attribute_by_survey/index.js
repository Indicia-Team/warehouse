function getControlData(list) {
  var r = '';
  $(list).children().each(function controlData(i, control) {
    if ($(control).hasClass('draggable-control')) {
      if (r !== '') {
        r += ',';
      }
      r += '{';
      if ($(control).hasClass('deleted')) {
        r += '"deleted":true,';
      }
      r += '"id":"' + control.id + '"}';
    }
  });
  return r;
}

function getBlockData(list) {
  var r = '';
  var caption;
  $(list).children().each(function blockData(i, block) {
    if ($(block).hasClass('draggable-block')) {
      if (r !== '') {
        r += ',';
      }
      r += '{';
      r += '"id":"' + block.id + '",';
      caption = $('#' + block.id + ' > div > .caption').text();
      if (caption.substr(caption.length - 1, 1) === '*') {
        caption = $.trim(caption.substr(0, caption.length - 1));
      }
      r += '"name":"' + caption + '",';
      if ($(block).hasClass('deleted')) {
        r += '"deleted":true,';
      }
      r += '"blocks": [\n';
      r += getBlockData($('#' + block.id + ' > .block-list'));
      r += '],"controls": [';
      r += getControlData($('#' + block.id + ' > .control-list'));
      r += ']';
      r += '}';
    }
  });
  return r;
}

function moveBlock(source, target) {
  var controlDrop;
  var label;
  if (source.prev()[0] !== target[0] && source.next()[0] !== target[0]) {
    if (source.hasClass('draggable-block')) {
      if (target.parent().parent().parent().parent()
        .parent()
        .hasClass('block-list')) {
        return;
      }
      controlDrop = source.prev();
      // move the drop target as well
      controlDrop.insertBefore(target);
      source.insertBefore(target);
    }
    source.css('top', 0);
    source.css('border-color', 'red');
    $('#layout-change-form').show();
    label = $(source.find('> div > .caption'));
    if (label.text().substr(label.text().length - 1, 1) !== '*') {
      label.text(label.text() + ' *');
    }
  }
}

function moveControl(source, target) {
  var srcList;
  var controlDrop;
  var label;
  // Don't bother doing anything if dragging to the dragged control's drop placeholder above or below it.
  if (source.prev()[0] !== target[0] && source.next()[0] !== target[0]) {
    if (source.hasClass('draggable-control')) {
      srcList = source.parent()[0];
      controlDrop = source.prev();
      // move the drop target as well
      controlDrop.insertBefore(target);
      source.insertBefore(target);
      // force a redraw to get round a bug in IE not updating after items removed from list
      $(srcList).addClass('redraw');
      $(srcList).removeClass('redraw');
    }
    source.css('top', 0);
    source.css('border-color', 'red');
    $('#layout-change-form').show();
    // Second child of the li is the label, the first is the drag handle. So go for index 1
    label = $(source.find('.caption'));
    if (label.text().substr(label.text().length - 1, 1) !== '*') {
      label.text(label.text() + ' *');
    }
  }
}

function makeBlocksDragDroppable() {
  // do a full refresh as there could be new items
  $('.draggable-block.ui-draggable').draggable('destroy');
  $('.draggable-block.ui-droppable').droppable('destroy');
  $('.draggable-block').draggable({
    axis: 'y',
    helper: 'clone',
    opacity: 0.5,
    revert: 'invalid',
    handle: '> div > .handle'
  });

  $('.block-drop').droppable({
    drop: function(event, ui) {
      moveBlock(ui.draggable, $(this));
    },
    accept: '.draggable-block',
    hoverClass: 'ui-state-highlight',
    activeClass: 'drop-active',
    tolerance: 'pointer'
  });
}


function makeControlsDragDroppable() {
  // do a full refresh as there could be new items
  $('.draggable-control.ui-draggable').draggable('destroy');
  $('.draggable-control.ui-droppable').droppable('destroy');
  $('.draggable-control').draggable({
    axis: 'y',
    helper: 'clone',
    opacity: 0.5,
    revert: 'invalid',
    handle: '.handle'
  });

  $('.control-drop').droppable({
    drop: function controlDrop(event, ui) {
      moveControl(ui.draggable, $(this));
    },
    accept: '.draggable-control',
    hoverClass: 'ui-state-highlight',
    activeClass: 'drop-active',
    tolerance: 'pointer'
  });
}

$(document).ready(function documentReady() {

  var blockCount = 0;

  /**
  * if the user clicks save for layout changes, then the structure must be posted back to the server for saving.
  */
  $('#layout-change-form').submit(function formSubmit() {
    var data = '{"blocks": [';
    data += getBlockData($('#top-blocks'));
    data += '], "controls": [';
    data += getControlData($('#controls'));
    data += ']}';
    // store the data in a hidden input, so it can be posted to the server
    $('#layout_updates').val(data);
  });

  $('#actions-add-existing').submit(function addExistingSubmit(event) {
    var attrId = $('#existing-attribute')[0].value;
    event.preventDefault();
    // Does this attribute already exist?
    if ($('li.attribute-' + attrId).length !== 0) {
      alert('This attribute already exists for the survey.');
    } else {
      $('#controls').append('<li id="attribute-' + attrId + '" style="border-color: red" ' +
              'class="attribute-' + attrId + ' draggable-control panel clearfix">' +
              '<span class="handle">&nbsp;</span>' +
              '<span class="caption">' + indiciaData.existingAttrs['id' + attrId] + ' (ID ' + attrId + ')*</span>' +
              '</li><li class="control-drop"></li>');
      makeControlsDragDroppable();
      $('#layout-change-form').show();
    }
  });

  $('#actions-new-block').submit(function addNewSubmit(event) {
    var block = $('#new-block')[0].value;
    event.preventDefault();
    block = $.trim(block);
    if (block === '') {
      alert('Please provide a name for the block.');
    } else if (!block.match(/^[a-zA-Z0-9 ]+$/)) {
      alert('The block name should consist of letters, numbers and spaces only.');
    } else {
      blockCount++;
      $('#top-blocks').append(
        '<li id="new-block-' + blockCount + '" style="border-color: red" class="draggable-block panel panel-primary">' +
        '<div class="clearfix">' +
        '<span class="handle">&nbsp;</span>' +
        '<span class="caption">' + block + ' *</span>' +
        '<a href="" class="block-rename btn btn-default btn-xs">Rename</a>' +
        '</div>' +
        '<ul id="child-blocks-new-block-' + blockCount + '" class="block-list"><li class="block-drop"></li></ul>' +
        '<ul class="control-list">' +
        '<li class="control-drop"></li></ul></li>' +
        '<li class="block-drop"></li>\n'
      );
      $('#layout-change-form').show();
      makeBlocksDragDroppable();
      makeControlsDragDroppable();
    }
  });

  /**
  * Handle click on the rename link for a block. Replaces the caption span with a temporary input control and an apply
  * button.
  */
  $(document).on('click', '.block-rename', null, function blockRenameClick(event) {
    var caption = $(event.target).siblings('span.caption');
    var current;
    var input;
    var btn;
    event.preventDefault();
    // Check we are not already in rename mode
    if (caption.length > 0) {
      // strip the * from the caption if already edited
      current = caption.text().replace(/ \*$/, '');
        // swap the span for a text input and Apply button
      caption.replaceWith(
        '<input type="text" class="caption" value="' + current + '"/>' +
        '<input type="button" class="rename-apply btn btn-primary btn-xs" value="Apply" />'
      );
      input = $(event.target).siblings('.caption');
      input.focus();
      input.select();
      btn = $(event.target).siblings('.rename-apply');
      btn.click(function btnClickEvent(btnClickEvt) {
        $(btnClickEvt.target.parentNode.parentNode).css('border-color', 'red');
        input.replaceWith('<span class="caption">' + input.val() + ' *</span>');
        btn.remove();
        $('#layout-change-form').show();
      });
    }
  });

  $('.block-delete').click(function blockDeleteClick(event) {
    var block = $(event.target.parentNode.parentNode);
    event.preventDefault();
    // finish renaming mode, if that is what we are doing
    block.find('input.rename-apply').click();
    // move the children out to the top level
    $.each(block.children('.block-list').children('.draggable-block'), function moveChildBlock(idx, childBlock) {
      moveBlock($(childBlock), $('#top-blocks').children('.block-drop:last'));
    });
    $.each(block.children('.control-list').children('.draggable-control'), function moveChildControl(idx, childControl) {
      moveControl($(childControl), $('#controls').children('.control-drop:last'));
    });
    // mark the block with a deleted class that will be handled later
    block.addClass('deleted');
    // restyle and remove the drag/drop capability of the deleted block
    block.is('.ui-draggable').draggable('destroy');
    block.find('.block-drop').is('.ui-droppable').droppable('destroy');
    block.find('.block-drop').removeClass('block-drop');
    block.find('.control-drop').is('.ui-droppable').droppable('destroy');
    block.find('.control-drop').removeClass('control-drop');
    block.find('a').css('display', 'none');
    block.find('.handle').css('display', 'none');
    block.find('.caption').css('text-decoration', 'line-through');
    $('#layout-change-form').show();
  });

  $('.control-delete').click(function controlDeleteClick(event) {
    var control = $(event.target.parentNode);
    event.preventDefault();
    // Mark the control with a deleted class that will be handled later.
    control.addClass('deleted');
    // Restyle and hide drag handles etc.
    control.find('a').css('display', 'none');
    control.find('.handle').css('display', 'none');
    control.find('.caption').css('text-decoration', 'line-through');
    // Remove the drag/drop capability of the deleted control.
    if (control.is('.ui-draggable')) {
      control.draggable('destroy');
    }
    control.find('.control-drop').removeClass('control-drop');
    $('#layout-change-form').show();
  });

  makeControlsDragDroppable();
  makeBlocksDragDroppable();
});
