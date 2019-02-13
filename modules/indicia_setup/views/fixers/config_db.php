<?php
if ($error!=null) {
  echo html::page_notice('Database configuration problem', $error, 'danger');
}
?>
<p><?php echo $description; ?></p>
<form name="setup" action="config_db_save" method="post" style="max-width: 400px">
<fieldset>
  <legend><?php echo Kohana::lang('setup.database'); ?></legend>
    <!-- DB host -->
    <div class="form-group ctrl-wrap">
      <label for="dbhost"><?php echo Kohana::lang('setup.db_host'); ?>:</label>
      <input type="text"
            title="<?php echo html::specialchars(Kohana::lang('setup.db_host')); ?>"
            id="host"
            name="host"
            maxlength="255"
            class="form-control"
            value="<?php echo html::specialchars($host); ?>" />
    </div>
    <!-- DB port -->
    <div class="form-group ctrl-wrap">
      <label for="dbport"><?php echo Kohana::lang('setup.db_port'); ?>:</label>
      <input type="text"
          title="<?php echo html::specialchars(Kohana::lang('setup.db_port')); ?>"
          id="port"
          name="port"
          maxlength="6"
          class="form-control"
          value="<?php echo html::specialchars($port); ?>" />
    </div>
    <!-- DB name -->
    <div class="form-group ctrl-wrap">
      <label for="dbname"><?php echo Kohana::lang('setup.db_name'); ?>:</label>
      <input type="text"
          title="<?php echo html::specialchars(Kohana::lang('setup.db_name')); ?>"
          id="database"
          name="database"
          maxlength="255"
          class="form-control"
          value="<?php echo html::specialchars($database); ?>" />
    </div>
    <!-- DB schema -->
    <div class="form-group ctrl-wrap">
      <label for="dbschema"><?php echo Kohana::lang('setup.db_schema'); ?>:</label>
      <input type="text"
          title="<?php echo html::specialchars(Kohana::lang('setup.db_schema')); ?>"
          id="schema"
          name="schema"
          maxlength="255"
          class="form-control"
          value="<?php echo html::specialchars($schema); ?>" />
    </div>
    <!-- DB user -->
    <div class="form-group ctrl-wrap">
      <label for="dbuser"><?php echo Kohana::lang('setup.db_user'); ?>:</label>
      <input type="text"
          title="<?php echo html::specialchars(Kohana::lang('setup.db_user')); ?>"
          id="dbuser"
          name="dbuser"
          maxlength="255"
          class="form-control"
          value="<?php echo html::specialchars($user); ?>" />
    </div>
    <!-- DB password -->
    <div class="form-group ctrl-wrap">
      <label for="dbpassword"><?php echo Kohana::lang('setup.db_password'); ?>:</label>
      <input type="text"
          title="<?php echo html::specialchars(Kohana::lang('setup.db_password')); ?>"
          id="dbpassword"
          name="dbpassword"
          maxlength="255"
          class="form-control"
          value="<?php echo html::specialchars($password); ?>" />
    </div>
    <!-- DB user for reports -->
    <div class="form-group ctrl-wrap">
      <label for="reportuser"><?php echo Kohana::lang('setup.db_report_user'); ?>:</label>
      <input type="text"
          title="<?php echo html::specialchars(Kohana::lang('setup.db_report_user')); ?>"
          id="reportuser"
          name="reportuser"
          maxlength="255"
          class="form-control"
          value="<?php echo html::specialchars($reportuser); ?>" />
    </div>
    <!-- DB password for reports -->
    <div class="form-group ctrl-wrap">
      <label for="reportpassword"><?php echo Kohana::lang('setup.db_report_password'); ?>:</label>
      <input type="text"
          title="<?php echo html::specialchars(Kohana::lang('setup.db_report_password')); ?>"
          id="reportpassword"
          name="reportpassword"
          maxlength="255"
          class="form-control"
          value="<?php echo html::specialchars($reportpassword); ?>" />
    </div>
  </ol>
</fieldset>

<input name="start_setup_button"
    id="start_setup_button"
    type="submit"
    tabindex="8"
    role="button"
    value="<?php echo html::specialchars(Kohana::lang('setup.submit')); ?>"
    class="btn btn-primary" />

</form>