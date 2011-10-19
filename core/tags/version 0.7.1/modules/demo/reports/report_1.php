<html>
<?php
require '../../../client_helpers/report_helper.php';
require '../data_entry_config.php';
$readAuth = report_helper::get_read_auth($config['website_id'], $config['password']);
?>
<head>
<title>Report Grid Demo</title>
</head>
<body>
<?php
report_helper::link_default_stylesheet();
echo report_helper::report_grid(array(
  'readAuth' => $readAuth,
  'dataSource' => 'species_occurrence_counts_by_taxon_group'
));
echo report_helper::dump_javascript();
?>
</body>
</html>
