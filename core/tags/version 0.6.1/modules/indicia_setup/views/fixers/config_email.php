<?php
if ($error!=null) {
  echo html::page_error('Email configuration test failed', $error);
}
$current=kohana::config('email');
// if first time email has been configured, set up some Indicia defaults that are not in the Kohana system version.
if (!is_array($current['options'])) {
  $current['options']=array(
    'hostname' => '',
    'username' => '',
    'password' => '',
    'port' => '25',
    'auth' => ''
  );
}
if (!array_key_exists('address', $current)) {
  $current['address']='';
}
if (!array_key_exists('forgotten_passwd_title', $current)) {
  $current['forgotten_passwd_title']='Forgotten password reminder';
}
if (!array_key_exists('server_name', $current)) {
  $current['server_name']='Indicia';
}
?>
<p> The following options configure the Indicia Warehouse to be able to access your email system to send emails.</p>
<form class="cmxform" action="config_email_save" method="post">
<fieldset>
<legend>Email configuration</legend>
<ol>
<li>
  <label for="hostname">Outgoing mail server (SMTP):</label>
  <input name="hostname" type="text" value="<?php echo $current['options']['hostname']; ?>"/>
</li>
<li>
  <label for="username">Username for email connection:</label>
  <input name="username" type="text" value="<?php echo $current['options']['username'];?>"/>
</li>
<li>
  <label for="password">Password for email connection:</label>
  <input name="password" type="password" value="<?php echo $current['options']['password']; ?>" />
</li>
<li>
  <label for="port">Port for email connection</label>
  <input name="port" type="text"  value="<?php echo $current['options']['port']; ?>"class="narrow" />
</li>
<li>
  <label for="address">Send email from the following email address:</label>
  <input name="address" type="text" value="<?php echo $current['address']; ?>"/>
</li>
<li>
  <label for="forgotten_passwd_title">Forgotten Password Email Title</label>
  <input name="forgotten_passwd_title" type="text" value="<?php echo $current['forgotten_passwd_title']; ?>" />
</li>
<li>
  <label for="server_name">Server name in emails</label>
  <input name="server_name" type="text" value="<?php echo $current['server_name']; ?>" />
</li>
<li>
  <label for="test_email">Send test email to the following address</label>
  <input name="test_email" type="text" value=""/>
</li>
</ol>
</fieldset>
<input type="Submit" name="save" value="Test and save" class="button ui-state-default ui-corner-all" />
<input type="Submit" name="skip" value="Skip email configuration" class="button ui-state-default ui-corner-all" />
</form>