<?php // DEMO PAGE FOR REMOTE USER LOGIN AGAINST USER DATA ON CORE
require '../../client_helpers/user_helper.php';
require 'data_entry_config.php';
$base_url = user_helper::$base_url;
$website_password=$config['password'];
$website_id=$config['website_id'];
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>Demo login control page</title>
<link rel="stylesheet" href="demo.css" type="text/css" media="screen" />
<style type="text/css">
#requirements {
  margin-left: 20px;
}
/* some example CSS styling for the indicia login control */
#indicia-login-control {
  margin-left: 150px;
  font-family: "Arial", "Helvetica", sans-serif;
}

#indicia-login-control fieldset {
  width: 450px;
  background-color: #ffffdd;;
  padding-bottom: 1em;
}

#indicia-login-control a:link {
  color: #404080;
}

#indicia-login-control a:hover {
  color: #50a050;
}

#indicia-login-control legend {
  font-weight: bold;
}

#indicia-login-control p {
  font-style: italic;
}

#indicia-login-control input[type="submit"] {
  margin-left: 175px;
  width: 100px;
}

#indicia-login-control legend,#indicia-login-control p,#indicia-login-control label,#indicia-login-control a,#indicia-login-control input
  {
  margin: 0.5em;
}
/* CSS styling for the minimal config login control */
#indicia-login-minimal {
  margin-left: 265px;
  font-family: "Arial", "Helvetica", sans-serif;
  color: white;
}

#indicia-login-minimal {
  width: 220px;
  background-color: black;
}

#indicia-login-minimal label,#indicia-login-minimal input {
  margin: 0.2em;
}

#indicia-login-minimal input[type="submit"] {
  margin-left: 10px;
}
</style>
</head>
<body>
  <div id="wrap">
    <h1>Demonstration Site Login Control</h1>
    <p>This page invokes the remote user authentication service offered by
      the indicia core, using the supplied website user credentials. It
      returns the user_id for the authenticated user. A user_id of 0
      indicates the user credentials were invalid for a user on this
      website.</p>
    <p>The indicia Login Control as shown on this page is intended to
      implement the features described on page 25 of the indicia
      requirements document (listed below). It can be styled using CSS to
      suit your own site. The link to 'Register' has not been implemented yet.</p>
      <?php
      if ($_GET || $_POST) {
        // ask core whether the credentials are good for this website.
        $readAuth = user_helper::get_read_auth($website_id, $website_password);
        // set options for case insensitive name comparison and to request the site role
        $options = array('namecase' => false, 'getprofile' => true);
        // find user by email address if using second login form
        if (array_key_exists('login_by_email_submit', $_POST)) $options['nameormail'] = 'mail';
        // make the call to indicia core to authenticate the user
        $response = user_helper::authenticate_user($_POST['username'], $_POST['password'],
            $readAuth, $website_password, $options);
        // act on the result
        $user_id = $response['user_id'];
        if ($user_id > 0) { // authenticated successfully
          echo '<p style="color: green;">You have been successfully authenticated by the indicia core '.
                  'as a user of website '.$website_id.'.<br />You are user number '.$user_id.'<br />';
          if (array_key_exists('remember_me', $_POST) && $_POST['remember_me'] == '1') {
            echo 'I will remember you '.$_POST['username'].'.<br />';
          } else {
            echo 'You will not be remembered. Who are you again?<br />';
          }
          $profile = $response['profile'];
          // or if we hadn't got profile on login, we could make the call on the next line.
          // $profile = user_helper::get_user_profile($user_id, $readAuth);
          echo '<br />User profile: [';
          print_r($profile);
          echo ']';
          echo '</p>';
        } else { // not authenticated
          echo '<p style="color: red;">Computer says <b>NO!</b><br />Try again...</p>';
        }
      }
      ?>
    <?php // the login control using user name
      echo user_helper::login_control(array(
            'action_url' => '',
            'login_text' => 'Please enter your username and password.<br />'
              .'Remember the password is case sensitive.',
            'show_fieldset' => true,
            'legend_label' => 'Login to MyDemoSite',
            'show_rememberme' => true,
            'register_uri' => $base_url.'modules/demo/register_user_control.php',
            'forgotten_uri' => $base_url.'modules/demo/forgotten_password_control.php',
          ));
        ?>
    or perhaps the same control in a minimal configuration and using email
    address for login...
    <?php // the login control using email address
      echo user_helper::login_control(array(
            'action_url' => '',
            'control_id' => 'indicia-login-minimal',
            'name_label' => 'Email address',
            'button_field' => 'login_by_email_submit',
            'button_label' => 'Go-->',
      ));
    ?>
    <div id="requirements">
      <h2>Login Control Requirements</h2>
      <p>The Login Control is a panel which provides a standardisation of
        the following controls:</p>
      <ul>
        <li>A label and text entry box for the username. The site
          administrator is able to configure this to accept the user's email
          address as an alternative.</li>
        <li>A label and text entry box for the password, with obscured
          characters.</li>
        <li>A login button which authenticates the user on the site when
          they log in or displays a message if the login is unsuccessful.</li>
        <li>An optional link to a page allowing the user to register on the
          site. Note that on sites where registration is by invite only, this
          link is removed.</li>
        <li>An optional "Request Forgotten Password" link to a page allowing
          the user to request their forgotten password.</li>
        <li>An optional checkbox that allows the user to select the option
          to remember them the next time they visit the website, so there is
          no need to log in again.</li>
      </ul>
    </div>
  </div>
</body>
</html>
