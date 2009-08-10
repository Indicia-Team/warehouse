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
<table class="report ui-widget ui-widget-content\"><thead class="ui-widget-header">
<?php
foreach ($report['columns'] as $col => $det)
{
  $display = $det['display'] ? $det['display'] : $col;
  echo "<th>$display</th>";
}
?>
</thead><tbody>
<?php
foreach ($report['data'] as $row)
{
  echo "<tr>";
  foreach ($report['columns'] as $col => $det)
  {
    $style = $det['style'];
    echo "<td style='$style'>".$row[$col]."</td>";
  }
  echo "</tr>";
}
?>
</tbody></table>