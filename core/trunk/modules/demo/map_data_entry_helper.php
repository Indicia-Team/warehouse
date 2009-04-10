<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<html>
<head>
<title>Map helper test</title>
<link rel="stylesheet" href="demo.css" type="text/css" media="screen">
</head>
<body>
<div id="wrap">
<h1>Data Entry Helper Map</h1>
<?php
include '../../client_helpers/data_entry_helper.php';
echo data_entry_helper::map('map', array('google_physical', 'virtual_earth'), true, true, null, true);
echo data_entry_helper::dump_javascript();
?>
</div>
</body>
</html>
