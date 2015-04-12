<p>This tab allows you to generate a piece of text which describes the terms in a termlist. The text generated can
  be used to recreate the same termlist on another warehouse. It is therefore ideal for migrating development
  or test versions of your surveys to the live warehouse server.</p>
<?php
require_once(DOCROOT.'client_helpers/data_entry_helper.php');
echo data_entry_helper::textarea(array(
  'label'=>'Exported termlist contents',
  'fieldname'=>'export_termlist_contents',
  'cols'=>100,
  'rows'=>8,
  'helpText' => 'Copy this text to the clipboard. You can then paste it into another termlist on another warehouse to clone the content.',
  'default' => $export
));
?>
<form class="iform" action="<?php echo url::site(); ?>termlist_export/save" method="post" id="entry-form"">
<fieldset>
<?php
echo data_entry_helper::textarea(array(
  'label'=>'Import termlist contents',
  'fieldname'=>'import_termlist_contents',
  'cols'=>100,
  'rows'=>8,
  'helpText' => 'Paste in the export of another termlist to import its terms.'
));
echo data_entry_helper::hidden_text(array(
  'fieldname'=>'termlist_id',
  'default'=>$termlistId
));

echo '<input type="submit" name="submit" value="Import" class="ui-corner-all ui-state-default button ui-priority-primary" />'."\n"; 
data_entry_helper::link_default_stylesheet();
echo data_entry_helper::dump_javascript();
?>
</fieldset>
</form>