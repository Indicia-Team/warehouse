<?php
if (!empty($error)) {
  echo html::page_notice('Email configuration test failed', $error, 'warning', 'alert');
}
$current = kohana::config('email');
// if first time email has been configured, set up some Indicia defaults that are not in the Kohana system version.
if (!is_array($current['options'])) {
  $current['options'] = array(
    'hostname' => '',
    'username' => '',
    'password' => '',
    'port' => '25',
    'auth' => ''
  );
}
if (!array_key_exists('address', $current)) {
  $current['address'] = '';
}
if (!array_key_exists('forgotten_passwd_title', $current)) {
  $current['forgotten_passwd_title'] = 'Forgotten password reminder';
}
if (!array_key_exists('server_name', $current)) {
  $current['server_name'] = 'Indicia';
}
?>
<p> The following options configure the Indicia Warehouse to be able to access your email system to send emails.</p>
<form action="config_email_save" method="post">
<div class="form-group">
  <label for="hostname">Outgoing mail server (SMTP):</label>
  <input name="hostname" type="text" class="form-control" value="<?php echo $current['options']['hostname']; ?>"/>
</div>
<div class="form-group">
  <label for="username">Username for email connection:</label>
  <input name="username" type="text" class="form-control" value="<?php echo $current['options']['username'];?>"/>
</div>
<div class="form-group">
  <label for="password">Password for email connection:</label>
  <input name="password" type="password" class="form-control" value="<?php echo $current['options']['password']; ?>" />
</div>
<div class="form-group">
  <label for="port">Port for email connection</label>
  <input name="port" type="text" class="form-control" value="<?php echo $current['options']['port']; ?>"class="narrow" />
</div>
<div class="form-group">
  <label for="address">Send email from the following email address:</label>
  <input name="address" type="text" class="form-control" value="<?php echo $current['address']; ?>"/>
</div>
<div class="form-group">
  <label for="forgotten_passwd_title">Forgotten Password Email Title</label>
  <input name="forgotten_passwd_title" class="form-control" type="text" value="<?php echo $current['forgotten_passwd_title']; ?>" />
</div>
<div class="form-group">
  <label for="server_name">Server name in emails</label>
  <input name="server_name" type="text" class="form-control" value="<?php echo $current['server_name']; ?>" />
</div>
<div class="form-group">
  <label for="test_email">Send test email to the following address</label>
  <input name="test_email" type="text" class="form-control" value=""/>
</div>
<input type="Submit" name="save" value="Test and save" class="btn btn-primary" />
<input type="Submit" name="skip" value="Skip email configuration" class="btn btn-warning" />
</form>
