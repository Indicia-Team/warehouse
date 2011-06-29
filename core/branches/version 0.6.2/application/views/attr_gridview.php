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

 /**
  * Generates a paginated grid for table view. Requires a number of variables passed to it:
  *  $columns - array of column names
  *  $pagination - the pagination object
  *  $body - gridview_table object.
  */
?>
<script type="text/javascript">
<!--
  var hardcoded_values = new Array();
  hardcoded_values[-1] = "";
<?php
      if (!is_null($this->auth_filter))
        $websites = ORM::factory('website')->in('id',$this->auth_filter['values'])->where(array('deleted'=>'f'))->orderby('id','asc')->find_all();
      else
        $websites = ORM::factory('website')->orderby('id','asc')->find_all();

      foreach ($websites as $website) {
        echo '	hardcoded_values['.$website->id.'] = new Array(';
        $surveys = ORM::factory('survey')->where('website_id', $website->id)->where(array('deleted'=>'f'))->orderby('title','asc')->find_all();
        $option_list = array();
        foreach ($surveys as $survey) {
          $option_list[] = 'new Array('.$survey->id.', "'.$survey->title.'")';
        }
        echo implode(",", $option_list).');';
      }
?>

function filter_selection_changed(filtercombo)
{
  // 1. get the selected value from websitecombo:
  var filtercombo_value = filtercombo.value;

  // 2. make sure survey_id combo is empty:
  if ( filtercombo_value == 1 )
  {
    document.forms["filterForm-<?php echo $id; ?>"].elements["website_id"].disabled="";
    document.forms["filterForm-<?php echo $id; ?>"].elements["survey_id"].disabled="";
  }
  else
  {
    document.forms["filterForm-<?php echo $id; ?>"].elements["website_id"].disabled="disabled";
    document.forms["filterForm-<?php echo $id; ?>"].elements["survey_id"].disabled="disabled";
  }
}

function website_selection_changed(websitecombo)
{
  // 1. get the selected value from websitecombo:
  var websitecombo_value = websitecombo.value;

  // 2. make sure survey_id combo is empty:
  document.forms["filterForm-<?php echo $id; ?>"].elements["survey_id"].options.length=0;
  if (websitecombo_value!=-1) {
    document.forms["filterForm-<?php echo $id; ?>"].elements["survey_id"].disabled="";
  
    var opt = document.createElement("option");
    opt.setAttribute('value', -1);
    opt.innerHTML = "Non Survey Specific Attributes";
    document.forms["filterForm-<?php echo $id; ?>"].elements["survey_id"].appendChild(opt);
  
    // 3. loop throught the hard-coded values:
    for (var i=0;i<hardcoded_values[websitecombo_value].length;i++)
    {
      // dynamically create a new option element:
      var opt = document.createElement("option");
      // set the value-attribute of it:
      opt.setAttribute('value', hardcoded_values[websitecombo_value][i][0]);
      // set the displayed value:
      opt.innerHTML = hardcoded_values[websitecombo_value][i][1];
      // append this option to survey_id:
      document.forms["filterForm-<?php echo $id; ?>"].elements["survey_id"].appendChild(opt);
    }
  } else {
	  document.forms["filterForm-<?php echo $id; ?>"].elements["survey_id"].disabled="disabled";
	  var opt = document.createElement("option");
    opt.setAttribute('value', -1);
    opt.innerHTML = "&lt;Please select the website first&gt;";
    document.forms["filterForm-<?php echo $id; ?>"].elements["survey_id"].appendChild(opt);
  }
}

jQuery(document).ready(function() {
	website_selection_changed($('#website_id')[0]);
	<?php if (array_key_exists('survey_id', $_GET)) : ?>
	$('#survey_id').val('<?php echo $_GET['survey_id'];?>');
	<?php endif; ?>
});

// -->
</script>


<div>
<form action='<?php echo url::site(Router::$routed_uri); ?>' method="get" id="filterForm-<?php echo $id; ?>">
<fieldset class="filter">
<?php 
$filter_type = array_key_exists('filter_type', $_GET) ? $_GET['filter_type'] : null;
$website_id = array_key_exists('website_id', $_GET) ? $_GET['website_id'] : null;
$survey_id = array_key_exists('survey_id', $_GET) ? $_GET['survey_id'] : null;
?>
<label for="filter_type">Filter Type</label>

<select id="filter_type" name="filter_type" onchange="filter_selection_changed(this);">
<option value="1"<?php if ($filter_type=="1") echo ' selected="selected"'; ?>>Filter by Website</option>
<option value="2"<?php if ($filter_type=="2") echo ' selected="selected"'; ?>>Public Attributes</option>
<option value="3"<?php if ($filter_type=="3") echo ' selected="selected"'; ?>>Created by me</option>
<?php
  if (is_null($this->auth_filter))
    echo '<option value="4"'. (($filter_type=="4") ? ' selected="selected"' : '') .'>All Distinct Attributes</option>';
?>
</select><br />
<?php
  if (!is_null($this->auth_filter))
    $websites = ORM::factory('website')->in('id',$this->auth_filter['values'])->where('deleted','f')->orderby('title','asc')->find_all();
  else
    $websites = ORM::factory('website')->where('deleted','f')->orderby('title','asc')->find_all();

  echo "<label for=\"website_id\">Website</label>\r\n";
  echo '<select id="website_id" name="website_id" ';
  if ($filter_type && $filter_type!="1") {
    echo 'disabled="disabled" ';
  }
  echo 'onchange="website_selection_changed(this);"><option value="-1">&lt;Please select&gt;</option>';
  foreach ($websites as $website) {
    echo '	<option value="'.$website->id.'"';
    if ($website_id==$website->id) echo ' selected="selected"';
    echo '>'.$website->title.'</option>';
  }
  echo '</select>';
?>
<br />
<label for="survey_id">Survey</label>
<select id="survey_id" name="survey_id" disabled="disabled"><option>&lt;Please select the website first&gt;</option></select> 
<input type="submit" value="Filter" class="ui-corner-all ui-state-default"/>
</fieldset>
</form>
</div>
<?php echo $filter_summary ?>
<table id="pageGrid-<?php echo $id; ?>" class="ui-widget ui-widget-content">
<thead class="ui-widget-header">
<tr class="headingRow">
<?php
foreach ($columns as $name => $dbtype) {
  echo "<th class='gvSortable gvCol'>".str_replace('_', ' ', ucwords($name))."</th>";
}
foreach ($actionColumns as $name => $action) {
  echo "<th class='gvAction'>".str_replace('_', ' ', ucwords($name))."</th>";
}
?>
</tr>
</thead>
<tbody id="gridBody-<?php echo $id; ?>">
<?php echo $body ?>
</tbody>
</table>
<div id="pager-<?php echo $id; ?>">
<?php echo $pagination ?>
</div>
<br/>
<form action="<?php echo url::site().$createpath."?filter_type=$filter_type&amp;website_id=$website_id&amp;survey_id=$survey_id"; ?>" method="post">
<fieldset>
<input type="submit" value="<?php echo $createbuttonname; ?>" class="button ui-corner-all ui-state-default" />
</fieldset>
</form>
<br />
