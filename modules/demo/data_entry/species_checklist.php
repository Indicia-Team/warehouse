<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
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
  // We're mainly submitting to the sample model
  $sampleMod = data_entry_helper::wrap($_POST, 'sample');
  $occurrences = data_entry_helper::wrap_species_checklist($_POST);

  // Add the occurrences in as submodels
  $sampleMod['subModels'] = $occurrences;

  // Wrap submission and submit
  $submission = array('submission' => array('entries' => array(
  array ( 'model' => $sampleMod ))));
  $response = data_entry_helper::forward_post_to('save', $submission);
  data_entry_helper::dump_errors($response);
}

?>

<form method='post'>
<?php
// Get authentication information
echo data_entry_helper::get_auth($config['website_id'], $config['password']);
$readAuth = data_entry_helper::get_read_auth($config['website_id'], $config['password']);
?>
<input type='hidden' id='website_id' name='website_id' value='<?php echo $config['website_id']; ?>' />
<input type='hidden' id='survey_id' name='survey_id' value='<?php echo $config['survey_id']; ?>' />
<input type='hidden' id='record_status' name='occurrence:record_status' value='C' />
<label for="sample:date">Date:</label>
<?php echo data_entry_helper::date_picker('sample:date'); ?>
<br />
<?php echo data_entry_helper::map('map', array('google_physical', 'virtual_earth'), true, true, null, true); ?>
<br />
<?php echo data_entry_helper::species_checklist($config['species_checklist_taxon_list'], $config['species_checklist_occ_attributes'], $readAuth); ?>

<br />
<input type='submit' value='Save' />
</form>
</div>
</body>
<?php echo data_entry_helper::dump_javascript(); ?>
</html>
