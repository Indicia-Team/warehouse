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
<script type='text/javascript'>
(function($) {
$(document).ready(function(){
  $('div#grid').indiciaDataGrid('rpt:occurrences_by_survey', {
    indiciaSvc: "<?php echo $svcUrl; ?>",
    auth : { nonce : "<?php echo $readAuth['nonce']; ?>", auth_token : "<?php echo $readAuth['auth_token']; ?>"}
  });
});
})(jQuery);
</script>
<title>Report Grid Demo</title>
</head>
<body>
<div id='grid'>
</div>
</body>
</html>
