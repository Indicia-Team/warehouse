<?php
session_start();
require '../../../client_helpers/data_entry_helper.php';
require '../data_entry_config.php';
$base_url = helper_config::$base_url;
$readAuth = data_entry_helper::get_read_auth($config['website_id'], $config['password']);
// Store data from the previous page into the session
data_entry_helper::add_post_to_session();
// To post our data, we need to get the whole lot from the session
$data = data_entry_helper::extract_session_array();
// Collect up the sample, sample attributes and grid data
$sampleMod = data_entry_helper::wrap($data, 'sample');
$smpAttrs = data_entry_helper::wrap_attributes($data, 'sample');
$occurrences = data_entry_helper::wrap_species_checklist($data);

// Add the occurrences in as submodels
$sampleMod['subModels'] = $occurrences;
// and link in the attributes of the sample
$sampleMod['metaFields']['smpAttributes']['value'] = $smpAttrs;

// Wrap submission and submit
$submission = array('submission' => array(
    'entries' => array(array ( 'model' => $sampleMod ))
));

$response = data_entry_helper::forward_post_to('save', $submission);
if (array_key_exists('success', $response)) {
  // on success, redirect to the thank you page
  header('Location:success.php');
  die();
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>Saving data</title>
<link rel="stylesheet" href="advanced.css" type="text/css" media="screen" />
<link rel="stylesheet" href="../../../media/css/default_site.css" type="text/css" media="screen">
</head>
<body>
<div id="wrap">
<!-- We shouldn't get this far as a success causes redirection -->
<h1>An error occurred</h1>
<?php
  echo data_entry_helper::dump_errors($response, false);
?>
</div>
</body>
</html>