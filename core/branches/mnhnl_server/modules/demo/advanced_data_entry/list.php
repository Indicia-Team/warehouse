<?php
session_start();
require '../../../client_helpers/data_entry_helper.php';
require '../data_entry_config.php';
$base_url = helper_config::$base_url;
$readAuth = data_entry_helper::get_read_auth($config['website_id'], $config['password']);
// Store data from the previous page into the session
data_entry_helper::add_post_to_session();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>Enter the list of observed species</title>
<link rel="stylesheet" href="advanced.css" type="text/css" media="screen" />
<link rel="stylesheet" href="../../../media/css/default_site.css" type="text/css" media="screen">
</head>
<body>
<div id="wrap">
<h1>Species Checklist</h1>
<p>Now please enter details of the list of species you observed in this sample.</p>
<form action="save.php" method="post">
<fieldset>
<legend>Species List</legend>
<?php echo data_entry_helper::get_auth($config['website_id'], $config['password']); ?>
<input type="hidden" class="auto" name="website_id" id="website_id" value="<?php echo $config['website_id']; ?>" />
<input type="hidden" class="auto" name="survey_id" id="website_id" value="<?php echo $config['survey_id']; ?>" />
<?php echo data_entry_helper::species_checklist(
    $config['species_checklist_taxon_list'],
    $config['species_checklist_occ_attributes'],
    $readAuth,
    null,
    $config['species_checklist_alt_list']); ?>
<br />
<input type="submit" value="Save" class="auto" />
</fieldset>
</form>
</div>
<?php echo data_entry_helper::dump_javascript(); ?>
</body>
</html>