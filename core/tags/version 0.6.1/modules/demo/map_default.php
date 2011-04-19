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
<h1>Default Map</h1>
<?php
include '../../client_helpers/data_entry_helper.php';
require 'data_entry_config.php';

/*
 * This is the single line of code that is required to output a default map with a spatial reference entry
 * control and place search box.
 */
echo data_entry_helper::map();

echo data_entry_helper::dump_javascript();
?>
</div>
</body>
</html>
