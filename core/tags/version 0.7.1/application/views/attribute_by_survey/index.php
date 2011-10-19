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
  $('.draggable-block').draggable('destroy');
  $('.draggable-block').droppable('destroy');
  $('.draggable-block').draggable({
    axis: 'y',
    helper: 'clone',
    opacity: 0.5,
    revert: 'invalid',
    handle: '> div > .handle'
  });
  
  $('.block-drop').droppable({
    drop: function(event, ui) {
	    moveBlock(ui.draggable, $(event.target));      
    },
    accept: '.draggable-block',
    hoverClass: 'ui-state-highlight',
    activeClass: 'drop-active',
    tolerance: 'pointer'
  });
}


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
	    moveControl(ui.draggable, $(event.target));
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
        // the JavaScript needs a list of attribute captions\
        $idx = 0;
        foreach ($existingAttrs as $attr) {
          echo '"id'.$attr->id.'":"'.$attr->caption.'"';
          if ($idx<count($existingAttrs)-1) {
            echo ",";
          }
          $idx++;
        }
        echo "}";
      ?>;
    
      $('#controls').append('<li id="attribute-'+attrId+'" style="border-color: red" '+
            'class="attribute-'+attrId+' draggable-control ui-widget ui-widget-content ui-corner-all ui-helper-clearfix">' +
            '<span class="handle">&nbsp;</span>' +
            '<span class="caption">' + existingAttrs['id'+attrId] + ' *</span>' +			
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
          '<li id="new-block-'+block.replace(/ /g,'_')+'" style="border-color: red" '+'class="ui-widget draggable-block">' +
		      '<div class="ui-helper-clearfix">' +
	        '<span class="handle">&nbsp;</span>' +
          '<span class="caption">'+block + ' *</span>' +
		      '<a href="" class="block-rename">Rename</a>'+
		      '</div>' +
          '<ul id="child-blocks-new-block-'+block.replace(' ','_')+'" class="block-list"><li class="block-drop"></li></ul>'+
          '<ul class="ui-widget ui-widget-content ui-corner-all control-list">'+
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
  $('.block-rename').live('click', function(event) {
    event.preventDefault();
	var caption=$(event.target).siblings('span.caption');
	// Check we are not already in rename mode
	if (caption.length>0) {
	  // strip the * from the caption if already edited
	  var current=caption.text().replace(/ \*$/,'');
      // swap the span for a text input and Apply button	  
	  caption.replaceWith('<input type="text" class="caption" value="' + current + '"/><input type="button" class="rename-apply button ui-state-default ui-corner-all ui-widget-content" value="Apply" />');
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
	block.draggable('destroy');
	block.find('.block-drop').droppable('destroy');	
	block.find('.block-drop').removeClass('block-drop');	
	block.find('.control-drop').droppable('destroy');	
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
    control.draggable('destroy');
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
<form action="" method="get">
<fieldset>
<?php 
require_once(DOCROOT.'client_helpers/data_entry_helper.php');
echo data_entry_helper::select(array(
  'fieldname'=>'type',
  'label' => 'Display attributes for',
  'lookupValues' => array('sample'=>'Samples','occurrence'=>'Occurrences','location'=>'Locations'),
  'default' => $_GET['type'],
  'suffixTemplate' => 'nosuffix',
  'class' => 'line-up'  
));
?>
<input type="submit" class="button ui-state-default ui-widget-content ui-corner-all line-up" id="change-type" value="Go" />
</fieldset>
</form>
<ul id="top-blocks" class="block-list">
<?php 
foreach($top_blocks as $block) {
  echo '<li class="block-drop"></li>';
  echo '<li id="block-'.$block->id.'" class="ui-widget draggable-block">';
  echo "<div class=\"ui-helper-clearfix\">\n";
  echo "<span class=\"handle\">&nbsp;</span>\n";
  echo '<span class="caption">'.$block->name."</span>\n";
  echo '<a href="" class="block-delete">Delete</a>';	
  echo '<a href="" class="block-rename">Rename</a>';
  echo "</div>\n";
  echo "<ul id=\"child-blocks-".$block->id."\" class=\"block-list\">\n";
  $child_blocks = ORM::factory('form_structure_block')->
        where('parent_id',$block->id)->
        where($filter)->
        orderby('weight', 'ASC')->find_all();
  foreach($child_blocks as $child_block) {
    echo '<li class="block-drop"></li>';
    echo '<li id="block-'.$child_block->id.'" class="ui-widget draggable-block">';
	  echo "<div class=\"ui-helper-clearfix\">\n";
    echo "<span class=\"handle\">&nbsp;</span>\n";
    echo '<span class="caption">'.$child_block->name."</span>\n"; 
    echo '<a href="" class="block-delete">Delete</a>';	
	  echo '<a href="" class="block-rename">Rename</a>';
    echo "</div>\n";
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
	// prepare some dynamic property names
    $attr = $_GET['type'].'_attribute';
	$attrId = $attr.'_id';
    echo '<li id="control-'.$control->id.'" class="attribute-'.$control->$attrId.' draggable-control ui-widget ui-widget-content ui-corner-all ui-helper-clearfix">'.
        "<span class=\"handle\">&nbsp;</span>\n".
        '<span class="caption">'.$control->$attr->caption."</span>\n".
        '<a href="'.url::site().'attribute_by_survey/edit/'.$control->id.'?type='.$_GET['type']."\">Edit</a>\n".
		'<a href="" class="control-delete">Delete</a>'.
        "</li>\n";
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
<input type="text" name="new-block" id="new-block" style="width: 200px;" class="line-up" />
<input type="submit" value="Create new block" id="submit-new-block" class="button ui-widget-content ui-corner-all ui-state-default line-up" />
</fieldset>
</form>
<form id="actions-add-existing">
<fieldset> 
<label for="existing-attribute">Existing attribute:</label>
<select id="existing-attribute" name="existing-attribute" style="width: 206px;" class="line-up">
<?php 
foreach ($existingAttrs as $attr) {
  echo '<option value="'.$attr->id.'">'.$attr->caption."</option>\n";
}
?>
</select>
<input type="submit" value="Add existing attribute" id="submit-existing-attribute" class="button ui-widget-content ui-corner-all ui-state-default line-up" />
</fieldset>
</form>