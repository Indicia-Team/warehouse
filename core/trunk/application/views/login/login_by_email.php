Indicia is a toolkit that simplifies the construction of new websites which allow data entry, mapping and reporting of wildlife records. Indicia is an Open Source project funded by the Open Air Laboratories Network and managed by the Centre for Ecology and Hydrology.<br /><br />
In order to gain access to this Indicia system you must log on. If you do not have an account and need one, please contact the administrator <?php echo $admin_contact; ?> who can create one for you.<br /><br />

<form class="cmxform" name = "login" action="<?php echo url::site(); ?>login/login_by_email" method="post">
<fieldset>
<legend>Login details</legend>
<?php if ( ! empty($error_message) )
{
    echo html::error_message($error_message);
}
?>
<ol>
<li>
  <label for="Email">Email</label>
  <input tabindex="1" type = "text" name = "Email" id = "Email" value="" class="narrow" />
</li>
<li>
  <label for="Password">Password</label>
  <input tabindex="2" type = "password" name = "Password" id = "Password" value="" class="narrow" />
</li>
<li>
  <label for="remember_me" >Remember me</label>
  <input tabindex="3" type="checkbox" id="remember_me" name="remember_me" class="default" />
</li>
</ol>
</fieldset>
<input tabindex="4" type = "submit" value = "Login" />
</form>
<?php if ( ! empty($link_to_username) )
{ ?>
  <br />You may <a href="<?php echo url::site(); ?>login">click here to log in using your Username</a>.
<?php } ?>
<br />If you have forgotten your password, <a href="<?php echo url::site(); ?>forgotten_password">click here to request an email allowing you to reset your password</a>.