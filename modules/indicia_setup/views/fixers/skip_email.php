<p>If this installation of the Indicia Warehouse is for testing or development only, you can skip the
email configuration. This means that the server will not be able to send emails reminding users of
forgotten passwords. Please do not skip the email configuration if the server is for a production environment.</p>
<br/>
<a href="<?php echo url::site().'setup_check/do_skip_email'; ?>" class="btn btn-warning">Skip Email Configuration</a>
<a href="<?php echo url::site().'setup_check'; ?>" class="btn btn-primary">Cancel</a>