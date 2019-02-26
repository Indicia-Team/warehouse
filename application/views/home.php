<?php

/**
 * @file
 * Home page template.
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

warehouse::loadHelpers(['report_helper']);
?>
<script type='text/javascript'>
jQuery(document).ready(function($){
  $('div#issues').hide();
  $('#issues_toggle').show();
  $('#issues_toggle').click(function() {
    $('div#issues').toggle();
    $('#issues_toggle').html(
      $('div#issues:visible').length > 0 ? 'Hide warnings' : 'Show warnings'
    )
  });
});
</script>
<?php if (version_compare($app_version, $db_version) === 1) : ?>
<div class="alert alert-warning"><p>Your database needs to be upgraded as the application version is
<?php echo $app_version; ?> but the database version is <?php echo $db_version; ?>.</p>
<a class="btn btn-primary" href="<?php echo url::base();?>index.php/home/upgrade">Run Upgrade</a>
</div>
<?php endif; ?>
<p>Indicia is a toolkit that simplifies the construction of new websites which allow data entry, mapping and reporting
of wildlife records. Indicia is an Open Source project managed by the <a href="http://www.brc.ac.uk/">Biological
Records Centre</a>, within the <a href="http://www.ceh.ac.uk/">NERC Centre for Ecology & Hydrology</a>.</p>
<ul>
  <li><a href="http://www.indicia.org.uk">Indicia project website</a></li>
  <li><a href="https://github.com/Indicia-Team">Indicia on GitHub</a></li>
</ul>
<?php
if (count($gettingStartedTips)) {
  echo '<h2>Getting started</h2>';
  foreach ($gettingStartedTips as $msg) {
    $severity = empty($msg['severity'])? 'info' : $msg['severity'];
    echo <<<TIP
<div class="alert alert-$severity alert-dismissible">
  <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
  <strong>$msg[title] - </strong>$msg[description]
</div>

TIP;
  }
}
if (count($statusWarnings)) {
  echo '<h2>Server status</h2>';
  $messages = ['danger' => [], 'warning' => [], 'other' => []];
  foreach ($statusWarnings as $msg) {
    $severity = empty($msg['severity']) ? 'info' : $msg['severity'];
    $icon = $severity === 'danger' ? 'exclamation' : $severity;
    $msg = <<<MSG
<div class="alert alert-$severity alert-dismissible">
  <div class="glyphicon glyphicon-$icon-sign">
  </div><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
  <strong>$msg[title] - </strong>$msg[description]
</div>

MSG;
    if ($severity === 'danger' || $severity === 'warning') {
      $messages[$severity][] = $msg;
    }
    else {
      $messages['other'][] = $msg;
    }
  }
  echo implode('', $messages['danger']);
  echo implode('', $messages['warning']);
  echo implode('', $messages['other']);
}
if (count($configProblems)) : ?>
  <h2>Configuration</h2>
  <p>There are configuration issues on this server.</p>
  <button id="issues_toggle" class="btn btn-warning" type="button" style="margin-left: 1em;">Show warnings</button>
  <div id="issues">
    <?php
    foreach ($configProblems as $problem) {
      echo "<div class=\"alert alert-danger\"><strong>$problem[title] - </strong>$problem[description]</div>";
    }
    ?>
  </div>
<?php endif;
