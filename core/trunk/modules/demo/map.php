<html>
<head>
<?php
	include '../../client_helpers/map_helper.php';
	$myMap = new Map('http://localhost:8080/geoserver/', array(0,1,2,3,4,5,6,8));
	$myMap->addIndiciaWFSLayer('Samples', 'topp:samples');
?>
<title>Map helper test</title>
</head>
<body>
<?php echo $myMap->render(); ?>
</body>
</html>
