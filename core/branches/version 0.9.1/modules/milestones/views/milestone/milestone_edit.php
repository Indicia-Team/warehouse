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
 * @package	Milestones
 * @subpackage Views
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

require_once(DOCROOT.'client_helpers/data_entry_helper.php');
require_once(DOCROOT.'client_helpers/prebuilt_forms/includes/report_filters.php');

if (isset($_POST))
  data_entry_helper::dump_errors(array('errors'=>$this->model->getAllErrors()));

if (!empty($_GET['filter_id']))
  $url = url::site().'milestone/save/'.$this->uri->argument(1).'?filter_id='.$_GET['filter_id'];
else
  $url = url::site().'milestone/save/'.$this->uri->argument(1);
?>
<form class="iform" id="milestones-form"action="
  <?php echo $url; ?>
" method="post">
<fieldset>
<legend>Milestone details</legend>
<?php
data_entry_helper::link_default_stylesheet();

if (isset($values['milestone:id'])) : ?>
  <input type="hidden" name="milestone:id" value="<?php echo html::initial_value($values, 'milestone:id'); ?>" />
<?php echo $metadata; endif;

echo data_entry_helper::hidden_text(array(
  'fieldname'=>'milestone:id',
  'default'=>html::initial_value($values, 'milestone:id')
));

echo data_entry_helper::hidden_text(array(
  'fieldname'=>'website_id',
  'default'=>html::initial_value($values, 'milestone:website_id')
));

echo data_entry_helper::text_input(array(
  'label'=>'Title',
  'fieldname'=>'milestone:title',
  'class'=>'control-width-4',
  'default'=> html::initial_value($values, 'milestone:title')
));

echo data_entry_helper::select(array(
  'label' => 'Count what?',
  'fieldname' => 'milestone:entity',
  'lookupValues' =>array('T'=>'Species or taxa', 'O'=>'Records', 'M'=>'Media/photos'),
  'default'=>html::initial_value($values, 'milestone:entity'),
  'helpText'=>'Are you targetting a total count of species, records or media/photos for this milestone?'
));

echo data_entry_helper::text_input(array(
  'label'=>'Count',
  'fieldname'=>'milestone:count',
  'class'=>'control-width-2',
  'default'=> html::initial_value($values, 'milestone:count'),
  'helpText' => 'What is the target number that must be reached to hit the milestone?'
));

echo data_entry_helper::text_input(array(
  'label'=>'Group ID',
  'fieldname'=>'milestone:group_id',
  'class'=>'control-width-2',
  'default'=> html::initial_value($values, 'milestone:group_id'),
  'helpText' => 'Optional ID of the recording group associated with the milestone, only users that are members of this group will receive the milestone award.'
));

echo data_entry_helper::textarea(array(
  'label'=>'Success message',
  'fieldname'=>'milestone:success_message',
  'class'=>'control-width-6',
  'default'=>html::initial_value($values, 'milestone:success_message'),
  'helpText' => 'This message will be sent to the user on reaching the milestone as a notification.'
));

echo data_entry_helper::text_input(array(
  'label'=>'Awarded by',
  'fieldname'=>'milestone:awarded_by',
  'class'=>'control-width-6',
  'default'=> html::initial_value($values, 'milestone:awarded_by'),
  'helpText' => 'Which organisation is awarding the milestone? This will appear in the from field for the notification sent to the recorder.'
));

//The filter title is actually generated using the milestone title we enter. There are issues with using the built-in validator to detect duplicate titles because the filter supermodel is validated
//first. The filter requires a unique title/sharing/created_by_id option that isn't included in the model, the issue is only
//detected once the system tries to add the filter to the database, this will fail with a general error without even getting as far as doing the milestone model's
//title duplciate detection.
//So to fix this, collect the existing filters from the database so we can compare the titles with the one we create and then
//do the validation manually.
$readAuth = data_entry_helper::get_read_auth(0-$_SESSION['auth_user']->id, kohana::config('indicia.private_key'));
$existingFilterData = data_entry_helper::get_population_data(array(
  'table' => 'filter',
  'extraParams' => $readAuth,
  'nocache' => true
));

//When we save a milestone when we need to automatically set the filter title as there isn't a separate field
//to fill this in.
//Also hide the "who" filter as we don't need this for milestones as they can apply to all users
//Also manually do the unique milestone/filter title validation (see note above)
data_entry_helper::$javascript .= "
var existingFilterData=".json_encode($existingFilterData).";  
$('#pane-filter_who').hide();
$('#milestones-form').submit(function() {
  $('#filter-title-val').val('" .'Filter for milestone'. " ' + $('#milestone\\\:title').val());
  for (var i = 0; i<existingFilterData.length;i++) {
    //Note we must allow a duplicate title in the situaton where the duplicate title is for the already existing item
    if (existingFilterData[i]['title']==$('#filter-title-val').val() && existingFilterData[i]['id']!=$('#filter\\\:id').val()) {
      alert('The filter title is generated from the milestone title you have entered and would cause a duplicate filter title, please choose a different title');
      return false;
    }
  }
  $('#filter-def-val').val(JSON.stringify(indiciaData.filter.def));
});\n";

$readAuth = data_entry_helper::get_read_auth(0-$_SESSION['auth_user']->id, kohana::config('indicia.private_key'));
$filterPanelHTML = '<h3>Specify the filter used to define which records count</h3>';
$hiddenPopupDivs='';
$filterPanelHTML .= report_filter_panel($readAuth, array(
  'allowLoad'=>false,
  'allowSave' => false,
  'embedInExistingForm' => true,
  'runningOnWarehouse' => true,
  'taxon_list_id' => kohana::config('cache_builder_variables.master_list_id'),
  'website_id' => html::initial_value($values, 'milestone:website_id') ? html::initial_value($values, 'milestone:website_id') : $values['website_id']
), $this->uri->argument(1), $hiddenStuff);
// fields to auto-create a filter record for this group's defined set of records
$filterPanelHTML .= data_entry_helper::hidden_text(array('fieldname'=>'filter:id' ,'default'=>html::initial_value($values, 'filter:id')));
$filterPanelHTML .= '<input type="hidden" name="filter:title" id="filter-title-val"/>';
$filterPanelHTML .= '<input type="hidden" name="filter:definition" id="filter-def-val"/>';
$filterPanelHTML .= '<input type="hidden" name="filter:sharing" value="R"/>';
echo $filterPanelHTML;

echo html::form_buttons(html::initial_value($values, 'milestone:id')!=null, false, false);
data_entry_helper::$dumped_resources[] = 'jquery';
data_entry_helper::$dumped_resources[] = 'jquery_ui';
data_entry_helper::$dumped_resources[] = 'fancybox';
data_entry_helper::enable_validation('milestones-form');
data_entry_helper::link_default_stylesheet();
echo data_entry_helper::dump_javascript();
?>
</fieldset>
</form>
<?php
echo $hiddenStuff;
?>