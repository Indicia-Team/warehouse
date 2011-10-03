<?php
include '../../../client_helpers/data_entry_helper.php';
include '../data_entry_config.php';

// Get read only access to the database
$readAuth = data_entry_helper::get_read_auth($config['website_id'], $config['password']);

// Find the location of the web service to provide reports
$svcUrl = data_entry_helper::$base_url.'index.php/services/report/requestReport';

// Redirect to the report content
header('Location:'.$svcUrl.'?report=occurrences_by_taxon_group.xml&'.
		'auth_token='.$readAuth['auth_token'].'&nonce='.$readAuth['nonce'].'&mode=csv');

?>