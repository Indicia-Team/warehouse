<p>This tab allows you to generate a piece of text which describes the structure of the custom attributes associated with 
a survey. The text generated can be used to recreate the same attribute structure on another survey on this warehouse
or on another warehouse. It is therefore ideal for migrating development or test versions of your surveys to the live
warehouse server.</p>
<?php
require_once(DOCROOT.'client_helpers/data_entry_helper.php');
echo data_entry_helper::textarea(array(
  'label'=>'Exported survey structure',
  'fieldname'=>'export survey structure',
  'cols'=>100,
  'rows'=>8,
  'helpText' => 'Copy this text to the clipboard. You can then paste it into another survey on this or another warehouse to clone the attributes.',
  'default' => $export
));
?>
<form class="iform" action="<?php echo url::site(); ?>survey_structure_export/save" method="post" id="entry-form"">
<fieldset>
<?php
echo data_entry_helper::textarea(array(
  'label'=>'Import survey structure',
  'fieldname'=>'import survey structure',
  'cols'=>100,
  'rows'=>8,
  'helpText' => 'Paste in the export of another survey to import its attributes.'
));
echo data_entry_helper::hidden_text(array(
  'fieldname'=>'survey_id',
  'default'=>$surveyId
));

echo '<input type="submit" name="submit" value="Import" class="ui-corner-all ui-state-default button ui-priority-primary" />'."\n"; 
data_entry_helper::link_default_stylesheet();
echo data_entry_helper::dump_javascript();
?>
</fieldset>
</form>