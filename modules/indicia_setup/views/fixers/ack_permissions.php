<p>If this installation of the Indicia Warehouse is for testing or development only, you can acknowledge the
directory permissions problems identified to allow the installation to proceed. Please do not acknowledge these
problems if the server is for a production environment.</p>
<p>The permission problems are:</p>
<div class="page-notice ui-widget ui-state-error ui-corner-all">
<?php echo $problems; ?>
</div>
<a href="<?php echo url::site().'setup_check/do_ack_permissions'; ?>" class="button ui-state-default ui-corner-all">Acknowledge</a>
<a href="<?php echo url::site().'setup_check'; ?>" class="button ui-state-default ui-corner-all">Cancel</a>
