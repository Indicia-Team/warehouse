
<form class="iform" action="<?php echo url::site(); ?>survey_structure_export/save" method="post" id="entry-form">
<fieldset>
<?php
require_once(DOCROOT.'client_helpers/data_entry_helper.php');
echo "<h1>The Import Has Completed</h1>";
$title = "Import Complete";
echo html::form_buttons(false, false, false);
data_entry_helper::link_default_stylesheet();
data_entry_helper::$dumped_resources[] = 'jquery';
data_entry_helper::$dumped_resources[] = 'jquery_ui';
data_entry_helper::$dumped_resources[] = 'fancybox';
echo data_entry_helper::dump_javascript();
?>
</fieldset>
</form>