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
The following records failed to import.
<table class="ui-widget ui-widget-content">
<thead class="ui-widget-header">
<tr>
<?php
foreach ($headers as $header) {
  echo "<th>$header</th>";
}
?>
</tr></thead><tbody>
<?php
$i=0;
foreach ($problems as $record) {
  echo '<tr class="';
  echo ($i % 2 == 0) ? 'evenRow">' : 'oddRow">';
  $i++;
  foreach ($record as $attr) {
    echo "<td>$attr</td>";
  }
  echo "</tr>";
}
?>
</tbody></table>
<?php echo html::anchor(url::base() . $errorFile,'Download a CSV file containing the records which failed to import.'); ?>