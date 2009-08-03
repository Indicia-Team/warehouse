<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<?php require '../../../client_helpers/data_entry_helper.php'; ?>
<?php
  // Include a configuration file - not part of the tutorial. In various places, we refer to the configuration
  // by replacing an ID with echo $config['<<id name>>'];
  require '../data_entry_config.php';
?>
<title>Basic Data Entry Tutorial Code</title>
<link rel="stylesheet" href="demo.css" type="text/css" media="screen">
<link rel="stylesheet" href="../../../media/css/default_site.css" type="text/css" media="screen">
</head>
<body>
<div id="wrap">
<h1>Basic Data Entry Tutorial Code</h1>
<body>
<form method="post">
<?php
  // Get authorisation tokens to update and read from the Core.
  echo data_entry_helper::get_auth($config['website_id'], $config['password']);
  $readAuth = data_entry_helper::get_read_auth($config['website_id'], $config['password']);
?>
<input type='hidden' id='website_id' name='website_id' value='<?php echo $config['website_id']; ?>' />
<input type='hidden' id='record_status' name='record_status' value='C' />
<label for='occurrence:taxa_taxon_list_id:taxon'>Species:</label>
<?php echo data_entry_helper::autocomplete('occurrence:taxa_taxon_list_id', 'taxa_taxon_list', 'taxon', 'id',
  $readAuth + array('taxon_list_id' => $config['species_checklist_taxon_list'])); ?>
<br />
<label for="date">Date:</label>
<?php echo data_entry_helper::date_picker('sample:date'); ?>
<br />
<?php echo data_entry_helper::map('map', array('google_physical'), true, true, null, true); ?>
<br/>
<label for="survey_id">Survey</label>
<?php echo data_entry_helper::select(
        'survey_id', 'survey', 'title', 'id', $readAuth); ?>
<br />
<label for='sample:comment'>Comment</label>
<textarea id='comment' name='sample:comment'></textarea>
<br />
<input type="submit" value="Save" />
</form>
<?php echo data_entry_helper::dump_javascript(); ?>
</html>