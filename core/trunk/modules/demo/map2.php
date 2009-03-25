<html>
<head>
<?php
	include '../../client_helpers/map_helper.php';
	$myMap = new Map('http://localhost:8080/geoserver/', array(0,1,2,3,4,5,6,8), null, array('input_field_id' => 'entered_sref'));
?>
<title>Map helper test</title>
</head>
<body>
<?php echo $myMap->render(); ?>
</body>
</html>
