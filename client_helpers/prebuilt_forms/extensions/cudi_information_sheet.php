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
 * @package	Client
 * @subpackage PrebuiltForms
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Extension class that supplies a new control which allows the user to click on a button to navigate to the cudi form page.
 */
class extension_cudi_information_sheet {
  /*
   * Control is not visible to user, instead it appends "dynamic-" to the front of the $_GET parameter which is then used
   * by the system "behind the scenes" to automatically load that parameter into reports on that page.
   * The parameter is currently always 'id' because if a count unit page is saved and the user is returned to the Cudi Information
   * Sheet, then the code that automatically handles that return is designed to supply 'id' as the parameter to the Cudi Information Sheet.
   */
  public function autoLoadReportParamFromGet($auth, $args, $tabalias, $options, $path) {
    $_REQUEST['dynamic-id']=$_GET['id'];
  }
  
  /*
   * Freeform report about a count unit.
   */
  public function informationSheetReport($auth, $args, $tabalias, $options, $path) {
    //List an array of display labels and their database values.
    //We then loop around an html template of one report line inserting each label and database value name until we have a 
    //full template.
    //We then call a freeform report that then populates this template.
    $fields = array(
        'Count Unit Name'=>'name',
        'Alternative Name 1'=>'alternative_1',
        'Alternative Name 2'=>'alternative_2',
        'Abbreviation'=>'abbreviation',
        'Parent Loction Name'=>'parent_location_name',
        'Country'=>'country',
        'Habitat'=>'habitat',
        'local_organiser_region'=>'Local Organiser Region',
        'survey'=>'Survey',
        'first_used_date'=>'Survey First Used Date',
        'Official Reason For Change'=>'official_reason_for_change',
    );
    foreach ($fields as $caption=>$databaseValue) {
      $attrsTemplate.='<div class="field ui-helper-clearfix"><span>'.$caption.':</span><span>{'.$databaseValue.'}</span></div>';
    }  
    //Call the report to populate the html template
    return $attrs_report = report_helper::freeform_report(array(
        'readAuth' => $auth['read'],
        'class'=>'information-sheet-details-fields',
        'dataSource'=>'reports_for_prebuilt_forms/CUDI/cudi_information_sheet',
        'bands'=>array(array('content'=>$attrsTemplate)),
        'extraParams'=>array(
          'id'=>$_GET['id'],
          //The report needs to know the ids of various attributes associated with the Count Unit so it can collect the attribute data
          //(as the ids will vary when the code is run against different databases). These ids are supplied as options in the Edit Tab
          //Form Structure.
          'alternative_1_attr_id'=>$options['alternative_1_attr_id'],
          'alternative_2_attr_id'=>$options['alternative_2_attr_id'],
          'country_attr_id'=>$options['country_attr_id'],
          'habitat_attr_id'=>$options['habitat_attr_id'],
          'official_reason_for_change_attr_id'=>$options['official_reason_for_change_attr_id'],
          'sharing'=>'reporting'
        )
      ));
  }
  /*
   * A button link to the cudi form for the same location as being viewed on the information sheet
   */
  public function cudiFormButtonLink($auth, $args, $tabalias, $options, $path) {
    //Get the "Is Complete?" location attribute from the database.
    $locData = data_entry_helper::get_population_data(array(
      'table' => 'location_attribute_value',
      'extraParams' => $auth['read'] + array('location_id' => $_GET['id'], 'location_attribute_id'=>$options['is_complete_attribute_id']),
      'nocache' => true
    ));

    $isComplete = $locData[0]['value'];
    $normalUser = !$options['admin_mode'];
    //Hide the Cudi Form button link if the user is a "normal user" and the Count Unit has been marked as "Is Complete". Other situations link is visible.
    if (!($isComplete && $normalUser)) {
      global $base_url;
      $cudiFormOptions = explode('|',$options['cudiFormOptions']);
      $cudiFormPath = $cudiFormOptions[0];
      $cudiFormParam = $cudiFormOptions[1];
      $cudi_form_url=(variable_get('clean_url', 0) ? '' : '?q=').$cudiFormPath.(variable_get('clean_url', 0) ? '?' : '&').$cudiFormParam.'='.$_GET[$options['urlParameter']];
      $cudiFormButtonLink = '<div>If you think any of this information is incorrect please submit a CUDI form</br>';
      $cudiFormButtonLink .= 
      "<FORM>
        <INPUT Type=\"BUTTON\" VALUE=\"Cudi Form\" ONCLICK=\"window.location.href='$cudi_form_url'\">
      </FORM>";
      return $cudiFormButtonLink;
    }
  }  
}
  