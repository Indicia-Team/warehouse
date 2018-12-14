<?php

/**
 * @file
 * View template for the list of fields available for posting into a survey.
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
?>
<p>The following list of fields are available for submission when building code which
  posts records into this survey. Those marked with a <span class="deh-required">*</span>
  are mandatory.</p>
<table class="ui-widget">
  <thead class="ui-widget-header">
  <tr>
    <th>Database field name</th>
    <th>Description</th>
    <th>Notes</th>
  </tr>
  </thead>
  <tbody class="ui-widget-content">
  <?php
  $odd = FALSE;
  foreach ($fields as $field => $description) {
    $isRequired = in_array($field, $requiredFields);
    $note = '';
    if (!$description) {
      $description = ucfirst(preg_replace('/[\s_:]+/', ' ', str_replace(array('fk_', '_id'), array('', ''), $field)));
    }
    if (strpos($field, ':fk_') !== FALSE) {
      // Also output a row for an ID submission rather than a lookup.
      $field .= ' or ' . str_replace(':fk_', ':', $field);
      $note = 'Use fk_* to submit text for automatic lookup, otherwise a record ID';
    }
    if ($isRequired) {
      $description .= ' <span class="deh-required">*</span>';
    }
    $oddClass = $odd ? ' class="odd"' : '';
    echo "<tr$oddClass><td>$field</td><td>$description</td><td>$note</td></tr>";
    $odd = !$odd;
  }
  ?>
  </tbody>
</table>
