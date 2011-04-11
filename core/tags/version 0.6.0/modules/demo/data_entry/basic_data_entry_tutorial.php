<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<?php 
require '../../../client_helpers/data_entry_helper.php'; 

// Include a configuration file - not part of the tutorial. In various places, we refer to the configuration
// by replacing an ID with echo $config['<<id name>>'];
require '../data_entry_config.php';
?>
<title>Basic Data Entry Tutorial Code</title>
<link rel="stylesheet" href="demo.css" type="text/css" media="screen">
<link rel="stylesheet" href="../../../media/css/default_site.css" type="text/css" media="screen">
<?php echo data_entry_helper::dump_header(); ?>
</head>
<body>
<div id="wrap">
<h1>Basic Data Entry Tutorial Code</h1>
<?php
if ($_POST) {
  $submission = data_entry_helper::build_sample_occurrence_submission($_POST);
  $response = data_entry_helper::forward_post_to('save', $submission);
  echo data_entry_helper::dump_errors($response);
}
echo data_entry_helper::loading_block_start();
?>
<p>This data entry page illustrates the final results of a data entry page built using the
<a href="http://code.google.com/p/indicia/wiki/TutorialBuildingBasicPage">Building a Basic Data Entry Page</a> tutorial.
<form method="post">
<?php
  // Get authorisation tokens to update and read from the Warehouse.
  $auth = data_entry_helper::get_read_write_auth($config['website_id'], $config['password']);
  echo $auth['write'];
  $readAuth = $auth['read'];
?>
<input type='hidden' id='website_id' name='website_id' value='<?php echo $config['website_id']; ?>' />
<input type='hidden' id='record_status' name='record_status' value='C' />
<?php
echo data_entry_helper::autocomplete(array(
    'label'=>'Species',
    'fieldname'=>'occurrence:taxa_taxon_list_id',
    'table'=>'taxa_taxon_list',
    'captionField'=>'taxon',
    'valueField'=>'id',
    'extraParams'=>$readAuth + array('taxon_list_id' => $config['species_checklist_taxon_list'])
));
echo data_entry_helper::date_picker(array(
    'label'=>'Date',
    'fieldname'=>'sample:date'
));
echo data_entry_helper::text_input(array(
	'label' => 'Temperature',
	'fieldname' => 'smpAttr:2'
));
echo data_entry_helper::map();
echo data_entry_helper::select(array(
    'label'=>'Survey',
    'fieldname'=>'sample:survey_id',
    'table'=>'survey',
    'captionField'=>'title',
    'valueField'=>'id',
    'extraParams' => $readAuth
));
echo data_entry_helper::textarea(array(
    'label'=>'Comment',
    'fieldname'=>'sample:comment',
    'class'=>'wide',
));
?>

<input type="submit" class="ui-state-default ui-corner-all" value="Save" />
</form>
<?php
echo data_entry_helper::loading_block_end();
echo data_entry_helper::dump_remaining_errors(); 
echo data_entry_helper::dump_javascript(); 
?>
</div>
</body>
</html>