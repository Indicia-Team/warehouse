<?php
include '../../client_helpers/helper_config.php';
$base_url = helper_config::$base_url;
?>
<html>
  <head>
    <title>Validating a set of fields</title>
    <meta content="">
    <script type='text/javascript' src='../../media/js/jquery.js' ></script>
	<script type='text/javascript' src='../../media/js/json2.js' ></script>
    <script type='text/javascript'>

function check_date() {
	var json = '{"fields":{"date":{"value":"' + $('#date').attr('value') + '","rules":{"vague_date"}}}}';
	var callback = function(data) {
		alert(data);
	}
	$.post("<?php echo $base_url; ?>/index.php/services/validation/check", {'submission': json}, callback, "json");
}

    </script>
  </head>
 <body>
 <form>
 <label for="date">Date:</label>
 <input id="date" type="text" onblur="check_date();"/>
 <br/>
 </form>
 </body>
 </html>