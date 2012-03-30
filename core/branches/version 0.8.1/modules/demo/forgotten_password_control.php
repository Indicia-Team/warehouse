<?php // DEMO PAGE FOR REMOTE USER FORGOTTEN PASSWORD RECOVERY
require_once '../../client_helpers/user_helper.php';
require_once '../../client_helpers/helper_config.php';
require_once 'data_entry_config.php';
$base_url = helper_config::$base_url;
$website_password=$config['password'];
$website_id=$config['website_id'];
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>Demo forgotten password page</title>
<link rel="stylesheet" href="demo.css" type="text/css" media="screen" />
<style type="text/css">
#requirements {
	margin-left: 20px;
}
/* some example CSS styling for the indicia forgotten password control */
#indicia-forgotten-password-control {
	margin-left: 150px;
	font-family: "Arial", "Helvetica", sans-serif;
}

#indicia-forgotten-password-control fieldset {
	width: 450px;
	background-color: #ffffdd;;
	padding-bottom: 1em;
}

#indicia-forgotten-password-control a:link {
	color: #404080;
}

#indicia-forgotten-password-control a:hover {
	color: #50a050;
}

#indicia-forgotten-password-control legend {
	font-weight: bold;
}

#indicia-forgotten-password-control p {
	font-style: italic;
}

#indicia-forgotten-password-control input[type="submit"] {
	margin-left: 75px;
}

#indicia-forgotten-password-control legend,#indicia-forgotten-password-control p,#indicia-forgotten-password-control label,#indicia-forgotten-password-control a,#indicia-forgotten-password-control input
	{
	margin: 0.5em;
}
</style>
</head>
<body>
	<div id="wrap">
		<h1>Demonstration Site Forgotten Password Control</h1>
		<p>This page invokes the remote user forgotten password recovery
			service offered by the indicia core, using the supplied website user
			name or email address. It triggers an email from indicia core which
			contains a token to allow the password to be reset.</p>
		<p>
			The indicia Forgotten Password Control as shown on this page is
			intended to implement the features described on page 26 of the
			indicia requirements document (listed below). <b>Note,</b> the secure
			password storage on indicia makes password recovery impossible, so
			this process allows the user to reset their password instead.
		</p>
		<p>The control can be styled using CSS to suit your own site.</p>
		<?php
		// code to process the completed form
		if ($_GET || $_POST) {
		  // ask core whether the service credentials are good for this website.
		  $readAuth = user_helper::get_read_auth($website_id, $website_password);
		  // make the call to indicia core to authenticate the user
		  $response = user_helper::request_password_reset($_POST['userid'], $readAuth);
		  // act on the result
		  $result = $response['result'];
		  if ($result) { // requested successfully
		    echo '<p style="color: green;">Your request has been processed.<br />'.
		    'An email will be sent to your registered email address with details of how to reset your password.';
		    echo '</p>';
		  } else { // not successful
		    echo '<p style="color: red;">There was an error processing your request.</p>';
		  }
		}
		?>
		<?php // the forgotten password control
		echo user_helper::forgotten_password_control(array(
            'action_url' => '',
            'show_fieldset' => true,
            'login_uri' => $base_url.'modules/demo/login_control.php',
		));
		?>
		<div id="requirements">
			<h2>Forgotten Password Control Requirements</h2>
			<p>The Forgotten Password Control is a panel which is placed on a
				page referred to by the Request Forgotten Password link in the Login
				control. This provides a text entry box allowing the user to enter
				their email address and a submit button. On clicking submit, the
				email address is checked against the list of known users. If valid,
				an email is sent to the address detailing the password. Otherwise a
				message is displayed stating that the email address could not be
				recognised and the user can retype their email address to try again.</p>
		</div>
	</div>
</body>
</html>
