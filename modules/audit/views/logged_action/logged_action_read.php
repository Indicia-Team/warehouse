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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
 */

// Not outputing table schema as they should all be in the indicia schema
// Not outputing statement_only at the moment
// Not outputing search fields.
switch ($values['logged_action:action']) {
  case 'I':
    $action = 'Insert';
    break;

  case 'D':
    $action = 'Delete';
    break;

  case 'U':
    $action = 'Update';
    break;

  case 'T':
    $action = 'Truncate';
    break;

  default:
    $action = '<Unknown>';
    break;

}
echo <<<HTML
<table class="table">
  <caption>Logged action details</caption>
  <tbody>
    <tr>
      <th for="row">Event ID</th>
      <td>{$values['logged_action:transaction_id']}</td>
    </tr>
    <tr>
      <th for="row">Postgres transaction ID</th>
      <td>{$values['logged_action:transaction_id']}</td>
    </tr>
    <tr>
      <th for="row">Table</th>
      <td>{$values['logged_action:event_table_name']}</td>
    </tr>
    <tr>
      <th for="row">ID</th>
      <td>{$values['logged_action:event_record_id']}</td>
    </tr>
    <tr>
      <th for="row">Time stamp</th>
      <td>{$values['logged_action:action_tstamp_tx']}</td>
    </tr>
    <tr>
      <th for="row">Action</th>
      <td></td>
    </tr>
  </tbody>
</table>

HTML;

// Yes the use of eval is dangerous, but here it is coming from postgres as an array.
// The geom fields are long strings of characters with no break: need some jiggery pokery
// to get it to display (ie. wrap) correctly.
if ($values['logged_action:changed_fields'] !== NULL) {
  $changed = eval("return array({$values['logged_action:changed_fields']});");
  $rows = '';
  foreach ($changed as $key => $val) {
    if ($val === NULL) {
      $val = "NULL";
    }
    $rows .= <<<HTML
  <tr>
    <th for="row">$key</th>
    <td style="word-wrap:break-word;overflow: hidden;">$val</td>
  </tr>

HTML;
  }
  echo <<<HTML
<table class="table">
  <caption>Changed fields</caption>
$rows;
</table>

HTML;
}

$fieldsType = $values['logged_action:action'] !== 'I' ? 'Original' : 'New';
$rows = '';
$original = eval("return array({$values['logged_action:row_data']});");
foreach ($original as $key => $val) {
  if ($val === NULL) {
    $val = "NULL";
  }
  $rows .= <<<HTML
  <tr>
    <th for="row">$key</th>
    <td style="word-wrap:break-word;overflow: hidden;">$val</td>
  </tr>

HTML;
}
echo <<<HTML
<table class="table">
  <caption>$fieldsType fields</caption>
$rows
</table>

HTML;

echo <<<HTML
<table class="table">
  <caption>Updating user details</caption>
  <tr>
    <th>User ID</th>
    <td>{$values['logged_action:updated_by_id']}</td>
  </tr>
  <tr>
    <th>User name</th>
    <td>{$values['logged_action:session_user_name']}</td>
  </tr>
</table>

HTML;

$rows = '';
foreach ($websites as $website){
  $website = get_object_vars($website);
  $rows .= <<<HTML
  <tr>
    <th>$website[title]</th>
    <td style="word-wrap:break-word;overflow: hidden;\">ID $website[website_id]</td>
  </tr>

HTML;
}
echo <<<HTML
<table class="table">
  <caption>Associated websites</caption>
  $rows
</table>

HTML;
