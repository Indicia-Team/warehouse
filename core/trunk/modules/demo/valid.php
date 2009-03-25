<?php 
if (array_key_exists('submission', $_POST)) {
	$url = 'http://localhost/indicia/index.php/services/validation/check';
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
    <title></title>
    <meta content="">
  </head>
  <body>
  <p><?php echo $response; ?></p>
  <form action="" method='post'>
  <textarea style='width: 800px' rows='20' name='submission' id='submission' >{ "fields" :
			{
				"title" :
				{ "value" : "Kernel Site" },
				"description" :
				{ "value" : "Linux kernel."},
				"url" :
				{ "value" : "http://www.kernel.org",
				  "rules" : { "url" : "" ,
					     "required" : "" }
				}
			}
		}
</textarea>
</br>
<input type='submit' value='Submit' />
</form>
</body>
</html>
