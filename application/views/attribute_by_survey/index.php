<?php

/**
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
 * @package Core
 * @subpackage Views
 * @author  Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link  http://code.google.com/p/indicia/
 */

?>
<script type="text/javascript">
$(document).ready(function() {
  makeControlsDragDroppable();
  makeBlocksDragDroppable();
  // if the user clicks save for layout changes, then the structure must be posted back to the server for
  // saving.
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
    var existingAttrs = {<?php 
      // the JavaScript needs a list of attribute captions
      $idx = 0;
      foreach ($existingAttrs as $attr) {
        echo '"id'.$attr->id.'":"'.$attr->caption.'"';
        if ($idx<count($existingAttrs)-1) {
          echo ",";
        }
        $idx++;
      }
    ?>};
    var attrId=$('#existing-attribute')[0].value;
    $('#controls').append('<li id="existing-attr-'+attrId+'" style="border-color: red" '+
            'class="draggable-control ui-widget ui-widget-content ui-corner-all">' +
            '<span class="handle">&nbsp;</span>' +
            '<span>' + existingAttrs['id'+attrId] + ' *</span>' +
            '<span class="ui-helper-clearfix"></span>'+
            '</li><li class="control-drop"></li>');
    makeControlsDragDroppable();
  });

  $('#actions-new-block').submit(function(event) {
    event.preventDefault();
    var block=$('#new-block')[0].value;
    block = $.trim(block);
    if (block==='') {
      alert('Please provide a name for the block');
    } else {
      $('#top-blocks').append('<li id="new-block-'+block.replace(' ','_')+'" style="border-color: red" '+
            'class="ui-widget ui-widget-header draggable-block">' +
          '<span>'+block + ' *</span>' +
          '<ul id="child-blocks-new-block-'+block.replace(' ','_')+'" class="block-list ui-widget-content"></ul>'+
          '<ul class="ui-widget ui-widget-content ui-corner-all control-list">'+
          '<li class="control-drop"></li></ul></li>');
      $('#layout-change-form').show();
      makeBlocksDragDroppable();
      makeControlsDragDroppable();
    }
  });

});

function outputControls(list) {
  var r='';
  $(list).children().each(function(i, control) {
    if ($(control).hasClass('draggable-control')) {
      if (r!=='') {
        r += ',';
      }
      r += '"' + control.id + '"';
    }
  });
  return r;
}

function outputBlocks(list) {
  var r='';
  $(list).children().each(function(i, block) {
    if ($(block).hasClass('draggable-block')) {
      if (r!=='') {
        r += ',';
      }
      r += '{';
        r += '"id":"' + block.id + '",'; 
        r += '"name":"' + $('#' + block.id+' span').text() + '",';
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

/*function makePlaceholderDroppable() {
  $('.blank-drop-placeholder').droppable('destroy');
  $('.blank-drop-placeholder').droppable({
    drop: function(event, ui) {
      var target = $(event.target);
      var draggable = ui.draggable;
      if (draggable.hasClass('draggable-control')) {
          draggable.insertBefore(target);
      } else if (draggable.hasClass('block')) {
        if (target.parent().parent().parent().parent().parent().hasClass('block-list')) {
          return;
        }
        target.parent().prev().append(draggable);
      }
      draggable.css('top',0);
      draggable.css('border-color', 'red');
      $('#layout-change-form').show();
      if (draggable.text().substr(draggable.text().length-1,1)!='*') {
        draggable.text(draggable.text() + ' *');
      }
    },
    accept: '.draggable-control, .block',
    hoverClass: 'ui-state-highlight',
    activeClass: 'drop-active'
  });
}*/

function makeControlsDragDroppable() {
  // do a full refresh as there could be new items
  $('.draggable-control').draggable('destroy');
  $('.draggable-control').droppable('destroy');
  $('.draggable-control').draggable({
    axis: 'y',
    helper: 'clone',
    opacity: 0.5,
    revert: 'invalid',
    handle: '.handle'
  });
  
  $('.control-drop').droppable({
    drop: function(event, ui) {
      // visuals
      var target = $(event.target);
      var draggable = ui.draggable;
      if (draggable.hasClass('draggable-control')) {
        var controlDrop = draggable.prev();
        // move the drop target as well
        controlDrop.insertBefore(target);
        draggable.insertBefore(target);
      }
      draggable.css('top',0);
      draggable.css('border-color', 'red');
      $('#layout-change-form').show();
      var label=$(draggable.children()[0]);
      if (label.text().substr(label.text().length-1,1)!='*') {
        label.text(label.text() + ' *');
      }
    },
    accept: '.draggable-control',
    hoverClass: 'ui-state-highlight',
    activeClass: 'drop-active',
    tolerance: 'pointer'
  });
}

function makeBlocksDragDroppable() {
  // do a full refresh as there could be new items
  $('.draggable-block').draggable('destroy');
  $('.draggable-block').droppable('destroy');
  $('.draggable-block').draggable({
    axis: 'y',
    helper: 'clone',
    opacity: 0.5,
    revert: 'invalid',
    handle: '> .handle'
  });
  
  $('.block-drop').droppable({
    drop: function(event, ui) {
      // visuals
      var target = $(event.target);
      var draggable = ui.draggable;
      if (draggable.hasClass('draggable-block')) {
        if (target.parent().parent().parent().parent().parent().hasClass('block-list')) {
          return;
        }
        var controlDrop = draggable.prev();
        // move the drop target as well
        controlDrop.insertBefore(target);
        draggable.insertBefore(target);
      }
      draggable.css('top',0);
      draggable.css('border-color', 'red');
      $('#layout-change-form').show();
      var label=$(draggable.children()[0]);
      if (label.text().substr(label.text().length-1,1)!='*') {
        label.text(label.text() + ' *');
      }
    },
    accept: '.draggable-block',
    hoverClass: 'ui-state-highlight',
    activeClass: 'drop-active',
    tolerance: 'pointer'
  });
}

</script>
<form action="" method="get">
<fieldset>
<?php 
require_once(DOCROOT.'client_helpers/data_entry_helper.php');
echo data_entry_helper::select(array(
  'fieldname'=>'type',
  'label' => 'Display attributes for',
  'lookupValues' => array('sample'=>'Samples','occurrence'=>'Occurrences','location'=>'Locations'),
  'default' => $_GET['type'],
  'suffixTemplate' => 'nosuffix'
));
?>
<input type="submit" class="button ui-state-default ui-widget-content ui-corner-all" id="change-type" value="Go" />
</fieldset>
</form>
<ul id="top-blocks" class="block-list">
<?php 
foreach($top_blocks as $block) {
  echo '<li class="block-drop"></li>';
  echo '<li id="block-'.$block->id.'" class="ui-widget draggable-block">';
  echo "<span class=\"handle\">&nbsp;</span>\n";
  echo '<span class="caption">'.$block->name."</span>\n";
  echo "<ul id=\"child-blocks-".$block->id."\" class=\"block-list\">\n";
  $child_blocks = ORM::factory('form_structure_block')->
        where('parent_id',$block->id)->
        where($filter)->
        orderby('weight', 'ASC')->find_all();
  foreach($child_blocks as $child_block) {
    echo '<li class="block-drop"></li>';
    echo '<li id="block-'.$child_block->id.'" class="ui-widget draggable-block">';
    echo "<span class=\"handle\">&nbsp;</span>\n";
    echo '<span>'.$child_block->name."</span>\n";
    echo '<span class="ui-helper-clearfix"></span>';
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
get_controls(null,$controlfilter);

function get_controls($block_id, $controlfilter) {
  $id = "controls";
  if ($block_id) $id .= '-for-block-'.$block_id;
  echo "<ul id=\"$id\" class=\"ui-widget ui-corner-all ui-widget-content control-list\">\n";
  $child_controls = ORM::factory($_GET['type'].'_attributes_website')->
        where('form_structure_block_id',$block_id)->
        where($controlfilter)->
        orderby('weight', 'ASC')->find_all();
  foreach($child_controls as $control) {
    echo '<li class="control-drop"></li>';
    $attr = $_GET['type'].'_attribute';
    echo '<li id="control-'.$control->id.'" class="draggable-control ui-widget ui-widget-content ui-corner-all">'.
        "<span class=\"handle\">&nbsp;</span>\n".
        '<span class="caption">'.$control->$attr->caption."</span>\n".
        "<a href=\"\">Edit</a>\n".
        "<div class=\"ui-helper-clearfix\"></div></li>\n";
  }
  // extra item to allow drop at end of list
  echo '<li class="control-drop"></li>';
  echo "</ul>";
}

 ?>

<form style="display: none" id="layout-change-form" class="ui-state-highlight page-notice" action="<?php 
    echo url::site().'attribute_by_survey/layout_update/'.$this->uri->last_segment().'?type='.$_GET['type'];
?>" method="post">
<fieldset>
<input type="hidden" name="layout_updates" id="layout_updates"/>
<span>The layout changes you have made will not be saved until you click the Save button.</span>
<input type="submit" value="Save" id="layout-submit"/>
</fieldset>
</form>
<form id="actions-new-block">
<fieldset>
<label for="new-block">Block name:</label>
<input type="text" name="new-block" id="new-block" style="width: 200px"/>
<input type="submit" value="Create new block" id="submit-new-block" />
</fieldset>
</form>
<form id="actions-add-existing">
<fieldset> 
<label for="existing-attribute">Existing attribute:</label>
<select id="existing-attribute" name="existing-attribute" style="width: 200px">
<?php 
foreach ($existingAttrs as $attr) {
  echo '<option value="'.$attr->id.'">'.$attr->caption."</option>\n";
}
?>
</select>
<input type="submit" value="Add existing attribute" id="submit-existing-attribute" />
</fieldset>
</form>