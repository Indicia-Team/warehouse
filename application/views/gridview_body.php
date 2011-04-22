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
  echo '<tr class="';
  echo ($i % 2 == 0) ? 'evenRow">' : 'oddRow">';
  $i++;
  $displayfields = array();
  $allfields = $item->as_array();
  foreach ($columns as $col => $name)
  {
    if (array_key_exists($col, $allfields))
    {
      $displayfields[$col] = $allfields[$col];
    }
  }
  $idx=0;
  foreach ($displayfields as $col => $value) {
    echo "<td>";
    if ($value!==NULL)
    {
      if (preg_match('/^http/', $value))
        echo html::anchor($value, $value);
      elseif ($col == 'path')
        // output a thumbnail with a link, suitable for lightbox.
        echo html::sized_image($value);
      else
        echo $value;
    }
    echo "</td>";
    $idx++;
  }
  if (count($actionColumns)>0) {
    echo "<td>";
    foreach ($actionColumns as $name => $action)
    {
      if ($this->get_action_visibility($allfields, $name)) {
        $action = preg_replace("/#([a-zA-Z_\-]+)#/e", "\$item->__get('$1')", $action);
        $safename = str_replace(' ','-',strtolower($name));
        echo html::anchor($action, str_replace(' ','&nbsp;',$name), array('class'=>'grid-action grid-action-'.$safename));
      }
    }
    echo "</td>";
  }

  echo "</tr>";
}
?>
