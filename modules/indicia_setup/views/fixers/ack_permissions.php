<p>If this installation of the Indicia Warehouse is for testing or development only, you can acknowledge the
directory permissions problems identified to allow the installation to proceed. Please do not acknowledge these
problems if the server is for a production environment.</p>
<div class="panel panel-default">
  <div class="panel-heading">
    Permission problems
  </div>
  <div class="panel-body">
    <?php echo $problems; ?>
  </div>
</div>
<a href="<?php echo url::site().'setup_check/do_ack_permissions'; ?>" class="btn btn-warning">Acknowledge</a>
<a href="<?php echo url::site().'setup_check'; ?>" class="btn btn-default">Cancel</a>
