<?php
if ($error!=null) {
  echo html::page_error('Database configuration problem', $error);
}
?>
<p><?php echo $description; ?></p>
<form class="cmxform widelabels" name="setup" action="config_db_save" method="post">
<fieldset>
  <legend><?php echo Kohana::lang('setup.database'); ?></legend>
  <ol>
      <!-- DB host -->
      <li><label for="dbhost"><?php echo Kohana::lang('setup.db_host'); ?>:</label>
      <input type="text"
          title="<?php echo html::specialchars(Kohana::lang('setup.db_host')); ?>"
          id="host"
          name="host"
          maxlength="255"
          value="<?php echo html::specialchars($host); ?>"
      </li>

      <!-- DB port -->
      <li><label for="dbport"><?php echo Kohana::lang('setup.db_port'); ?>:</label>
      <input type="text"
          title="<?php echo html::specialchars(Kohana::lang('setup.db_port')); ?>"
          id="port"
          name="port"
          maxlength="6"
          value="<?php echo html::specialchars($port); ?>"
      </li>

      <!-- DB name -->
      <li><label for="dbname"><?php echo Kohana::lang('setup.db_name'); ?>:</label>
      <input type="text"
          title="<?php echo html::specialchars(Kohana::lang('setup.db_name')); ?>"
          id="database"
          name="database"
          maxlength="255"
          value="<?php echo html::specialchars($database); ?>"
      </li>

      <!-- DB schema -->
      <li><label for="dbschema"><?php echo Kohana::lang('setup.db_schema'); ?>:</label>
      <input type="text"
          title="<?php echo html::specialchars(Kohana::lang('setup.db_schema')); ?>"
          id="schema"
          name="schema"
          maxlength="255"
          value="<?php echo html::specialchars($schema); ?>"
      </li>

      <!-- DB user -->
      <li><label for="dbuser"><?php echo Kohana::lang('setup.db_user'); ?>:</label>
      <input type="text"
          title="<?php echo html::specialchars(Kohana::lang('setup.db_user')); ?>"
          id="user"
          name="user"
          maxlength="255"
          value="<?php echo html::specialchars($user); ?>"
      </li>

      <!-- DB password -->
      <li class="item_title"><label for="dbpassword"><?php echo Kohana::lang('setup.db_password'); ?>:</label>
      <input type="text"
          title="<?php echo html::specialchars(Kohana::lang('setup.db_password')); ?>"
          id="password"
          name="password"
          maxlength="255"
          value="<?php echo html::specialchars($password); ?>"
      </li>
  </ol>
</fieldset>

<input name="start_setup_button"
    id="start_setup_button"
    type="submit"
    tabindex="8"
    role="button"
    value="<?php echo html::specialchars(Kohana::lang('setup.submit')); ?>"
    class="narrow" />

</form>