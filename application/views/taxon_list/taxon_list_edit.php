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
 * @package	Core
 * @subpackage Views
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */
?>
<script type="text/javascript">
  $(document).ready(function(){
    var $tabs=$("#tabs").tabs();
    var initTab='<?php echo array_key_exists('tab', $_GET) ? $_GET['tab'] : '' ?>';
    if (initTab!='') {
      $tabs.tabs('select', '#' + initTab);
    }
  });
</script>

<?php if ($model->parent_id != null) : ?>
<h1>Subset of:
<a href="<?php echo url::site() ?>taxon_list/edit/<?php echo $model->parent_id ?>" >
<?php echo ORM::factory("taxon_list",$model->parent_id)->title ?>
</a>
</h1>
<?php endif; ?>
<div id="tabs">
  <ul>
    <li><a href="#details"><span>List Details</span></a></li>
<?php if ($model->id != null) : ?>
    <li><a href="<?php echo url::site().'taxa_taxon_list/'.$model->id; ?>" title="taxa"><span>Taxa</span></a></li>
    <li><a href="#sublists"><span>Child Lists</span></a></li>
<?php endif; ?>
  </ul>
<div id="details">
<form class="cmxform"  name='editList' action="<?php echo url::site().'taxon_list/save' ?>" method="POST">
<?php echo $metadata ?>
<fieldset>
<legend>List Details</legend>
<ol>
<li>
<input type="hidden" name="id" id="id" value="<?php echo html::specialchars($model->id); ?>" />
<input type="hidden" name="parent_id" id="parent_id" value="<?php echo html::specialchars($model->parent_id); ?>" />
<label for="title">Title</label>
<input id="title" name="title" value="<?php echo html::specialchars($model->title); ?>"/>
<?php echo html::error_message($model->getError('title')); ?>
</li>
<li>
<label for="description">Description</label>
<textarea rows=7 id="description" name="description"><?php echo html::specialchars($model->description); ?></textarea>
<?php echo html::error_message($model->getError('description')); ?>
</li>
<li>
<label for="website">Owned by</label>
<?php if ($model->parent_id != null && $model->parent->website_id != null) { ?>
<input type="hidden" id="website_id" name="website_id" value="<?php echo $model->parent->website_id; ?>" />
<?php } ?>
<select id="website_id" name="website_id" <?php if ($model->parent_id != null && $model->parent->website_id != null) echo "disabled='disabled'"; ?>>
  <option value=''>&lt;Warehouse&gt;</option>
<?php
  if (!is_null($this->auth_filter))
    $websites = ORM::factory('website')->in('id',$this->auth_filter['values'])->orderby('title','asc')->find_all();
  else
    $websites = ORM::factory('website')->orderby('title','asc')->find_all();
  foreach ($websites as $website) {
    echo '	<option value="'.$website->id.'" ';
    if ($website->id==$model->website_id)
      echo 'selected="selected" ';
    echo '>'.$website->title.'</option>';
  }
?>
</select>
<?php echo html::error_message($model->getError('website_id')); ?>
</li>
</ol>
</fieldset>
<input type="submit" name="submit" value="Submit" />
<input type="submit" name="submit" value="Delete" />
</form>
</div>
<div id="taxa"></div>
<?php if ($model->id != '' && $table != null) : ?>
  <div id="sublists">
  <h2> Sublists </h2>
  <?php echo $table; ?>
  <form class="cmxform" action="<?php echo url::site(); ?>/taxon_list/create" method="post">
  <input type="hidden" name="parent_id" value=<?php echo $model->id ?> />
  <input type="submit" value="New Sublist" />
  </form>
  </div>
<?php endif; ?>
