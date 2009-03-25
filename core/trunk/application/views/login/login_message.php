<?php echo $message; ?>
<?php if ( ! empty($link_to_logout) )
{ ?>
  <br />You may <a href="<?php echo url::site(); ?>logout">click here to logout</a>.
<?php } ?>
<?php if ( ! empty($link_to_home) )
{ ?>
  <br />You may <a href="<?php echo url::site(); ?>">click here to return to the home page</a>.
<?php } ?>
