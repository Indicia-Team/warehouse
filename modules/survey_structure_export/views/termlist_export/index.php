<p>
  This tool allows you to generate a piece of text which describes the terms
  in a termlist. The text generated can be used to recreate the same termlist
  on another warehouse. It is therefore ideal for migrating development or test
  versions of your surveys to the live warehouse server.
</p>
<?php
warehouse::loadHelpers(['data_entry_helper']);
echo data_entry_helper::textarea([
  'label' => 'Exported termlist contents',
  'fieldname' => 'export_termlist_contents',
  'helpText' => 'Copy this text to the clipboard. You can then paste it into ' .
  'another termlist on another warehouse to clone the content.',
  'default' => $export,
]);
?>
<form id="termlist_structure_export"
  action="<?php echo url::site(); ?>termlist_export/save"
  method="post"
  id="entry-form"
>
  <fieldset>
    <?php
    echo data_entry_helper::textarea([
      'label' => 'Import termlist contents',
      'fieldname' => 'import_termlist_contents',
      'helpText' => 'Paste in the export of another termlist to import its terms.',
      'validation' => ['required'],
    ]);
    echo data_entry_helper::hidden_text([
      'fieldname' => 'termlist_id',
      'default' => $termlistId,
    ]);

    echo '<input type="submit" name="submit" value="Import" class="btn btn-primary" />' . "\n";
    data_entry_helper::link_default_stylesheet();
    echo data_entry_helper::dump_javascript();
    ?>
  </fieldset>
</form>
