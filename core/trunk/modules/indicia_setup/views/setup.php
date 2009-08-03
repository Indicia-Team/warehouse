<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>

<!-- Setup template -->

<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

<?php
echo html::stylesheet(
  array(
    'media/css/site',
    'media/css/forms',
    'media/themes/default/jquery-ui.custom'
  ),
  array('screen')
); ?>

<title><?php echo Kohana::lang('setup.title'); ?><?php echo $page_title_error; ?></title>

</head>
<body>

<div id="wrapper">
    <div id="banner" role="banner">
        <span id="sitetitle">Indicia</span><br/>
        <span id="subtitle">The NBN OPAL Online Recording Toolkit</span>
    </div>

    <!-- BEGIN: page level content -->
    <div class="ui-widget ui-widget-content ui-corner-all" role="main" style="padding: 0.3em; float: left; margin-top: 1em;">

        <h1 class="ui-widget-header ui-corner-all"><?php echo Kohana::lang('setup.title'); ?></h1>

        <p><?php echo $description; ?></p>

        <?php if(count($error_general) > 0): ?>
            <div id="global_error" role="alert" class="ui-state-error ui-corner-all" style="padding: 0.3em;">
                <strong><?php echo Kohana::lang('setup.warning'); ?></strong>
                <?php foreach($error_general as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form class="cmxform widelabels" name="setup" action="<?php echo $url; ?>" method="post">

            <fieldset>

                <h2><legend><?php echo Kohana::lang('setup.database'); ?></legend></h2>

                <ul>
                    <!-- DB host -->
                    <li><label for="dbhost"><?php echo Kohana::lang('setup.db_host'); ?>:</label>
                    <input type="text"
                        title="<?php echo html::specialchars(Kohana::lang('setup.db_host')); ?>"
                        id="dbhost"
                        name="dbhost"
                        tabindex="1"
                        maxlength="255"
                        <?php if($error_dbhost): ?>class="text_field text_field_error"<?php else: ?>class="text_field"<?php endif; ?>
                        value="<?php echo html::specialchars($dbhost); ?>"
                        aria-required="true"
                        aria-labelledby="dbhost"
                        <?php if($error_dbhost): ?>aria-invalid="true"<?php endif; ?> />
                    </li>

                    <!-- DB port -->
                    <li><label for="dbport"><?php echo Kohana::lang('setup.db_port'); ?>:</label>
                    <input type="text"
                        title="<?php echo html::specialchars(Kohana::lang('setup.db_port')); ?>"
                        id="dbport"
                        name="dbport"
                        tabindex="2"
                        maxlength="6"
                        <?php if($error_dbport): ?>class="text_field text_field_error"<?php else: ?>class="text_field"<?php endif; ?>
                        value="<?php echo html::specialchars($dbport); ?>"
                        aria-required="true"
                        aria-labelledby="dbport"
                        <?php if($error_dbhost): ?>aria-invalid="true"<?php endif; ?> />
                    </li>

                    <!-- DB name -->
                    <li><label for="dbname"><?php echo Kohana::lang('setup.db_name'); ?>:</label>
                    <input type="text"
                        title="<?php echo html::specialchars(Kohana::lang('setup.db_name')); ?>"
                        id="dbname"
                        name="dbname"
                        tabindex="3"
                        maxlength="255"
                        <?php if($error_dbname): ?>class="text_field text_field_error"<?php else: ?>class="text_field"<?php endif; ?>
                        value="<?php echo html::specialchars($dbname); ?>"
                        aria-required="true"
                        aria-labelledby="dbname"
                        <?php if($error_dbname): ?>aria-invalid="true"<?php endif; ?> />
                    </li>

                    <!-- DB schema -->
                    <li><label for="dbschema"><?php echo Kohana::lang('setup.db_schema'); ?>:</label>
                    <input type="text"
                        title="<?php echo html::specialchars(Kohana::lang('setup.db_schema')); ?>"
                        id="dbschema"
                        name="dbschema"
                        tabindex="4"
                        maxlength="255"
                        <?php if($error_dbschema): ?>class="text_field text_field_error"<?php else: ?>class="text_field"<?php endif; ?>
                        value="<?php echo html::specialchars($dbschema); ?>"
                        aria-required="true"
                        aria-labelledby="dbschema"
                        <?php if($error_dbschema): ?>aria-invalid="true"<?php endif; ?> />
                    </li>

                    <!-- DB user -->
                    <li><label for="dbuser"><?php echo Kohana::lang('setup.db_user'); ?>:</label>
                    <input type="text"
                        title="<?php echo html::specialchars(Kohana::lang('setup.db_user')); ?>"
                        id="dbuser"
                        name="dbuser"
                        tabindex="5"
                        maxlength="255"
                        <?php if($error_dbuser): ?>class="text_field text_field_error"<?php else: ?>class="text_field"<?php endif; ?>
                        value="<?php echo html::specialchars($dbuser); ?>"
                        aria-required="true"
                        aria-labelledby="dbuser"
                        <?php if($error_dbuser): ?>aria-invalid="true"<?php endif; ?> />
                    </li>

                    <!-- DB password -->
                    <li class="item_title"><label for="dbpassword"><?php echo Kohana::lang('setup.db_password'); ?>:</label>
                    <input type="text"
                        title="<?php echo html::specialchars(Kohana::lang('setup.db_password')); ?>"
                        id="dbpassword"
                        name="dbpassword"
                        tabindex="6"
                        maxlength="255"
                        <?php if($error_dbpassword): ?>class="text_field text_field_error"<?php else: ?>class="text_field"<?php endif; ?>
                        value="<?php echo html::specialchars($dbpassword); ?>"
                        aria-required="true"
                        aria-labelledby="dbpassword"
                        <?php if($error_dbpassword): ?>aria-invalid="true"<?php endif; ?> />
                    </li>

                    <!-- DB grants -->

                    <li class="item_title"><label for="dbgrant"><?php echo Kohana::lang('setup.db_grant'); ?>:</label>
                    <input type="text"
                        title="<?php echo html::specialchars(Kohana::lang('setup.db_grant')); ?>"
                        id="dbgrant"
                        name="dbgrant"
                        tabindex="7"
                        maxlength="1500"
                        <?php if($error_dbgrant): ?>class="text_field text_field_error"<?php else: ?>class="text_field"<?php endif; ?>
                        value="<?php echo html::specialchars($dbgrant); ?>"
                        aria-required="true"
                        aria-labelledby="dbgrant"
                        <?php if($error_dbgrant): ?>aria-invalid="true"<?php endif; ?> />
                    </li>
                </ul>

            </fieldset>

            <!-- start Setup -->

            <input name="start_setup_button"
                id="start_setup_button"
                type="submit"
                tabindex="8"
                role="button"
                value="<?php echo html::specialchars(Kohana::lang('setup.start_setup_button')); ?>"
                class="narrow" />

        </form>

    </div>
    <!-- END: page level content -->

</div>

</body>
</html>
