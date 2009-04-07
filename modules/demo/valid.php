<?php
include '../../client_helpers/helper_config.php';
$base_url = helper_config::$base_url;

if (array_key_exists('submission', $_POST)) {
	$url = "$base_url/index.php/services/validation/check";
	$postargs = 'submission='.$_POST['submission'];
	$session = curl_init($url);
	curl_setopt($session, CURLOPT_POST, true);
	curl_setopt ($session, CURLOPT_POSTFIELDS, $postargs);
	curl_setopt($session, CURLOPT_HEADER, false);
	curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
	$response = curl_exec($session);
} else {
	$response = null;
}
?>
<html>
  <head>
    <title>Validating a JSON submission</title>
    <meta content="">
  </head>
  <body>
  <p><?php echo $response; ?></p>
  <form action="" method='post'>
  <p>Note that in most cases, data will be validated when submitted so there is no need for an explicit call to the
  validation service. However sometimes it is useful to perform validation earlier, such as when the focus leaves a field.</p>
  <p>The following submission contains a valid website entry.
  However, try clearing the title, or making the url invalid then submitting to see what happens.</p>

  <textarea style='width: 800px' rows='20' name='submission' id='submission' >{ "fields" :
{
	"title" : {
		"value" : "National Biodiversity Network Gateway",
		"rules" : { "required" : "" }
	},
	"description" :	{
		"value" : "The NBN Gateway home page."
	},
	"url" : {
		"value" : "http://data.nbn.org.uk/",
		"rules" : {
			"url" : "" ,
			"required" : ""
		}
	}
}
}
</textarea>
</br>
<input type='submit' value='Submit' />
</form>
</body>
</html>
