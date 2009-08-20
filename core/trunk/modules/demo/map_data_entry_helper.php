<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<html>
<head>
<title>Map helper test</title>
<link rel="stylesheet" href="demo.css" type="text/css" media="screen">
<link rel="stylesheet" href="../../media/css/default_site.css" type="text/css" media="screen">
</head>
<body>
<div id="wrap">
<h1>Data Entry Helper Map</h1>
<?php
include '../../client_helpers/data_entry_helper.php';
require 'data_entry_config.php';
$readAuth = data_entry_helper::get_read_auth($config['website_id'], $config['password']);
?>
<label for="imp-sref">Spatial ref:</label>
<?php
echo data_entry_helper::sref_and_system();
?><br/>
<label for="imp-location_select">Select a known place:</label>
<?php echo data_entry_helper::location_select(array('extraParams'=>$readAuth)); ?>
<label for="imp-georef-lookup">Search for place:</label>
<?php echo data_entry_helper::georeference_lookup();
echo data_entry_helper::map_panel();

echo data_entry_helper::dump_javascript();
?>
</div>
</body>
</html>
