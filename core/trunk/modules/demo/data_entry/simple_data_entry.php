<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<?php
include '../../../client_helpers/data_entry_helper.php';
include '../data_entry_config.php';
?>
<title>Occurrence Data Entry</title>
<link rel="stylesheet" href="demo.css" type="text/css" media="screen">
<link rel="stylesheet" href="../../../media/css/default_site.css" type="text/css" media="screen">
</head>
<body>
<div id="wrap">
<h1>Occurrence Data Entry</h1>
<?php
// Catch a submission to the form and send it to Indicia
if ($_POST)
{
  $sampleMod = data_entry_helper::wrap($_POST, 'sample');
  $occurrenceMod = data_entry_helper::wrap($_POST, 'occurrence');
  $occurrenceMod['superModels'][] = array
  (
  'fkId' => 'sample_id',
   'model' => $sampleMod
   );

   $submission = array('submission' => array('entries' => array(
     array ( 'model' => $occurrenceMod )
   )));
   $response = data_entry_helper::forward_post_to(
      'save', $submission
   );
   data_entry_helper::dump_errors($response);
}

?>
<form method="post" >
<fieldset>
<?php
// This PHP call demonstrates inserting authorisation into the form, for website ID
// 1 and password 'password'
echo data_entry_helper::get_auth(1,'password');
$readAuth = data_entry_helper::get_read_auth(1, 'password');
?>
<input type='hidden' id='website_id' name='website_id' value='1' />
<input type='hidden' id='record_status' name='record_status' value='C' />
<label for='actaxa_taxon_list_id'>Taxon</label>
<?php echo data_entry_helper::autocomplete('taxa_taxon_list_id', 'taxa_taxon_list', 'taxon', 'id', $readAuth); ?>
<br/>
<label for="date">Date:</label>
<?php echo data_entry_helper::date_picker('date'); ?>
<br />
<?php echo data_entry_helper::map('map', array('virtual_earth'), true, false, null, true); ?>
<br />
<input type="submit" value="Save" />
</fieldset>

</form>
</div>
</body>
<?php echo data_entry_helper::dump_javascript(); ?>
</html>
