<!-- Generates a paginated grid for table view. Requires a number of variables passed to it:
$columns - array of column names
$pagination - the pagination object
$body - gridview_table object.
-->
<script type="text/javascript" src='<?php echo url::base() ?>application/views/gridview.js' ></script>
<script type="text/javascript">
<!--
	var hardcoded_values = new Array();
	hardcoded_values[-1] = "";
<?php
			if (!is_null($this->auth_filter))
				$websites = ORM::factory('website')->in('id',$this->auth_filter['values'])->orderby('id','asc')->find_all();
			else
				$websites = ORM::factory('website')->orderby('id','asc')->find_all();

			foreach ($websites as $website) {
				echo '	hardcoded_values['.$website->id.'] = new Array(';
				$surveys = ORM::factory('survey')->where('website_id', $website->id)->orderby('title','asc')->find_all();
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
		document.forms["Filter"].elements["website_id"].disabled="";
		document.forms["Filter"].elements["survey_id"].disabled="";
	}
	else
	{
		document.forms["Filter"].elements["website_id"].disabled="disabled";
		document.forms["Filter"].elements["survey_id"].disabled="disabled";
	}
}

    function website_selection_changed(websitecombo)
	{
		// 1. get the selected value from websitecombo:
		var websitecombo_value = websitecombo.value;

		// 2. make sure survey_id combo is empty:
		document.forms["Filter"].elements["survey_id"].options.length=0;
		document.forms["Filter"].elements["survey_id"].disabled="";

		var opt = document.createElement("option");
		opt.setAttribute('value', -1);
		opt.innerHTML = "Non Survey Specific Attributes";
		document.forms["Filter"].elements["survey_id"].appendChild(opt);

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
			document.forms["Filter"].elements["survey_id"].appendChild(opt);
		}
	}
// -->
</script>


<div>
<form id='Filter' action='' method='get'>
<fieldset>
<label for="filter_type">Filter Type</label>
<select id="filter_type" name="filter_type" onchange="filter_selection_changed(this);">
<option value="1">Filter by Website</option>
<option value="2">Public Attributes</option>
<option value="3">Created by me</option>
<?php
	if (is_null($this->auth_filter))
		echo '<option value="4">All Distinct Attributes</option>';
?>
</select><br />
<?php
	if (!is_null($this->auth_filter))
		$websites = ORM::factory('website')->in('id',$this->auth_filter['values'])->orderby('title','asc')->find_all();
	else
		$websites = ORM::factory('website')->orderby('title','asc')->find_all();

	echo "<label for=\"website_id\">Website</label>\r\n";
	echo '<select id="website_id" name="website_id" onchange="website_selection_changed(this);"><option value="-1">&lt;Please select&gt;</option>';
	foreach ($websites as $website) {
		echo '	<option value="'.$website->id.'">'.$website->title.'</option>';
	}
	echo '</select>';
?>
<br />
<label for="survey_id">Survey</label>
<select id="survey_id" name="survey_id" disabled="disabled"><option>&lt;Please select the website first&gt;</option></select>

<input id='gvFilterButton' type='submit' value='Filter'/>
</fieldset>
</form>
</div>
<?php echo $filter_summary ?>
<table id='pageGrid'>
<thead>
<tr class='headingRow'>
<?php
foreach ($columns as $name => $dbtype) {
	echo "<th class='gvSortable gvCol'>".ucwords($name)."</th>";
}
foreach ($actionColumns as $name => $action) {
	echo "<th class='gvAction'>".ucwords($name)."</th>";
}
?>
</tr>
</thead>
<tbody id='gvBody'>
<?php echo $body ?>
</tbody>
</table>
<div class='pager'>
<?php echo $pagination ?>
</div>
<br/>
<form action="<?php echo url::site().$createpath; ?>" method="post">
<fieldset>
<?php if (isset($website_id)) { ?>
<input type="hidden" name="website_id" value="<?php echo html::specialchars($website_id); ?>" />
<?php } ?>
<?php if (isset($survey_id)) { ?>
<input type="hidden" name="survey_id" value="<?php echo html::specialchars($survey_id); ?>" />
<?php } ?>
<input type="submit" value="<?php echo $createbuttonname; ?>" class="default" />
</fieldset>
</form>
<br />
