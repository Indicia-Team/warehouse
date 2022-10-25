<!DOCTYPE html>
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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
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
<?php
echo html::stylesheet(
  [
    'vendor-other/bootstrap/css/bootstrap.min.css',
    'vendor-other/bootstrap/css/bootstrap-theme.min.css',
    'media/css/warehouse',
    'media/js/fancybox/dist/jquery.fancybox.min.css',
    'media/css/jquery-ui.min',
    'media/css/jquery.autocomplete',
    "media/themes/$theme/jquery-ui.theme.min",
    'media/css/default_site.css',
    'media/css/theme-bootstrap-3.css',
  ],
  ['screen']
);
if (isset($cssFile)) {
  echo html::stylesheet([$cssFile], ['screen']);
}
echo html::script(
  [
    'media/js/jquery.js?v=3.2.1',
    'media/js/jquery.url.js',
    'media/js/fancybox/dist/jquery.fancybox.min.js',
    'media/js/hasharray.js',
    'media/js/jquery-ui.min.js?v=1.12.1',
    'vendor-other/bootstrap/js/bootstrap.min.js',
  ], FALSE
);
if (isset($jsFile)) {
  echo html::script([$jsFile], FALSE);
}
?>
<script type="text/javascript">
$(document).ready(function() {
  if (typeof indiciaFns !== 'undefined') {
    $('a.fancybox').fancybox({ afterLoad: indiciaFns.afterFancyboxLoad });
  }
});
</script>
</head>
<body>
  <div id="banner"><a href="<?php echo url::site(); ?>"><img id="logo" src="<?php echo url::base();?>media/images/indicia-logo.png" width="255" height="100" alt="Indicia"/></a></div>
    <?php if (isset($menu)) : ?>
    <nav class="navbar navbar-inverse">
      <div class="container-fluid">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#main-navbar">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
        </div>
        <div class="collapse navbar-collapse" id="main-navbar">
          <ul class="nav navbar-nav">
          <?php foreach ($menu as $toplevel => $contents) : ?>
            <?php if (is_array($contents) && count($contents) > 0) : ?>
            <li class="dropdown">
              <a class="dropdown-toggle" data-toggle="dropdown"><?php echo $toplevel; ?>
              <span class="caret"></span></a>
              <ul class="dropdown-menu">
                <?php foreach ($contents as $menuitem => $url) : ?>
                <li><?php echo html::anchor($url, $menuitem); ?></li>
                <?php endforeach; ?>
              </ul>
            </li>
            <?php elseif (is_string($contents)) : ?>
            <li>
              <?php echo html::anchor($contents, $toplevel); ?>
            </li>
            <?php else : ?>
            <li>
              <a><?php echo $toplevel; ?></a>
            </li>
            <?php endif; ?>
          <?php endforeach; ?>
          </ul>
        </div>
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
    ?>
  </footer>

</body>
</html>
