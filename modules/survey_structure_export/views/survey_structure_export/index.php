<p>
  This tool allows you to generate a piece of text which describes the 
  structure of the custom attributes associated with a survey. The text
  generated can be used to recreate the same attribute structure on another
  survey on this warehouse or on another warehouse. It is therefore ideal for
  migrating development or test versions of your surveys to the live
  warehouse server.
</p>
<?php
warehouse::loadHelpers(['data_entry_helper']);
echo data_entry_helper::textarea([
  'label' => 'Exported survey structure',
  'fieldname' => 'export survey structure',
  'helpText' => 'Copy this text to the clipboard. You can then paste it into ' .
  'another survey on this or another warehouse to clone the attributes.',
  'default' => $export,
]);
?>
<form 
  id="survey-structure-import" 
  action="<?php echo url::site(); ?>survey_structure_export/save" 
  method="post" 
  id="entry-form"
>
  <fieldset>
    <?php
    echo data_entry_helper::textarea([
      'label' => 'Import survey structure',
      'fieldname' => 'import survey structure',
      'helpText' => 'Paste in the export of another survey to import its attributes.',
      'validation' => ['required'],
    ]);
    echo data_entry_helper::hidden_text([
      'fieldname' => 'survey_id',
      'default' => $surveyId,
    ]);

    echo '<input type="submit" name="submit" value="Import" class="btn btn-primary" />' . "\n";
    data_entry_helper::enable_validation('survey-structure-import');
    echo data_entry_helper::dump_javascript();
    ?>
  </fieldset>
</form>
