<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>

<!-- Setup template -->

<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

<?php echo html::stylesheet(array('modules/indicia_setup/media/setup',),array('screen',)); ?>

<title><?php echo Kohana::lang('setup.title'); ?><?php echo $page_title_error; ?></title>

</head>
<body>

<div id="wrapper">

    <!-- BEGIN: page level content -->
    <div id="content" role="main">

        <h1><?php echo Kohana::lang('setup.title'); ?></h1>

        <p><?php echo Kohana::lang('setup.description'); ?></p>

        <hr />

        <?php if(count($error_general) > 0): ?>
            <fieldset id="global_error" role="alert">
                <h2><legend><?php echo Kohana::lang('setup.warning'); ?></legend></h2>
                <ul>
                <?php foreach($error_general as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
                </ul>
            </fieldset>
        <?php endif; ?>

        <form name="setup" action="<?php echo $url; ?>" method="post">

            <fieldset>

                <h2><legend><?php echo Kohana::lang('setup.database'); ?></legend></h2>

                <ul>
                    <!-- DB host -->
                    <li class="item_title"><h3><label for="dbhost"><?php echo Kohana::lang('setup.db_host'); ?></label></h3></li>

                    <li class="item_content"><input type="text"
                                                    title="<?php echo html::specialchars(Kohana::lang('setup.db_host')); ?>"
                                                    id="dbhost"
                                                    name="dbhost"
                                                    tabindex="1"
                                                    maxlength="255"
                                                    <?php if($error_dbhost): ?>class="text_field text_field_error"<?php else: ?>class="text_field"<?php endif; ?>
                                                    value="<?php echo html::specialchars($dbhost); ?>"
                                                    aria-required="true"
                                                    aria-labelledby="dbhost"
                                                    <?php if($error_dbhost): ?>aria-invalid="true"<?php endif; ?>
                                             /></li>

                    <!-- DB port -->
                    <li class="item_title"><h3><label for="dbport"><?php echo Kohana::lang('setup.db_port'); ?></label></h3></li>

                    <li class="item_content"><input type="text"
                                                    title="<?php echo html::specialchars(Kohana::lang('setup.db_port')); ?>"
                                                    id="dbport"
                                                    name="dbport"
                                                    tabindex="2"
                                                    maxlength="6"
                                                    <?php if($error_dbport): ?>class="text_field text_field_error"<?php else: ?>class="text_field"<?php endif; ?>
                                                    value="<?php echo html::specialchars($dbport); ?>"
                                                    aria-required="true"
                                                    aria-labelledby="dbport"
                                                    <?php if($error_dbhost): ?>aria-invalid="true"<?php endif; ?>
                                             /></li>

                    <!-- DB name -->
                    <li class="item_title"><h3><label for="dbname"><?php echo Kohana::lang('setup.db_name'); ?></label></h3></li>

                    <li class="item_content"><input type="text"
                                                    title="<?php echo html::specialchars(Kohana::lang('setup.db_name')); ?>"
                                                    id="dbname"
                                                    name="dbname"
                                                    tabindex="3"
                                                    maxlength="255"
                                                    <?php if($error_dbname): ?>class="text_field text_field_error"<?php else: ?>class="text_field"<?php endif; ?>
                                                    value="<?php echo html::specialchars($dbname); ?>"
                                                    aria-required="true"
                                                    aria-labelledby="dbname"
                                                    <?php if($error_dbname): ?>aria-invalid="true"<?php endif; ?>
                                             /></li>

                    <!-- DB schema -->
                    <li class="item_title"><h3><label for="dbschema"><?php echo Kohana::lang('setup.db_schema'); ?></label></h3></li>

                    <li class="item_content"><input type="text"
                                                    title="<?php echo html::specialchars(Kohana::lang('setup.db_schema')); ?>"
                                                    id="dbschema"
                                                    name="dbschema"
                                                    tabindex="4"
                                                    maxlength="255"
                                                    <?php if($error_dbschema): ?>class="text_field text_field_error"<?php else: ?>class="text_field"<?php endif; ?>
                                                    value="<?php echo html::specialchars($dbschema); ?>"
                                                    aria-required="true"
                                                    aria-labelledby="dbschema"
                                                    <?php if($error_dbschema): ?>aria-invalid="true"<?php endif; ?>
                                             /></li>

                    <!-- DB user -->
                    <li class="item_title"><h3><label for="dbuser"><?php echo Kohana::lang('setup.db_user'); ?></label></h3></li>

                    <li class="item_content"><input type="text"
                                                    title="<?php echo html::specialchars(Kohana::lang('setup.db_user')); ?>"
                                                    id="dbuser"
                                                    name="dbuser"
                                                    tabindex="5"
                                                    maxlength="255"
                                                    <?php if($error_dbuser): ?>class="text_field text_field_error"<?php else: ?>class="text_field"<?php endif; ?>
                                                    value="<?php echo html::specialchars($dbuser); ?>"
                                                    aria-required="true"
                                                    aria-labelledby="dbuser"
                                                    <?php if($error_dbuser): ?>aria-invalid="true"<?php endif; ?>
                                             /></li>

                    <!-- DB password -->
                    <li class="item_title"><h3><label for="dbpassword"><?php echo Kohana::lang('setup.db_password'); ?></label></h3></li>

                    <li class="item_content"><input type="text"
                                                    title="<?php echo html::specialchars(Kohana::lang('setup.db_password')); ?>"
                                                    id="dbpassword"
                                                    name="dbpassword"
                                                    tabindex="6"
                                                    maxlength="255"
                                                    <?php if($error_dbpassword): ?>class="text_field text_field_error"<?php else: ?>class="text_field"<?php endif; ?>
                                                    value="<?php echo html::specialchars($dbpassword); ?>"
                                                    aria-required="true"
                                                    aria-labelledby="dbpassword"
                                                    <?php if($error_dbpassword): ?>aria-invalid="true"<?php endif; ?>
                                             /></li>

                    <!-- DB grants -->

                    <li class="item_title"><h3><label for="dbgrant"><?php echo Kohana::lang('setup.db_grant'); ?></label></h3></li>

                    <li class="item_content"><input type="text"
                                                    title="<?php echo html::specialchars(Kohana::lang('setup.db_grant')); ?>"
                                                    id="dbgrant"
                                                    name="dbgrant"
                                                    tabindex="7"
                                                    maxlength="1500"
                                                    <?php if($error_dbgrant): ?>class="text_field text_field_error"<?php else: ?>class="text_field"<?php endif; ?>
                                                    value="<?php echo html::specialchars($dbgrant); ?>"
                                                    aria-required="true"
                                                    aria-labelledby="dbgrant"
                                                    <?php if($error_dbgrant): ?>aria-invalid="true"<?php endif; ?>
                                             /></li>
                </ul>

            </fieldset>

            <!-- start Setup -->

            <fieldset>

                <h2><legend><?php echo Kohana::lang('setup.start_setup_title'); ?></legend></h2>

                <ul>
                    <li class="item_content"><input name="start_setup_button"
                                                    id="start_setup_button"
                                                    type="submit"
                                                    tabindex="8"
                                                    role="button"
                                                    value="<?php echo html::specialchars(Kohana::lang('setup.start_setup_button')); ?>" /></li>
                </ul>

            </fieldset>

        </form>

    </div>
    <!-- END: page level content -->

</div>

</body>
</html>
