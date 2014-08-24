<?php
require_once(DOCROOT.'client_helpers/data_entry_helper.php');
echo data_entry_helper::textarea(array(
  'label'=>'Exported survey structure',
  'fieldname'=>'export survey structure',
  'class'=>'control-width-6',
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
  'class'=>'control-width-6',
  'helpText' => 'Paste in the export of another survey to import its attributes'
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