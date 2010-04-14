<html>
<?php
include '../../../client_helpers/data_entry_helper.php';
include '../data_entry_config.php';
$readAuth = data_entry_helper::get_read_auth($config['website_id'], $config['password']);
$svcUrl = data_entry_helper::$base_url.'index.php/services';
?>
<head>
<link rel='stylesheet' type='text/css' href='../../../media/css/datagrid.css' />
<link rel='stylesheet' type='text/css' href='../../../media/themes/default/jquery-ui.custom.css' />
<script type='text/javascript' src='../../../media/js/jquery.js' ></script>
<script type='text/javascript' src='../../../media/js/hasharray.js' ></script>
<script type='text/javascript' src='../../../media/js/jquery.datagrid.js' ></script>
<title>Report Grid Demo</title>
<style type="text/css">
#charts .ui-widget-header {
  margin: 0.2em;
  padding: 0.2em;
}

#charts .ui-widget {
  margin: 1em;
}
</style>
</head>
<body>
<?php
data_entry_helper::link_default_stylesheet();
$readAuth = data_entry_helper::get_read_auth($config['website_id'], $config['password']); 
echo data_entry_helper::report_grid(array(
  'dataSource' => 'occurrences_by_survey',
  'mode' => 'report',
  'readAuth' => $readAuth,
  'columns' => array('survey'=>array('caption' => 'Survey Title'), 'count' => array('caption' => 'Number of Occurrences')),
  'itemsPerPage' => 10
));
echo "<div id=\"charts\">\n";
echo data_entry_helper::report_chart(array(
  'title' => 'Bar chart of record count per survey',
  'id' => 'barChart',
  'dataSource' => 'occurrences_by_survey',
  'mode' => 'report',
  'readAuth' => $readAuth,
  'chartType' => 'bar',
  'yValues' => 'count',
  'xLabels' => 'survey',
  'width' => 600
));
echo data_entry_helper::report_chart(array(
  'title' => 'Demonstration line chart (y=survey website id, x=survey id)',
  'id' => 'lineChart',
  'dataSource' => 'survey',
  'mode' => 'direct',
  'readAuth' => $readAuth,
  'chartType' => 'line',
  'yValues' => 'website_id',
  'xValues' => 'id',
  'width' => 600
));
echo data_entry_helper::report_chart(array(
  'title' => 'Demonstration pie chart of occurrences per survey',
  'id' => 'pieChart',
  'dataSource' => 'occurrences_by_survey',
  'mode' => 'report',
  'readAuth' => $readAuth,
  'chartType' => 'pie',
  'yValues' => 'count',
  'xLabels' => 'survey',
  'legendOptions' => array('show' => true),
  'width' => 500
));
echo "</div>";
echo data_entry_helper::dump_javascript();
?> 
</body>
</html>
