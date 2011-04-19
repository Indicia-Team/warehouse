<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

<title>Indicia external site species checklist test page</title>
<link rel="stylesheet" href="demo.css" type="text/css" media="screen">
<link rel="stylesheet" href="../../../media/css/default_site.css" type="text/css" media="screen">
</head>
<body>
<div id="wrap">
<h1>Indicia Species Checklist Test</h1>
<?php
include '../../../client_helpers/data_entry_helper.php';
include '../data_entry_config.php';
$javascript = '';
// Catch and submit POST data.
if ($_POST){
  $submission = data_entry_helper::build_sample_occurrences_list_submission($_POST);
  $response = data_entry_helper::forward_post_to('save', $submission);
  echo data_entry_helper::dump_errors($response);
}

?>

<form method='post'>
<?php
  // Get authorisation tokens to update and read from the Warehouse.
  $auth = data_entry_helper::get_read_write_auth($config['website_id'], $config['password']);
  echo $auth['write'];
  $readAuth = $auth['read'];
?>
<input type='hidden' name='website_id' value='<?php echo $config['website_id']; ?>' />
<input type='hidden' name='survey_id' value='<?php echo $config['survey_id']; ?>' />
<input type='hidden' name='occurrence:record_status' value='C' />
<?php 
echo data_entry_helper::date_picker(array(
    'label'=>'Date',
    'fieldname'=>'sample:date'
));
echo data_entry_helper::sref_and_system(array(
  'label' => 'Grid Ref',
  'fieldname' => 'sample:entered_sref'
));
?>
<div class="smaller">
<?php 
echo data_entry_helper::species_checklist(array(
    'listId'=>$config['species_checklist_taxon_list'],
    'lookupListId'=>$config['species_checklist_alt_list'],
    'extraParams'=>$readAuth,
	  'survey_id'=>$config['survey_id']
));
?>
</div>

<br />
<input type='submit' value='Save' />
</form>
</div>
</body>
<?php echo data_entry_helper::dump_javascript(); ?>
</html>
