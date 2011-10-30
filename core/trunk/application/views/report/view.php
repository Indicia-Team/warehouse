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
<p><?php echo $report['description']['description']; ?></p>
<table class="report ui-widget ui-widget-content"><thead class="ui-widget-header">
<?php
$content = $report['content'];

foreach ($content['columns'] as $col => $det)
{
  if (!array_key_exists('visible', $det) || $det['visible']!='false') {
    $display = isset($det['display']) ? $det['display'] : $col;
    echo "<th>$display</th>";
  }
}
?>
</thead><tbody>
<?php
$row_class= ($report['description']['row_class']!='') ? 'class="'.$report['description']['row_class'].'" ' : '';
foreach ($content['data'] as $row)
{
  echo "<tr $row_class>";
  foreach ($content['columns'] as $col => $det)
  {
    if (!array_key_exists('visible', $det) || $det['visible']!='false') {
      $style= (isset($det['style']) && $det['style']!='') ? 'style="'.$det['style'].'" ' : '';
      $class= (isset($det['class']) && $det['class']!='') ? 'class="'.$det['class'].'" ' : '';
      echo "<td $style $class>".$row[$col]."</td>";
    }
  }
  echo "</tr>";
}
?>
</tbody></table>