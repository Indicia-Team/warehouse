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

warehouse::loadHelpers(['data_entry_helper']);
?>
<form action="" method="get" class="form-inline">
<?php

echo data_entry_helper::select(array(
  'fieldname' => 'type',
  'label' => 'Display attributes for',
  'lookupValues' => array(
    'sample' => 'Samples',
    'occurrence' => 'Occurrences',
    'location' => 'Locations',
  ),
  'default' => $_GET['type'],
));
?>
<input type="submit" class="btn btn-default" id="change-type" value="Go" />
</form>
<div id="attribute-by-survey-index">
<ul id="top-blocks" class="block-list">
<?php
foreach ($top_blocks as $block) {
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
get_controls(NULL, $controlfilter);

/**
 * Echos the list of controls inside a block level.
 *
 * @param string $block_id
 *   ID of the block, or NULL for top level.
 * @param array
 *   Filter to apply, e.g. to the correct survey.
 */
function get_controls($block_id, array $controlfilter) {
  $id = "controls";
  if ($block_id) {
    $id .= '-for-block-' . $block_id;
  }
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
    $attrIdField = $attr . '_id';
    $attrId = $control->$attrIdField;
    $caption = $control->$attr->caption;
    $siteUrl = url::site();
    echo <<<HTML
<li id="control-$control->id" class="$attrId draggable-control panel panel-primary clearfix">
  <span class="handle\">&nbsp;</span>
  <span class="caption"> $caption (ID {$attrId})</span>
  <a class="control-delete pull-right btn btn-warning btn-xs">Delete</a>
  <a href="{$siteUrl}attribute_by_survey/edit/$control->id?type=$_GET[type]" class="pull-right btn btn-default btn-xs">Survey settings</a>
  <a href="$siteUrl$_GET[type]_attribute/edit/{$control->$attrIdField}" class="pull-right btn btn-default btn-xs">Global settings</a>
</li>
HTML;
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
<?php
// The JavaScript needs a list of attribute captions.
$attrsData = [];
foreach ($existingAttrs as $attr) {
  $attrs["id$attr->id"] = $attr->caption;
}
data_entry_helper::$javascript .= "indiciaData.existingAttrs = " . json_encode($existingAttrs) . ";\n";
