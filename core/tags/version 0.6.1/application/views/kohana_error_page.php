<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<?php

/**
 * Indicia, the OPAL Online Recording Toolkit.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses/gpl.html.
 *
 * @package	Core
 * @subpackage Views
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

?>
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
            <?php endif; ?>

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
