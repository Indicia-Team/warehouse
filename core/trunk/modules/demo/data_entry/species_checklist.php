<html>
<head>
<title>Indicia external site species checklist test page</title>
<link rel="stylesheet" href="demo.css" type="text/css" media="screen">
<link rel="stylesheet" href="../../../media/css/default_site.css" type="text/css" media="screen">
</head>
<body>
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
 $response = data_entry_helper::forward_post_to(
 'save', $submission);
 data_entry_helper::dump_errors($response);
 }

 ?>
 <form method='post'>
 <?php
 // This PHP call demonstrates inserting authorisation into the form, for website ID
 // 1 and password 'password'
 echo data_entry_helper::get_auth(1,'password');
 $readAuth = data_entry_helper::get_read_auth(1, 'password');
 ?>
 <input type='hidden' id='website_id' name='website_id' value='1' />
 <input type='hidden' id='record_status' name='record_status' value='C' />
 <input type='hidden' id='determiner_id' name='determiner_id' value='1' />
 <label for="date">Date:</label>
 <input type="text" size="30" value="click here" id="sampleDate" name="date" />
 <style type="text/css">.embed + img { position: relative; left: -21px; top: -1px; }</style>
 <br />
 <?php echo data_entry_helper::map('map', array('google_physical', 'virtual_earth'), true, true, null, true); ?>
 <br />
 <?php echo data_entry_helper::species_checklist($config['species_checklist_taxon_list'], $config['species_checklist_occ_attributes'], $readAuth); ?>

 <br />
 <input type='submit' value='submit' />
 </form>
 </body>
 <?php echo data_entry_helper::dump_javascript(); ?>
 </html>
