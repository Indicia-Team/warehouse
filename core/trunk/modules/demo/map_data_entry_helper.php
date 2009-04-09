<?php include '../../client_helpers/data_entry_helper.php'; ?>
<html>
<head>
<title>Map helper test</title>
<link rel="stylesheet" href="demo.css" type="text/css" media="screen">
</head>
<body>
<div id="wrap">
<h1>Data Entry Helper Map</h1>
<?php
echo data_entry_helper::map('map', array('google_physical', 'virtual_earth'), true, true, null, true);
echo data_entry_helper::dump_javascript();
?>
</div>
</body>
</html>
