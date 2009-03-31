<?php include '../../client_helpers/data_entry_helper.php'; ?>
<html>
<head>
<title>Map helper test</title>
</head>
<body>
<?php 
echo data_entry_helper::map('map', array('google_physical', 'virtual_earth'), true, true, true);
echo data_entry_helper::dump_javascript();
?>
</body>
</html>
