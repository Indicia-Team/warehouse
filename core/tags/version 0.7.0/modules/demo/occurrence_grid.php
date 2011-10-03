<html>
<?php
include '../../client_helpers/report_helper.php';
include 'data_entry_config.php';
$readAuth = report_helper::get_read_auth($config['website_id'], $config['password']);
$svcUrl = report_helper::$base_url.'/index.php/services';
?>

<head>
<title>Occurrence Grid Demo</title>
</head>
<body>
<?php
report_helper::link_default_stylesheet();
echo report_helper::report_grid(array(
  'readAuth' => $readAuth,
  'dataSource' => 'occurrence',
  'mode'=>'direct',
  'columns' => array(array(
    'display' => 'Actions',
    'actions' => array(array(
      'caption'=>'edit',
      'url' => '{rootFolder}occurrence.php',
      'urlParams' => array('id'=>'{id}')
    ))
  ))
));
echo report_helper::dump_javascript();
?>
</body>
</html>