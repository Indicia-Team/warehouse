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
<meta id="baseURI" name="baseURI" content="<?php echo url::site() ?>" />
<meta id="routedURI" name="routedURI" content="<?php echo url::site().router::$routed_uri; ?>" />

<?php
echo html::stylesheet(
  array(
    'media/css/site',
    'media/css/forms',
    'media/css/thickbox',
    'media/css/jquery.autocomplete',
    'media/themes/default/jquery-ui.custom'
  ),
  array('screen')
); ?>

<!-- BEGIN: jquery/superfish init -->
<?php
    echo html::script(array(
      'media/js/json2.js',
      'media/js/jquery.js',
      'media/js/jquery.url.js',
      'media/js/jquery.url.js',
      'media/js/hasharray.js',
      'media/js/superfish.js',
      'media/js/jquery-ui.custom.min.js'
    ), FALSE);
?>

<?php echo html::stylesheet(array('media/css/menus',),array('screen',)); ?>

<script type="text/javascript">
      jQuery(document).ready(function() {
        jQuery('ul.sf-menu').superfish();
    });
</script>
<!-- END: jquery/superfish init -->

<title><?php echo html::specialchars($title) ?></title>

</head>
<body>

<div id="wrapper">

    <!-- BEGIN: banner -->
    <div id="banner" role="banner">
        <span id="sitetitle">Indicia</span><br/>
        <span id="subtitle">The NBN OPAL Online Recording Toolkit</span>
    </div>
    <!-- END: banner -->

    <!-- BEGIN: main menu (jquery/superfish) -->
    <ul class="sf-menu" role="menubar">

    <?php foreach ($menu as $toplevel => $submenu): ?>

        <!-- BEGIN: print the top level menu items -->
        <?php if(count($submenu)==0): ?>
            <!-- No submenu, so treat as link to the home page -->
            <li role="menuitem"><?php echo html::anchor('home', $toplevel); ?>
        <?php else: ?>
            <li role="menu"><a href="#"><?php echo $toplevel; ?></a>
        <?php endif; ?>

            <!-- BEGIN: print the sub menu items -->
            <?php if (count($submenu)>0): ?>
                <ul>
                <?php foreach ($submenu as $menuitem => $url): ?>
                    <li role="menuitem"><?php echo html::anchor($url, $menuitem); ?></li>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <!-- END: print the sub menu items -->

        </li>
        <!-- END: print the top level menu items -->

    <?php endforeach; ?>

    </ul>
    <!-- END: main menu (jquery/superfish) -->

    <!-- BEGIN: page level content -->
    <div id="content" role="main">

        <h1><?php echo $title; ?></h1>

        <?php echo $content; ?>

    </div>
    <!-- END: page level content -->

    <!-- BEGIN: footer -->
    <div id="footer">
        <p>Version <?php echo $system['version']; ?> - Release date <?php echo $system['release_date']; ?></p>
    </div>
    <!-- END: footer -->

</div>

</body>
</html>
