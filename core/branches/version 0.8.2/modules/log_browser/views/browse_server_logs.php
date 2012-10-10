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
 * @package	Log Browser
 * @subpackage Views
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */
?>

<form action="" method="post">
<?php 
require_once(DOCROOT.'client_helpers/data_entry_helper.php');
if (isset($_POST['from']) && !preg_match('/^[0-2][0-9]:[0-5][0-9](:[0-5][0-9])?$/', $_POST['from'])) {
  echo '<p>The start time you entered was not recognised.</p>';
  unset($_POST['from']);
}
if (isset($_POST['to']) && !preg_match('/^[0-2][0-9]:[0-5][0-9](:[0-5][0-9])?$/', $_POST['to'])) {
  echo '<p>The end time you entered was not recognised.</p>';
  unset($_POST['to']);
}

echo data_entry_helper::select(array(
  'label' => 'Select the log date',
  'lookupValues' => $files,
  'fieldname'=>'file',
  'labelClass' => 'auto',
  'suffixTemplate'=>'nosuffix',
  'default' => isset($_POST['file']) ? $_POST['file'] : null
));
echo data_entry_helper::text_input(array(
  'label'=>'between',
  'labelClass' => 'auto',
  'fieldname'=>'from',
  'suffixTemplate'=>'nosuffix',
  'default' => isset($_POST['from']) ? $_POST['from'] : '00:00:00'
));
echo data_entry_helper::text_input(array(
  'label'=>'and',
  'labelClass' => 'auto',
  'fieldname'=>'to',
  'suffixTemplate'=>'nosuffix',
  'default' => isset($_POST['to']) ? $_POST['to'] : '24:00:00'
));
echo data_entry_helper::select(array(
  'label'=>'level',
  'labelClass' => 'auto',
  'fieldname'=>'level',
  'suffixTemplate'=>'nosuffix',
  'default' => isset($_POST['level']) ? $_POST['level'] : 'debug',
  'lookupValues' => array('4' => 'All messages', '3'=> 'Errors, warnings and notices', '2' => 'Errors and warnings', '1'=>'Errors only')
));
?>
<input type="submit" value="Go" />
</form>
<?php
if (isset($_POST['file'])) {
  // A file has been selected, so output the filtered log content.
  $filename = DOCROOT . 'application/logs/' . $_POST['file'];
  if (file_exists($filename)) {
    $file = fopen($filename, 'r');
    if ($file===FALSE) {
      echo '<p class="page-notice">The log file could not be opened.</p>';
    } else {
      echo '<br/><h2>Showing log file for '.str_replace('.log.php','',$_POST['file']).'</h2>';
      $time = '00:00:00';
      $threshold = 4;
      while ($line=fgets($file)) {
        if (preg_match('/^(\d{4})\D?(0[1-9]|1[0-2])\D?([12]\d|0[1-9]|3[01])/', $line)) {
          // line has a date at start so is a new log entry. Get the time for filtering
          $time = substr($line,11,8);
          $remaining=substr($line, 31);
          $class=substr($remaining, 0, strpos($remaining, ':'));
          switch ($class) {
            case 'debug': $threshold=4; break;
            case 'info':  $threshold=3; break;
            case 'alert': $threshold=2; break;
            case 'error': $threshold=1; break;
          }
          $line = substr($remaining, strpos($remaining, ':')+2);
          if (skip_row($time, $threshold)) continue;
          echo "<span class=\"log-message log-$class\">$time $class</span>";
        }
        if (skip_row($time, $threshold)) continue;
        echo '<span class="log-line">'.strip_tags($line)."</span><br/>\n";
      }
    }
  }

}

function skip_row($time, $threshold) {
  if (isset($_POST['from']) && strtotime($time)<strtotime($_POST['from'])) 
    return true;
  if (isset($_POST['to']) && strtotime($time)>strtotime($_POST['to'])) 
    return true;
  if ($threshold>$_POST['level'])
    return true;
  return false;
}
?>