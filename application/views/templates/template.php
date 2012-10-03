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
// during setup, the indicia config file does not exist
$indicia = kohana::config_load('indicia', false);
$theme=$indicia ? $indicia['theme'] : 'default';
echo html::stylesheet(
  array(
    'media/css/site',
    'media/css/forms',
    'media/js/fancybox/jquery.fancybox.css',
    'media/css/jquery.autocomplete',
    'media/themes/'.$theme.'/jquery-ui.custom'
  ),
  array('screen')
); ?>

<!-- BEGIN: jquery/superfish init -->
<?php
    echo html::script(array(
      'media/js/json2.js',
      'media/js/jquery.js',
      'media/js/jquery.url.js',
      'media/js/fancybox/jquery.fancybox.pack.js',
      'media/js/hasharray.js',
      'media/js/superfish.js',
      'media/js/jquery-ui.custom.min.js'
    ), FALSE);
?>


<?php echo html::stylesheet(array('media/css/menus',),array('screen',)); ?>

<script type="text/javascript">
/*<![CDATA[*/
  jQuery(document).ready(function() {
    jQuery('ul.sf-menu').superfish();
    // Implement hover over highlighting on buttons, even for AJAX loaded content by using live events
    $('.ui-state-default').live('mouseover', function() { 
      $(this).addClass('ui-state-hover'); 
    });
    $('.ui-state-default').live('mouseout', function() { 
      $(this).removeClass('ui-state-hover'); 
    });
    // Hack to get fancybox working as a jQuery live, because some of our images load from AJAX calls,
    // e.g. on the species checklist taxa tab. So we temporarily create a dummy link to our image and click it.
    $('a.fancybox').live('click', function() {
      jQuery("body").after('<a id="link_fancybox" style="display: hidden;" href="'+jQuery(this).attr('href')+'"></a>');
      jQuery('#link_fancybox').fancybox(); 
      jQuery('#link_fancybox').click();
      jQuery('#link_fancybox').remove();
      return false;
    });
  });
/*]]>*/
</script>
<!-- END: jquery/superfish init -->

<title><?php echo html::specialchars($warehouseTitle . ' | ' . $title) ?></title>

</head>
<body>

<div id="wrapper" class="ui-widget">

    <!-- BEGIN: banner -->
    <div id="banner"></div>
    <!-- END: banner -->

    <!-- BEGIN: main menu (jquery/superfish) -->
    <?php if (isset($menu)) : ?>
    <div id="menu">
    <ul class="sf-menu ui-helper-reset ui-helper-clearfix ui-widget-header">

    <?php foreach ($menu as $toplevel => $submenu): ?>

        <!-- BEGIN: print the top level menu items -->
        <li class="ui-state-default">
        <?php if(count($submenu)==0) {
            // No submenu, so treat as link to the home page
          echo html::anchor('home', $toplevel);
        } else {
            echo '<a href="#">'.$toplevel.'</a>';
        } ?>

            <!-- BEGIN: print the sub menu items -->
            <?php if (count($submenu)>0): ?>
                <ul>
                <?php foreach ($submenu as $menuitem => $url): ?>
                    <li class="ui-state-default"><?php echo html::anchor($url, $menuitem); ?></li>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <!-- END: print the sub menu items -->

        </li>
        <!-- END: print the top level menu items -->

    <?php endforeach; ?>

    </ul>
    </div>
    <?php endif; ?>
    <!-- END: main menu (jquery/superfish) -->

    <!-- BEGIN: page level content -->
    <div id="content">
    <div id="breadcrumbs">
<?php echo $this->get_breadcrumbs(); ?>
</div>
        <h1><?php echo $title; ?></h1>
<?php
  $info = $this->session->get('flash_info', null);
  if ($info) : ?>
        <div class="ui-widget-content ui-corner-all ui-state-highlight page-notice" >
        <?php echo $info; ?>
        </div>
<?php endif;
  $error = $this->session->get('flash_error', null);
  if ($error) : ?>
        <div class="ui-widget-content ui-corner-all ui-state-error page-notice">
        <?php echo $error; ?>
        </div>
<?php endif; ?>
        <?php echo $content; ?>

    </div>
    <!-- END: page level content -->

    <!-- BEGIN: footer -->
    <div id="footer">
    <?php
    echo Kohana::lang('misc.indicia_version').' '; 
    echo kohana::config('version.version'); 
    if (kohana::config('upgrade.continuous_upgrade')) echo " (dev)"; ?>
    </div>
    <!-- END: footer -->

</div>

</body>
</html>
