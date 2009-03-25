<form class="cmxform"  name = "new_password" action="<?php echo url::site(); ?>new_password/save" method="post">
<input type="hidden" name="id" id="id" value="<?php echo html::specialchars($user_model->id); ?>" />
<fieldset>
<legend>Set Password</legend>
<ol>
<?php if ( ! empty($message) )
{
    echo "<li>".$message."</li>";
}
?>
<li>
  <label for="username">Username</label>
  <input tabindex="1" type = "text" name = "username" id = "username" value="<?php echo $user_model->username; ?>" disabled="disabled"  class="narrow" >
  <?php echo html::error_message($user_model->getError('username')); ?>
</li>
<li>
  <label for="email_address">Email</label>
  <input tabindex="2" type = "text" name = "email_address" id = "email_address" value="<?php echo $person_model->email_address; ?>" class="narrow" >
  <?php echo html::error_message($person_model->getError('email_address')); ?>
</li>
<li>
  <label for="password">Password</label>
  <input tabindex="3" type = "password" name = "password" id = "password" value="<?php echo $password; ?>" class="narrow" >
  <?php echo html::error_message($user_model->getError('password')); ?>
</li>
<li>
  <label for="password2">Repeat Password</label>
  <input tabindex="4" type = "password" name = "password2" id = "password2" value="<?php echo $password2; ?>" class="narrow" >
</li>
<li>
  <label for="remember_me" >Remember me</label>
  <input tabindex="5" type="checkbox" id="remember_me" name="remember_me" class="default" />
</li>
</ol>
</fieldset>
  <input tabindex="6" type = "submit" value = "Submit New Password" />
</form>
