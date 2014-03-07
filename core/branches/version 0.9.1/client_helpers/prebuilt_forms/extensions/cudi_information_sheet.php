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
        'Site Name'=>'parent_location_name',
        'Country'=>'country',
        'Central Grid Ref/Coordinates'=>'centroid',
        'Habitat'=>'habitat',
        'Local Organiser Region'=>'local_organiser_region',
        'Official Reason For Change'=>'official_reason_for_change',
    );
    foreach ($fields as $caption=>$databaseValue) {
      $attrsTemplate.='<div class="field ui-helper-clearfix"><span>'.$caption.':</span><span>{'.$databaseValue.'}</span></div>';
    } 

    if (!empty($options['alternative_1_attr_id'])&&
        !empty($options['alternative_2_attr_id'])&&
        !empty($options['country_attr_id'])&&
        !empty($options['habitat_attr_id'])&&   
        !empty($options['official_reason_for_change_attr_id'])&&
        !empty($options['site_location_type_id'])&&
        !empty($options['loc_org_reg_attr_id'])) { 
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
          'site_location_type_id'=>$options['site_location_type_id'],
          'loc_org_reg_attr_id'=>$options['loc_org_reg_attr_id'],
          'sharing'=>'reporting'
        )
      ));
    } else {      
      return '<div><h2>Plesse configure the Form Structure for the CUDI Information Sheet report</h2></div>';
    }
     
  }
  
  /*
   * Control used to display the Surveys associated with a Count Unit on the Cudi Information Sheet
   */ 
  public function informationSheetSurveysReport($auth, $args, $tabalias, $options, $path) {
    //The Surveys associated with the Count Unit are held as location_attribute_values so collect these
    $surveysAttributeData = data_entry_helper::get_population_data(array(
      'table' => 'location_attribute_value',
      'extraParams' => $auth['read'] + array('location_id' => $_GET['id'], 'location_attribute_id'=>$options['surveys_attribute_id']),
      'nocache' => true,
      'sharing' => $sharing
    ));
    //Create a table to put the data in
    $r = '<div><h3>Surveys</h3><table><tr><th>Name</th><th>Date</th></tr>';
    //Cycle around each Survey associated with the Count Unit and add rows to the grid.
    foreach ($surveysAttributeData as $surveysAttributeDataItem) {
      //The Survey Id and Date are json_encoded in the location_atribute_value so decode.
      $decoded = json_decode($surveysAttributeDataItem['value']);
      //The stored data does not include the Survey name, so we need to collect it.
      $surveysData = data_entry_helper::get_population_data(array(
        'table' => 'survey',
        'extraParams' => $auth['read'] + array('id' => $decoded[0]),
        'nocache' => true,
        'sharing' => $sharing
      ));
      $r .= '<tr><td>';
      $r .= $surveysData[0]['title'];
      $r .= '</td>';
      $r .= '<td>';
      //The second item in the $decoded array holds the date associated with the Survey entry.
      $r .= $decoded[1];
      $r .= '</td>';
      $r .= '</tr>';
    }
    $r .= '</table></div>';
    return $r;
  }
  
  /*
   * A button link to the cudi form for the same location as being viewed on the information sheet
   */
  public function cudiFormButtonLink($auth, $args, $tabalias, $options, $path) {
    global $user;
    //Get the Count Units that are in the user's tasks list using the same report.
    $getNormalUserEditableCountUnitData  = data_entry_helper::get_report_data(array(
      'dataSource'=>'reports_for_prebuilt_forms/cudi/my_cudi_tasks',
      'readAuth'=>$auth['read'],
      'extraParams'=>array('clean_url' => $options['clean_url'],
                           'cudi_form_url' => $options['cudi_form_url'],
                           'deactivate_site_attribute_id' => $options['deactivate_site_attribute_id'],
                           'preferred_boundary_attribute_id' => $options['preferred_boundary_attribute_id'],
                           'count_unit_boundary_type_id'=>$options['count_unit_boundary_type_id'],
                           'count_unit_type_id'=>$options['count_unit_type_id'],
                           'is_complete_attribute_id'=>$options['is_complete_attribute_id'],
                           'preferred_sites_attribute_id'=>$options['preferred_sites_attribute_id'],
                           'current_user_id'=>$user->profile_indicia_user_id)
    ));
    $isNormalUserAccessibleCountUnitIds = array();
    //Convert the Count Units in the user's task list into an array of ids only.
    foreach($getNormalUserEditableCountUnitData as $idx => $isNormalUserAccessibleDirtyItem) {
      $isNormalUserAccessibleCountUnitIds[$idx] = $isNormalUserAccessibleDirtyItem['id'];
    }
    //Only show the Cudi Form button for admin users or the Count Unit is the user's task list 
    if (in_array($_GET['id'],$isNormalUserAccessibleCountUnitIds)||$options['admin_mode']) {
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
  