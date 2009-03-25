<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Indicia demonstrations</title>
</head>
<body>
<h1>Indicia demonstrations</h1>
<?php
	// for first time usage, check that the data entry config file exists.
	if (!file_exists('data_entry/data_entry_config.php')) {
		rename('data_entry/data_entry_config.php.example', 'data_entry/data_entry_config.php');
	}

?>
<p>The following list of demonstration pages illustrate service based Indicia functionality.</p>
<ol>
<li><a href="data_entry/test_data_entry.php">Demonstration basic data entry page</a></li>
<li><a href="data_entry/species_checklist.php">Demonstration checklist based data entry page</a></li>
<li><a href="map.php">Demonstration distribution map</a></li>
<li><a href="valid.php">Simple demonstration of validation</a></li>
<li><a href="occurrence_grid.php">Browse a grid of occurrences captured by this instance of the Core Module.</a></li>
<li><a href="test_treeview.php">Demonstration of 3 term based treeviews.</a></li>
<li><a href="../../index.php/services/data/location?mode=xml&view=detail">List the locations as XML by calling the Data service</a></li>

</ol>
</body>
