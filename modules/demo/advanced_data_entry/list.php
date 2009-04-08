<?php
require '../../../client_helpers/data_entry_helper.php';
require '../data_entry_config.php';
$base_url = helper_config::$base_url;
$readAuth = data_entry_helper::get_read_auth($config['website_id'], $config['password']);
// Store data from the previous page into the session
data_entry_helper::add_post_to_session();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN">
<html>
<head>
<title>Indicia external site data entry test page</title>
<link rel="stylesheet" href="advanced.css" type="text/css" media="screen" />
<link rel="stylesheet" href="../../../media/css/default_site.css" type="text/css" media="screen">
</head>
<body>
<div id="wrap">
<h1>Species Checklist</h1>
<p>Now please enter details of the list of species you observed in this sample.</p>
<form action="list.php" method="post" enctype="text/plain">
<fieldset>
<legend>Species List</legend>
<?php echo data_entry_helper::species_checklist(
    $config['species_checklist_taxon_list'],
    $config['species_checklist_occ_attributes'],
    $readAuth,
    null,
    $config['species_checklist_alt_list']); ?>
</fieldset>
</form>
</div>
</body>
</html>