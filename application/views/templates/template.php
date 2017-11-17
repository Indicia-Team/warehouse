<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<?php

/**
 * @file
 * Main html template.
 *
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
 * @package Core
 * @subpackage Views
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link http://code.google.com/p/indicia/
 */

// During setup, the indicia config file does not exist.
$indicia = kohana::config_load('indicia', FALSE);
$theme = $indicia ? $indicia['theme'] : 'default';
$warehouseTitle = isset($warehouseTitle) ? $warehouseTitle : 'Indicia warehouse';
$siteTitle = html::specialchars($warehouseTitle);

?>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>

<!-- Main template -->

<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<meta id="baseURI" name="baseURI" content="<?php echo url::site() ?>" />
<meta id="routedURI" name="routedURI" content="<?php echo url::site() . router::$routed_uri; ?>" />
<title><?php echo $siteTitle; ?> | <?php echo $title ?></title>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css"
  integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u"
  crossorigin="anonymous">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css"
  integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp"
  crossorigin="anonymous">
<?php
echo html::stylesheet(
  array(
    'media/css/warehouse',
    //'media/css/forms',
    'media/js/fancybox/source/jquery.fancybox.css',
    'media/css/jquery.autocomplete',
    'media/themes/' . $theme . '/jquery-ui.custom'
  ),
  array('screen')
);
echo html::stylesheet(array('media/css/menus'), array('screen'));
?>
<?php
echo html::script(
  array(
    'media/js/json2.js',
    'media/js/jquery.js',
    'media/js/jquery.url.js',
    'media/js/fancybox/source/jquery.fancybox.pack.js',
    'media/js/hasharray.js',
    'media/js/jquery-ui.custom.min.js'
  ), FALSE
);
?>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"
      integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa"
      crossorigin="anonymous"></script>
<!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
<script src="../../assets/js/ie10-viewport-bug-workaround.js"></script>
<script type="text/javascript">
/*<![CDATA[*/
  jQuery(document).ready(function() {
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
</head>
<body>
  <div id="banner"><img id="logo" src="<?php echo url::base();?>media/images/indicia_logo.png" width="248" height="100" alt="Indicia"/></div>
    <?php if (isset($menu)) : ?>
    <nav class="navbar navbar-inverse">
      <div class="container-fluid">
        <ul class="nav navbar-nav">
        <?php foreach ($menu as $toplevel => $submenu) : ?>
          <?php if (count($submenu) > 0) : ?>
          <li class="dropdown">
            <a class="dropdown-toggle" data-toggle="dropdown"><?php echo $toplevel; ?>
            <span class="caret"></span></a>
            <ul class="dropdown-menu">
            <?php foreach ($submenu as $menuitem => $url) : ?>
                <li><?php echo html::anchor($url, $menuitem); ?></li>
            <?php endforeach; ?>
            </ul>
          </li>
          <?php else : ?>
          <li>
            <a><?php echo $toplevel; ?></a>
          </li>
          <?php endif; ?>
        <?php endforeach; ?>
        </ul>
      </div>
    </nav>
  <?php endif; ?>

  <div class="container">
    <div id="breadcrumbs">
      <?php echo $this->get_breadcrumbs(); ?>
    </div>
    <h1><?php echo $title; ?></h1>
    <?php
    $info = $this->session->get('flash_info', NULL);
    if ($info) : ?>
      <div class="alert alert-info">
        <?php echo $info; ?>
      </div>
    <?php
    endif;
    $error = $this->session->get('flash_error', NULL);
    if ($error) : ?>
    <div class="alert alert-danger">
      <?php echo $error; ?>
    </div>
    <?php endif; ?>
    <?php echo $content; ?>
  </div><!-- /.container -->
  <footer id="footer" class="container">
    <?php
    echo $siteTitle . ' | ' . Kohana::lang('misc.indicia_version') . ' ' . kohana::config('version.version');
    if (kohana::config('upgrade.continuous_upgrade')) {
      echo " (dev)";
    } ?>
  </footer>

</body>
</html>
