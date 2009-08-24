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
<?php echo html::error_message($model->getError('termlist_id')); ?>
<form class="cmxform"  name='editList' action="<?php echo url::site().'termlists_term/save' ?>" method="POST">
<?php echo $metadata ?>
<fieldset>
<legend>Term Details</legend>
<input type="hidden" name="id" id="id" value="<?php echo html::specialchars($model->id); ?>" />
<input type="hidden" name="termlist_id" id="termlist_id" value="<?php echo html::specialchars($model->termlist_id); ?>" />
<ol>
<li>
<input type="hidden" name="term_id" id="term_id" value="<?php echo html::specialchars($model->term_id); ?>" />
<label for="term">Term Name</label>
<input id="term" name="term" value="<?php echo (($model->term_id != null) ? html::specialchars($model->term->term) : ''); ?>"/>
<?php echo html::error_message($model->getError('term')); ?>
</li>
<li>
<label for="language_id">Language</label>
<select id="language_id" name="language_id">
  <option>&lt;Please select&gt;</option>
<?php
  $languages = ORM::factory('language')->orderby('language','asc')->find_all();
  foreach ($languages as $lang) {
    echo '	<option value="'.$lang->id.'" ';
    if ($model->term_id != null && $lang->id==$model->term->language_id) {
      echo 'selected="selected" ';
    }
    echo '>'.$lang->language.'</option>';
  }
?>
<?php echo html::error_message($model->getError('language_id')); ?>
</li>
<li>
<input type="hidden" name="parent_id" id="parent_id" value="<?php echo html::specialchars($model->parent_id); ?>" />
<label for="parent">Parent Term</label>
<input id="parent" name="parent" readonly="readonly" value="<?php echo (($model->parent_id != null) ? html::specialchars(ORM::factory('termlists_term', $model->parent_id)->term->term) : ''); ?>" />
</li>
<li>
<input type="hidden" name="meaning_id" id="meaning_id" value="<?php echo html::specialchars($model->meaning_id); ?>" />
<label for="synonyms">Synonyms
<span class="ui-state-highlight ui-widget-content ui-corner-all" title="Enter synonyms one per line. Optionally follow each name by a | character then the 3 character code for the language, e.g. 'Countryside | eng'.">?</span></label>
<textarea rows=7 id="synonyms" name="synonyms"><?php echo html::specialchars($synonyms); ?></textarea>
</li>
<li>
<label for="sort_order">Sort Order in List</label>
<input id="sort_order" name="sort_order" class="narrow" value="<?php echo html::specialchars($model->sort_order); ?>" />
<?php echo html::error_message($model->getError('sort_order')); ?>
</li>
</fieldset>
<input type="submit" name="submit" value="Submit" />
<input type="submit" name="submit" value="Delete" />
<?php echo html::error_message($model->getError('deleted')); ?>
</form>

<?php if ($model->id != '' && $table != null) { ?>
  <br />
  <h2> Child Terms </h2>
  <?php echo $table; ?>
<form class="cmxform" action="<?php echo url::site(); ?>termlists_term/create/<?php echo $model->termlist_id; ?>" method="post">
  <input type="hidden" name="parent_id" value=<?php echo $model->id ?> />
  <input type="submit" value="New Child Term" />
  </form>
<?php } ?>
