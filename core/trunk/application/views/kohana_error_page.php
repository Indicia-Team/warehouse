<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>

<!-- Main template -->

<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

<?php echo html::stylesheet(array('media/css/site',),array('screen',)); ?>
<style type="text/css">
<?php include Kohana::find_file('views', 'kohana_errors', FALSE, 'css') ?>
</style>

<title><?php echo html::specialchars($error) ?></title>

</head>
<body>

<div id="wrapper">

    <!-- BEGIN: banner -->
    <div id="banner" role="banner">
        <span>Indicia</span>
    </div>
    <!-- END: banner -->


    <!-- BEGIN: page level content -->
    <div id="content" role="main">

        <h1><?php echo html::specialchars($error) ?></h1>

        <div id="framework_error" style="width:42em;margin:20px auto;">
            <pre><?php echo html::specialchars($description) ?></pre>

            <?php if ( ! empty($line) AND ! empty($file)): ?>
            <p><?php echo Kohana::lang('core.error_file_line', $file, $line) ?></p>
            <?php endif ?>

            <pre><?php echo $message ?></pre>

            <?php if ( ! empty($trace)): ?>
            <h3><?php echo Kohana::lang('core.stack_trace') ?></h3>
            <?php echo $trace ?>
            <?php endif ?>
        </div>
    </div>
    <!-- END: page level content -->

</div>

</body>
</html>
