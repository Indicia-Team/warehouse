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
if (isset($_POST))
  data_entry_helper::dump_errors(array('errors'=>$this->model->getAllErrors()));
?>
<form class="iform" action="<?php echo url::site(); ?>summariser_definition/save" method="post" id="entry-form"">
<fieldset>
<legend>Summariser Definition details</legend>
<?php
data_entry_helper::link_default_stylesheet();
data_entry_helper::enable_validation('entry-form');
if ($existing) :
	echo data_entry_helper::hidden_text(array(
		'fieldname'=>'summariser_definition:id',
		'default'=>html::initial_value($values, 'summariser_definition:id')
	));
else : ?>
  <p>New Record</p>
<?php endif;
echo data_entry_helper::hidden_text(array(
		'fieldname'=>'summariser_definition:survey_id',
		'default'=>html::initial_value($values, 'summariser_definition:survey_id')
));
echo data_entry_helper::text_input(array(
		'label'=>'Survey Title',
		'fieldname'=>'survey:title',
		'default'=>html::initial_value($other_data, 'survey_title'),
		'disabled' => 'disabled'
));
echo data_entry_helper::checkbox(array(
		'label'=>'Check for missing',
		'fieldname'=>'summariser_definition:check_for_missing',
		'default'=>html::initial_value($values, 'summariser_definition:check_for_missing'),
		'helpText' => 'Enable checks for missed data - this is data entered before the last scheduled task run, but which is not yet represented in the summary table. This check has a performance impact. Needs to be selected during any initial catch up period.',
));
echo data_entry_helper::text_input(array(
		'label' => 'Max Number of Records',
		'fieldname' => 'summariser_definition:max_records_per_cycle',
		'default' => html::initial_value($values, 'summariser_definition:max_records_per_cycle'),
		'helpText' => 'The maximum number of occurrence records processed for this survey per invocation of the scheduled task.',
		'validation' => array('required','integer','minimum[1]')
));
?>
<p>Only one period option (weekly) available at the moment.</p>
<?php 
echo data_entry_helper::hidden_text(array(
		// 'caption'=>'Summarisation Period',
		'fieldname'=>'summariser_definition:period_type',
		'default'=>'W' // html::initial_value($values, 'summariser_definition:period_type')
));
echo data_entry_helper::text_input(array(
	'label'=>'Period Start',
	'fieldname'=>'summariser_definition:period_start',
	'default'=>html::initial_value($values, 'summariser_definition:period_start'),
	'helpText' => 'Define the first day of each period. There are 2 options.<br/>'.
				"&nbsp;&nbsp;<strong>weekday=&lt;n&gt;</strong> where <strong>&lt;n&gt;</strong> is a number between 1 (for Monday) and 7 (for Sunday).<br/>".
				"&nbsp;&nbsp;<strong>date=MMM/DD</strong> where <strong>MMM/DD</strong> is a month/day combination: e.g. choosing Apr-1 will start each week on the day of the week on which the 1st of April occurs.<br/>",
	'validation'=>'required'
));
echo data_entry_helper::text_input(array(
		'label'=>'Period One Contains',
		'fieldname'=>'summariser_definition:period_one_contains',
		'default'=>html::initial_value($values, 'summariser_definition:period_one_contains'),
		'helpText' => 'Calculate week one as the week containing this date: value should be in the format <strong>MMM/DD</strong>, which is a month/day combination: e.g. choosing Apr-1 will mean week one contains the date of the 1st of April. Default is the Jan-01',
		'validation'=>'required'
));
echo data_entry_helper::select(array(
		'label'=>'Attribute to Sum',
		'fieldname'=>'summariser_definition:occurrence_attribute_id',
		'lookupValues' => $other_data['occAttrs'],
		'default'=>html::initial_value($values, 'summariser_definition:occurrence_attribute_id'),
		'helpText' => 'The occurrence attribute which is used as the count associated with the occurrence. If not provided then each occurrence has a count of one.'
));
echo data_entry_helper::checkbox(array(
		'label'=>'Calculate Estimates',
		'fieldname'=>'summariser_definition:calculate_estimates',
		'default'=>html::initial_value($values, 'summariser_definition:calculate_estimates')
));
?>
<fieldset><legend>Data Handling</legend>
<?php 
echo data_entry_helper::select(array(
	'label'=>'Summary Data Combination method',
	'fieldname'=>'summariser_definition:data_combination_method',
	'lookupValues' => array('A'=>'Add all occurrences together',
							'M'=>'Choose the value from the sample with the greatest count',
							'L'=>'Average over all samples for that location during that period'),
	'default'=>html::initial_value($values, 'summariser_definition:data_combination_method'),
	'helpText' => 'When data is aggregated for a location/period combination, this determines how.'
));
echo data_entry_helper::select(array(
	'label'=>'Data Rounding',
	'fieldname'=>'summariser_definition:data_rounding_method',
	'lookupValues' => array('N'=>'To the nearest integer, .5 rounds up',
							'U'=>'Up: To the integer greater than or equal to the value',
							'D'=>'Down: To the integer less than or equal to the value',
							'X'=>'None (may result in non-integer values)'),
	'default'=>html::initial_value($values, 'summariser_definition:data_rounding_method'),
	'helpText' => 'When data is averaged, this determines what rounding is carried out. Note that anything between 0 and 1 will be rounded up to 1.'
));
?>
</fieldset><fieldset><legend>Estimate Generation</legend>
<p>Only one interpolation option (linear) available at the moment.</p>
<?php 
// Only one interpolation option at the moment. This may change in future. Keep hidden control until that point.
// 'L' = 'Linear interpolation'
echo data_entry_helper::hidden_text(array(
		// 'caption'=>'Interpolation method',
		'fieldname'=>'summariser_definition:interpolation',
		'default'=>'L' // html::initial_value($values, 'summariser_definition:interpolation')
));
echo data_entry_helper::text_input(array(
	'label'=>'Season Limits',
	'fieldname'=>'summariser_definition:season_limits',
	'default'=>html::initial_value($values, 'summariser_definition:season_limits'),
	'helpText' => 'This is a comma separated pair of the week numbers for the start and end of the season. When provided, and data is not entered for these weeks, the value is taken as zero, irrespective of the First/Last value processing. First/Last value processing is not carried out outwith these weeks.'
));

echo data_entry_helper::select(array(
	'label'=>'First Value Processing',
	'fieldname'=>'summariser_definition:first_value',
	'lookupValues' => array('X'=>'No special processing', 'H'=>'The entry for the previous week is half the entered value'),
	'default'=>html::initial_value($values, 'summariser_definition:first_value'),
	'helpText' => 'When encountering the first entered value, this determines what happens.'
));
echo data_entry_helper::select(array(
	'label'=>'Last Value Processing',
	'fieldname'=>'summariser_definition:last_value',
	'lookupValues' => array('X'=>'No special processing', 'H'=>'The entry for the next week is half the entered value'),
	'default'=>html::initial_value($values, 'summariser_definition:last_value'),
	'helpText' => 'When encountering the last entered value, this determines what happens.'
));
?>
</fieldset>
<?php

echo $metadata;
echo html::form_buttons($existing, false, false);
data_entry_helper::$dumped_resources[] = 'jquery';
data_entry_helper::$dumped_resources[] = 'jquery_ui';
data_entry_helper::$dumped_resources[] = 'fancybox';
echo data_entry_helper::dump_javascript();
?>
</fieldset>
</form>