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

$i = 0;
foreach ($table as $item)
{
  echo '<tr class="'.($i % 2 == 0) ? 'evenRow">' : 'oddRow">';
  $i++;
  $fields = array();
  $a = $item->as_array();
  foreach ($columns as $col => $name)
  {
    if (array_key_exists($col, $a))
    {
      $fields[$col] = $a[$col];
    }
  }
  foreach ($fields as $field) {
    echo "<td>";
    if ($field!==NULL)
    {
      if (preg_match('/^http/', $field))
      echo html::anchor($field, $field);
      else
  echo $field;
    }
    echo "</td>";
  }
  foreach ($actionColumns as $name => $action)
  {
    echo "<td>";
    $action = preg_replace("/£([a-zA-Z_\-]+)£/e", "\$item->__get('$1')", $action);
    echo html::anchor($action, $name, array('class'=>'ui-state-default ui-corner-all grid-button'));
    echo "</td>";
  }
  echo "</tr>";
}
?>
