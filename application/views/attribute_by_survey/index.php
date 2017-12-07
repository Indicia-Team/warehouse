<?php

/**
 * @file
 * View template for the attributes by surveys index page.
 *
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
 *
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
 */

?>
<script type="text/javascript">
function outputControls(list) {
  var r='';
  $(list).children().each(function(i, control) {
    if ($(control).hasClass('draggable-control')) {
      if (r!=='') {
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

function outputBlocks(list) {
  var r='', caption;
  $(list).children().each(function(i, block) {
    if ($(block).hasClass('draggable-block')) {
      if (r!=='') {
        r += ',';
      }
      r += '{';
        r += '"id":"' + block.id + '",';
    caption = $('#' + block.id+' > div > .caption').text();
    if (caption.substr(caption.length-1, 1)==='*') {
      caption = $.trim(caption.substr(0, caption.length-1));
    }
        r += '"name":"' + caption + '",';
    if ($(block).hasClass('deleted')) {
      r += '"deleted":true,';
    }
        r += '"blocks": [\n';
          r += outputBlocks($('#' + block.id + ' > .block-list'));
        r += '],"controls": [';
          r += outputControls($('#' + block.id + ' > .control-list'));
        r += ']';
      r += '}';
    }
  });
  return r;
}

function moveBlock(source, target) {
  if (source.prev()[0]!==target[0] && source.next()[0]!==target[0]) {
    if (source.hasClass('draggable-block')) {
      if (target.parent().parent().parent().parent().parent().hasClass('block-list')) {
        return;
      }
      var controlDrop = source.prev();
      // move the drop target as well
      controlDrop.insertBefore(target);
      source.insertBefore(target);
    }
    source.css('top',0);
    source.css('border-color', 'red');
    $('#layout-change-form').show();
    var label=$(source.find('> div > .caption'));
    if (label.text().substr(label.text().length-1,1)!=='*') {
      label.text(label.text() + ' *');
    }
  }
}

function moveControl(source, target) {
  // Don't bother doing anything if dragging to the dragged control's drop placeholder above or below it.
  if (source.prev()[0]!==target[0] && source.next()[0]!==target[0]) {
  if (source.hasClass('draggable-control')) {
    var srcList = source.parent()[0], controlDrop = source.prev();
    // move the drop target as well
    controlDrop.insertBefore(target);
    source.insertBefore(target);
    // force a redraw to get round a bug in IE not updating after items removed from list
    $(srcList).addClass('redraw');
    $(srcList).removeClass('redraw');
  }
  source.css('top',0);
  source.css('border-color', 'red');
  $('#layout-change-form').show();
  // Second child of the li is the label, the first is the drag handle. So go for index 1
  var label=$(source.find('.caption'));
  if (label.text().substr(label.text().length-1,1)!='*') {
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
    drop: function(event, ui) {
      moveControl(ui.draggable, $(this));
    },
    accept: '.draggable-control',
    hoverClass: 'ui-state-highlight',
    activeClass: 'drop-active',
    tolerance: 'pointer'
  });
}

$(document).ready(function() {
  /**
  * if the user clicks save for layout changes, then the structure must be posted back to the server for saving.
  */
  $('#layout-change-form').submit(function() {
    var data = '{"blocks": [';
    data += outputBlocks($('#top-blocks'));
    data += '], "controls": [';
    data += outputControls($('#controls'));
    data += ']}';
    // store the data in a hidden input, so it can be posted to the server
    $('#layout_updates').val(data);
  });

  $('#actions-add-existing').submit(function(event) {
    event.preventDefault();
  var attrId=$('#existing-attribute')[0].value;
  // Does this attribute already exist?
  if ($('li.attribute-' + attrId).length!==0) {
    alert('This attribute already exists for the survey.');
  } else {
    var existingAttrs = <?php
    echo "{";
    // The JavaScript needs a list of attribute captions.
    $idx = 0;
    foreach ($existingAttrs as $attr) {
      echo '"id' . $attr->id . '":"' . $attr->caption . '"';
      if ($idx < count($existingAttrs) - 1) {
        echo ",";
      }
      $idx++;
    }
    echo "}";
    ?>;

    $('#controls').append('<li id="attribute-'+attrId+'" style="border-color: red" '+
            'class="attribute-'+attrId+' draggable-control panel clearfix">' +
            '<span class="handle">&nbsp;</span>' +
            '<span class="caption">' + existingAttrs['id'+attrId] + ' (ID ' + attrId + ')*</span>' +
            '</li><li class="control-drop"></li>');
    makeControlsDragDroppable();
    $('#layout-change-form').show();
  }
  });

  $('#actions-new-block').submit(function(event) {
    event.preventDefault();
    var block=$('#new-block')[0].value;
    block = $.trim(block);
    if (block==='') {
      alert('Please provide a name for the block.');
    } else if (!block.match(/^[a-zA-Z0-9 ]+$/)) {
    alert('The block name should consist of letters, numbers and spaces only.');
  } else {
      $('#top-blocks').append(
          '<li id="new-block-'+block.replace(/ /g,'_')+'" style="border-color: red" '+'class="draggable-block panel panel-primary">' +
          '<div class="clearfix">' +
          '<span class="handle">&nbsp;</span>' +
          '<span class="caption">'+block + ' *</span>' +
          '<a href="" class="block-rename btn btn-default btn-xs">Rename</a>'+
          '</div>' +
          '<ul id="child-blocks-new-block-'+block.replace(' ','_')+'" class="block-list"><li class="block-drop"></li></ul>'+
          '<ul class="control-list">'+
          '<li class="control-drop"></li></ul></li>'+
          "<li class=\"block-drop\"></li>\n");
      $('#layout-change-form').show();
      makeBlocksDragDroppable();
      makeControlsDragDroppable();
    }
  });

  /**
  * Handle click on the rename link for a block. Replaces the caption span with a temporary input control and an apply button.
  */
  $(document).on('click', '.block-rename', null, function(event) {
    event.preventDefault();
    var caption=$(event.target).siblings('span.caption');
    // Check we are not already in rename mode
    if (caption.length>0) {
      // strip the * from the caption if already edited
      var current=caption.text().replace(/ \*$/,'');
        // swap the span for a text input and Apply button
      caption.replaceWith('<input type="text" class="caption" value="' + current + '"/><input type="button" class="rename-apply btn btn-primary btn-xs" value="Apply" />');
      var input=$(event.target).siblings('.caption');
      input.focus();
      input.select();
      var btn=$(event.target).siblings('.rename-apply');
      btn.click(function(event) {
        $(event.target.parentNode.parentNode).css('border-color', 'red');
        input.replaceWith('<span class="caption">' + input.val() + ' *</span>');
        btn.remove();
          $('#layout-change-form').show();
      });
    }
  });

  $('.block-delete').click(function(event) {
    event.preventDefault();
  var block = $(event.target.parentNode.parentNode);
  // finish renaming mode, if that is what we are doing
  block.find('input.rename-apply').click();
  // move the children out to the top level
  $.each(block.children('.block-list').children('.draggable-block'), function(idx, block) {
    moveBlock($(block), $('#top-blocks').children('.block-drop:last'));
  });
  $.each(block.children('.control-list').children('.draggable-control'), function(idx, control) {
    moveControl($(control), $('#controls').children('.control-drop:last'));
  });
  // mark the block with a deleted class that will be handled later
  block.addClass('deleted');
  // restyle and remove the drag/drop capability of the deleted block
  block.is('.ui-draggable').draggable('destroy');
  block.find('.block-drop').is('.ui-droppable').droppable('destroy');
  block.find('.block-drop').removeClass('block-drop');
  block.find('.control-drop').is('.ui-droppable').droppable('destroy');
  block.find('.control-drop').removeClass('control-drop');
  block.find('a').css('display','none');
  block.find('.handle').css('display','none');
  block.find('.caption').css('text-decoration', 'line-through');
  $('#layout-change-form').show();
  });

  $('.control-delete').click(function(event) {
    event.preventDefault();
    var control = $(event.target.parentNode);
    // mark the control with a deleted class that will be handled later
    control.addClass('deleted');
    // restyle and remove the drag/drop capability of the deleted control
    control.is('.ui-draggable').draggable('destroy');
    control.find('.control-drop').removeClass('control-drop');
    control.find('a').css('display','none');
    control.find('.handle').css('display','none');
    control.find('.caption').css('text-decoration', 'line-through');
    $('#layout-change-form').show();
  });

  makeControlsDragDroppable();
  makeBlocksDragDroppable();
});

</script>
<form action="" method="get" class="form-inline">
<?php
warehouse::loadHelpers(['data_entry_helper']);

echo data_entry_helper::select(array(
  'fieldname' => 'type',
  'label' => 'Display attributes for',
  'lookupValues' => array(
    'sample' => 'Samples',
    'occurrence' => 'Occurrences',
    'location' => 'Locations',
  ),
  'default' => $_GET['type']
));
?>
<input type="submit" class="btn btn-default" id="change-type" value="Go" />
</form>
<div id="attribute-by-survey-index">
<ul id="top-blocks" class="block-list">
<?php
foreach($top_blocks as $block) {
  echo <<<PNLTOP
<li class="block-drop"></li>
<li id="block-$block->id" class="panel panel-primary draggable-block">
  <div class="panel-heading clearfix">
    <span class="handle">&nbsp;</span>
    <span class="caption">$block->name</span>
    <a href="" class="block-delete btn btn-warning btn-xs pull-right">Delete</a>
    <a href="" class="block-rename btn btn-default btn-xs pull-right">Rename</a>
  </div>
  <ul id="child-blocks-$block->id" class="block-list">

PNLTOP;
  $child_blocks = ORM::factory('form_structure_block')
    ->where('parent_id', $block->id)
    ->where($filter)
    ->orderby('weight', 'ASC')
    ->find_all();
  foreach ($child_blocks as $child_block) {
    echo <<<PNLCHILD
    <li class="block-drop"></li>
    <li id="block-$child_block->id" class="panel panel-info draggable-block">
      <div class="panel-heading clearfix">
      <span class="handle">&nbsp;</span>
      <span class="caption">$child_block->name</span>
      <a href="" class="block-delete pull-right btn btn-warning btn-xs">Delete</a>
      <a href="" class="block-rename pull-right btn btn-default btn-xs">Rename</a>
    </div>
PNLCHILD;
    get_controls($child_block->id, $controlfilter);
    echo "</li>\n";
  }
  echo '<li class="block-drop"></li>';
  echo "</ul>";
  get_controls($block->id, $controlfilter);
  echo "</li>";
}
?><li class="block-drop"></li></ul>
<?php
get_controls(null, $controlfilter);

function get_controls($block_id, $controlfilter) {
  $id = "controls";
  if ($block_id) $id .= '-for-block-' . $block_id;
  echo "<ul id=\"$id\" class=\"control-list\">\n";
  $child_controls = ORM::factory($_GET['type'] . '_attributes_website')
    ->where('form_structure_block_id', $block_id)
    ->where($controlfilter)
    ->where('deleted', 'f')
    ->orderby('weight', 'ASC')->find_all();
  foreach ($child_controls as $control) {
    echo '<li class="control-drop"></li>';
    // Prepare some dynamic property names.
    $attr = $_GET['type'] . '_attribute';
    $attrId = $attr . '_id';
    echo '<li id="control-' . $control->id . '" class="attribute-' . $control->$attrId . ' draggable-control panel panel-primary clearfix">'.
        "<span class=\"handle\">&nbsp;</span>\n" .
        '<span class="caption">' . $control->$attr->caption . " (ID {$control->$attrId})</span>\n" .
        '<a class="control-delete pull-right btn btn-warning btn-xs">Delete</a>' .
        '<a href="' . url::site() . 'attribute_by_survey/edit/' . $control->id . '?type=' . $_GET['type'] . "\" class=\"pull-right btn btn-default btn-xs\">Survey settings</a>\n" .
        '<a href="' . url::site() . $_GET['type'] . "_attribute/edit/{$control->$attrId}\" class=\"pull-right btn btn-default btn-xs\">Global settings</a>\n" .
        "</li>\n";
  }
  // Extra item to allow drop at end of list.
  echo '<li class="control-drop"></li>';
  echo "</ul>";
}

 ?>
 </div>

<form style="display: none" id="layout-change-form" class="inline-form panel alert alert-info" action="<?php
    echo url::site() . 'attribute_by_survey/layout_update/' . $this->uri->last_segment() . '?type=' . $_GET['type'];
?>" method="post">
<input type="hidden" name="layout_updates" id="layout_updates"/>
<span>The layout changes you have made will not be saved until you click the Save button.</span>
<input type="submit" value="Save" id="layout-submit" class="btn btn-primary"/>
</form>
<form id="actions-new-block" class="form-inline">
  <div class="form-group">
    <label for="new-block">Block name:</label>
    <input type="text" name="new-block" id="new-block" class="form-control" />
  </div>
  <input type="submit" value="Create new block" id="submit-new-block" class="btn btn-default line-up" />
</form>
<form id="actions-add-existing" class="form-inline">
  <div class="form-group">
    <label for="existing-attribute">Existing attribute:</label>
    <select id="existing-attribute" name="existing-attribute" class="form-control">
<?php
foreach ($existingAttrs as $attr) {
  echo "      <option value=\"{$attr->id}\">{$attr->caption} (ID {$attr->id})</option>\n";
}
?>
    </select>
    <input type="submit" value="Add existing attribute" id="submit-existing-attribute" class="btn btn-default" />
  </div>
</form>