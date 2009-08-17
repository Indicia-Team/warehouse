<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<?php

require_once '../../../client_helpers/data_entry_helper.php';
require_once '../../../client_helpers/flickr_helper.php';
require_once '../data_entry_config.php';

// Obtain read access to the user's Flickr account
flickr_helper::auth('read');

?>
<head>
    <title>Indicia demonstrations</title>
    <link rel="stylesheet" type="text/css" href="../../../media/css/thickbox.css" />
    <link rel="stylesheet" type="text/css" href="demo.css" />
    <link rel="stylesheet" type="text/css" href="../../../media/css/default_site.css" />


    <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
</head>
<body>
<div id="wrap"">
<?php
// Catch a submission to the form and send it to Indicia
if ($_POST)
{
  $submission = data_entry_helper::build_sample_occurrence_submission($_POST);
  $response = data_entry_helper::forward_post_to(
    'save', $submission
  );
  data_entry_helper::dump_errors($response);
}

?>
<form method="post" class="ui-widget ui-widget-content ui-corner-all">
<h1 class="ui-widget-header ui-corner-all">Flickr Integration Demo</h1>
<fieldset class="ui-widget ui-widget-content ui-corner-all">
<?php
// Get authentication information
echo data_entry_helper::get_auth($config['website_id'], $config['password']);
$readAuth = data_entry_helper::get_read_auth($config['website_id'], $config['password']);
?>
<input type='hidden' id='website_id' name='website_id' value='<?php echo $config['website_id']; ?>' />
<input type='hidden' id='survey_id' name='survey_id' value='<?php echo $config['survey_id']; ?>' />
<input type='hidden' id='record_status' name='occurrence:record_status' value='C' />
<?php echo flickr_helper::flickr_selector(); ?>
<label for='occurrence:taxa_taxon_list_id:taxon'>Taxon:</label>
<?php echo data_entry_helper::autocomplete('occurrence:taxa_taxon_list_id', 'taxa_taxon_list', 'taxon', 'id', $readAuth); ?>
<br/>
<label for="date">Date:</label>
<?php echo data_entry_helper::date_picker('sample:date'); ?>
<br />
<?php echo data_entry_helper::map('map', array('multimap_landranger'), true, false, null, true); ?>
<br />
<input type="submit" value="Save" />
</fieldset>

</form>
<?php
echo data_entry_helper::dump_javascript();
?>
    <script type='text/javascript' src='http://localhost/indicia/media/js/jquery.flickr.js'></script>
    <script type='text/javascript' src='http://localhost/indicia/media/js/thickbox.js'></script>

</body>
</html>