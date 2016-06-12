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
 * @package	Data Cleaner
 * @subpackage Views
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

require_once(DOCROOT.'client_helpers/data_entry_helper.php');
?>
<fieldset style="padding:5px">
<legend>Logged Action details</legend>
<?php
// We don't allow updates of the auditing records, so no inputs or form here
data_entry_helper::link_default_stylesheet();

// Not outputing table schema as they should all be in the indicia schema
// Not outputing statement_only at the moment
// Not outputing search fields
echo "<label>Event ID:</label>".$values["logged_action:id"]."<br/>".
		"<label>Postgres Transaction ID:</label>".$values["logged_action:transaction_id"]."<br/>".
		"<label>Table:</label>".$values["logged_action:event_table_name"]."<br/>".
		"<label>ID:</label>".$values["logged_action:event_record_id"]."<br/>".
		"<label>Time Stamp:</label>".$values["logged_action:action_tstamp_tx"]."<br/>".
		"<label>Action:</label>";
switch ($values["logged_action:action"]) {
	case "I" : echo "Insert";
			break;
	case "D" : echo "Delete";
			break;
	case "U" : echo "Update";
			break;
	case "T" : echo "Truncate";
			break;
	default : echo "<Unknown>";
			break;
}

// yes the use of eval is dangerous, but here it is coming from postgres as an array.
// The geom fields are long strings of characters with no break: need some jiggery pokery
//  to get it to display (ie. wrap) correctly.
if ($values["logged_action:changed_fields"] !== null) {
	echo "<fieldset style=\"padding:5px;margin-bottom:10px;\"><legend>Changed Fields</legend><table style=\"table-layout:fixed;width:100%;\">";
	$changed = eval("return array(".$values["logged_action:changed_fields"].");");
	foreach($changed as $key => $val){
   		if($val === null) $val = "NULL";
		echo "<tr><td style=\"width:160px;\">".$key.":</td><td style=\"word-wrap:break-word;overflow: hidden;\">".$val."</td></tr>";
   	}
	echo "</table></fieldset>\n";
}

echo "<fieldset style=\"padding:5px;margin-bottom:10px;\"><legend>".($values["logged_action:action"] !== "I" ? "Original" : "New")." Fields</legend><table style=\"table-layout:fixed;width:100%;\">";
$original = eval("return array(".$values["logged_action:row_data"].");");
foreach($original as $key => $val){
	if($val === null) $val = "NULL";
	echo "<tr><td style=\"width:160px;\">".$key.":</td><td style=\"word-wrap:break-word;overflow: hidden;\">".$val."</td></tr>";
}

echo "</table></fieldset>\n<fieldset style=\"padding:5px;margin-bottom:10px;\"><legend>Updating User Details</legend>".
		"<label>User ID:</label>".$values["logged_action:updated_by_id"]."<br/>".
		"<label>User Name:</label>".$values["logged_action:session_user_name"]."<br/>".
		"</fieldset>\n";

echo "<fieldset style=\"padding:5px;margin-bottom:10px;\"><legend>Associated Websites</legend><table style=\"table-layout:fixed;width:100%;\">";
foreach($websites as $website){
	$website = get_object_vars($website);
	echo "<tr><td style=\"width:160px;\">".$website['title']."</td><td style=\"word-wrap:break-word;overflow: hidden;\">ID ".$website['website_id']."</td></tr>";
}
echo "</table></fieldset></fieldset>\n";


data_entry_helper::$dumped_resources[] = 'jquery';
data_entry_helper::$dumped_resources[] = 'jquery_ui';
data_entry_helper::$dumped_resources[] = 'fancybox';
echo data_entry_helper::dump_javascript();
?>