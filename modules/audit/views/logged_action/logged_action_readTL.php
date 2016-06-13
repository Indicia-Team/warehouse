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
<fieldset style="padding:5px;">
<legend>Logged Action details</legend>
<?php
// We don't allow updates of the auditing records, so no inputs or form here
data_entry_helper::link_default_stylesheet();
// we can have any number of top level records - none, one or many
echo "<label>Postgres Transaction ID:</label>".$search["transaction_id"]."<br/>".
		"<label>Table:</label>".$search["search_table_name"]."<br/>".
		"<label>ID:</label>".$search["search_key"]."<br/>";

if($values === null || count($values)==0) {
	echo "No top level record changes in this transaction.<br/>";
} else {
	foreach($values as $value){
		$value = get_object_vars($value);
		// Not outputing table schema as they should all be in the indicia schema
		// Not outputing statement_only at the moment
		echo "<fieldset style=\"padding:5px;margin-bottom:10px;\"><legend>Event ID : ".$value["id"]."</legend>".
				"<label>Time Stamp:</label>".$value["action_tstamp_tx"]."<br/>".
				"<label>Action:</label>";
		switch ($value["action"]) {
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
		if ($value["changed_fields"] !== null) {
			echo "<fieldset style=\"padding:5px;margin-bottom:10px;\"><legend>Changed Fields</legend><table style=\"table-layout:fixed;width:100%;\">";
			$changed = eval("return array(".$value["changed_fields"].");");
			foreach($changed as $key => $val){
				if($val === null) $val = "NULL";
				echo "<tr><td style=\"width:160px;\">".$key.":</td><td style=\"word-wrap:break-word;overflow: hidden;\">".$val."</td></tr>";
			}
			echo "</table></fieldset>\n";
		}
		
		echo "<fieldset style=\"padding:5px;margin-bottom:10px;\"><legend>".($value["action"] !== "I" ? "Original" : "New")." Fields</legend><table style=\"table-layout:fixed;width:100%;\">";
		$original = eval("return array(".$value["row_data"].");");
		foreach($original as $key => $val){
			if($val === null) $val = "NULL";
			echo "<tr><td style=\"width:160px;\">".$key.":</td><td style=\"word-wrap:break-word;overflow: hidden;\">".$val."</td></tr>";
		}
		
		echo "</table></fieldset>\n<fieldset style=\"padding:5px;margin-bottom:10px;\"><legend>Updating User Details</legend>".
				"<label>User ID:</label>".$value["updated_by_id"]."<br/>".
				"<label>User Name:</label>".$value["session_user_name"]."<br/>".
				"</fieldset></fieldset>\n";
	}
}

if($subtableData === null || count($subtableData)==0) {
	echo "No child record changes in this transaction.<br/>";
} else {
	echo "<fieldset style=\"padding:5px;margin-bottom:10px;\"><legend>Associated Child Records</legend><table class=\"ui-widget ui-widget-content report-grid\"><thead class=\"ui-widget-header\"><tr>".
			"<th class=\"ui-widget-header\">Event ID</th><th class=\"ui-widget-header\">Table</th><th class=\"ui-widget-header\">ID</th><th class=\"ui-widget-header\">Action</th><th  class=\"ui-widget-header\"></th>".
			"</thead><tbody>";
	foreach($subtableData as $value){
		$value = get_object_vars($value);
		// Not outputing table schema as they should all be in the indicia schema
		// Not outputing statement_only at the moment
		echo "<td>".$value["id"]."</td><td>".$value["event_table_name"]."</td><td>".$value["event_record_id"]."</td><td>";
		switch ($value["action"]) {
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
		echo "</td><td><a href=\"".url::base(true)."logged_action/read/".$value["id"]."\">View</a></td></tr>";
	}
	echo "</tbody></table></fieldset>";
}
echo "<fieldset style=\"padding:5px;margin-bottom:10px;\"><legend>Associated Websites</legend><table style=\"table-layout:fixed;width:100%;\">";
foreach($websites as $website){
	$website = get_object_vars($website);
	echo "<tr><td style=\"width:160px;\">".$website['title']."</td><td style=\"word-wrap:break-word;overflow: hidden;\">ID ".$website['website_id']."</td></tr>";
}
echo "</table></fieldset>\n";

?>
</fieldset>
<?php
data_entry_helper::$dumped_resources[] = 'jquery';
data_entry_helper::$dumped_resources[] = 'jquery_ui';
data_entry_helper::$dumped_resources[] = 'fancybox';
echo data_entry_helper::dump_javascript();
?>