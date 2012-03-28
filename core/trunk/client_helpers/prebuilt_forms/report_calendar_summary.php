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
 * @package Client
 * @subpackage PrebuiltForms
 * @author  Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link  http://code.google.com/p/indicia/
 */

require_once('includes/form_generation.php');
require_once('includes/report.php');
require_once('includes/user.php');

/**
 * Prebuilt Indicia data form that lists the output of any report
 *
 * @package Client
 * @subpackage PrebuiltForms
 */
class iform_report_calendar_summary {

  /** 
   * Return the form metadata.
   * @return string The definition of the form.
   */
  public static function get_report_calendar_summary_definition() {
    return array(
      'title'=>'Report Calendar Summary v2',
      'category' => 'Reporting',
      'description'=>'Outputs a grid of sumary data loaded from an Indicia report, arranged by week.',
      'helpLink' => 'http://code.google.com/p/indicia/wiki/PrebuiltFormReportCalendarSummary'
    );
  }
  
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {
    return 
      array(
        array(
          'name'=>'report_name',
          'caption'=>'Report Name',
          'description'=>'Select the report to provide the output for this page.',
          'type'=>'report_helper::report_picker',
          'default'=>'library/samples/samples_list_for_cms_user.xml',
          'group'=>'Report Settings'
        ), array(
          'name' => 'param_presets',
          'caption' => 'Preset Parameter Values',
          'description' => 'To provide preset values for any report parameter and avoid the user having to enter them, enter each parameter into this '.
              'box one per line. Each parameter is followed by an equals then the value, e.g. survey_id=6. You can use {user_id} as a value which will be replaced by the '.
              'user ID from the CMS logged in user or {username} as a value replaces with the logged in username.',
          'type' => 'textarea',
          'required' => false,
          'group'=>'Report Settings'
        ),
        array(
          'name'=>'includeLocationFilter',
          'caption'=>'Include user specific location filter',
          'description'=>'Choose whether to include a filter on the locations assigned to this user using the CMS User ID location attribute. This alters how the links are highlighted, and provides a default site when creationg a new sample.',
          'type'=>'boolean',
          'default' => false,
          'required' => false,
          'group' => 'Report Settings'
        ),
        array(
          'name'=>'locationTypeFilter',
          'caption'=>'Restrict locations to type',
          'description'=>'Retrict the locations in the user specific location filter to a particular location type. The CMS User ID attribute must be defined for this location type or all location types.',
          'type'=>'select',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'extraParams'=>array('termlist_external_key'=>'indicia:location_types'),
          'default' => false,
          'required' => false,
          'group' => 'Report Settings'
        ),
        array(
          'name'=>'includeSrefInLocationFilter',
          'caption'=>'Include Sref in location filter name',
          'description'=>'When including a user specific location filter, choose whether to include the sref when generating the select name.',
          'type'=>'boolean',
          'default' => true,
          'required' => false,
          'group' => 'Report Settings'
        ),
        array(
          'name'=>'locationParam',
          'caption'=>'Location Parameter',
          'description'=>'When using the user specific location filter, pass the filter value through to the report using this parameter name.',
          'type'=>'string',
          'default' => 'location_id',
          'group' => 'Report Settings'
        ),
        array(
          'name'=>'weekstart',
          'caption'=>'Start of week definition',
          'description'=>'Define the first day of the week. There are 2 options.<br/>'.
                        "&nbsp;&nbsp;<strong>weekday=&lt;n&gt;</strong> where <strong>&lt;n&gt;</strong> is a number between 1 (for Monday) and 7 (for Sunday).<br/>".
                        "&nbsp;&nbsp;<strong>date=MMM/DD</strong> where <strong>MMM/DD</strong> is a month/day combination: e.g. choosing Apr-1 will start each week on the day of the week on which the 1st of April occurs.",
          'type'=>'string',
          'default' => 'weekday=7',
          'group' => 'Report Settings'
        ),
        array(
          'name'=>'weekHeaders',
          'caption'=>'Type of header rows to include in calendar grid',
          'description'=>'Choose whether to either the week comence date, week number or both as rows in the table header for each column.',
          'type'=>'select',
          'options' => array(
            'date' => 'Date Only',
            'number' => 'Week number only',
            'both' => 'Both'
          ),
          'required' => false,
          'group' => 'Report Settings'
        ),
        array(
          'name'=>'weekOneContains',
          'caption'=>'Week One Contains',
          'description'=>'When including a week number column, calculate week one as the week containing this date: value should be in the format <strong>MMM/DD</strong>, which is a month/day combination: e.g. choosing Apr-1 will mean week one contains the date of the 1st of April. Default is the Jan-01',
          'type'=>'string',
          'required' => false,
          'group' => 'Report Settings'
        ),
        array(
          'name'=>'weekNumberFilter',
          'caption'=>'Restrict displayed weeks',
          'description'=>'Restrict displayed weeks to between 2 weeks defined by their week numbers. Colon separated.<br />'.
                         'Leaving an empty value means the end of the year. Blank means no restrictions.<br />'.
                         'Examples: "1:30" - Weeks one to thirty inclusive. "4:" - Week four onwards. ":5" - Upto and including week five.',
          'type'=>'string',
          'required' => false,
          'group' => 'Report Settings'
        ),
        array(
          'name'=>'rowGroupColumn',
          'caption'=>'Vertical Axis',
          'description'=>'The column in the report which is used as the vertical axis in the grid.',
          'type'=>'string',
          'default'=>'taxon',
          'group' => 'Report Settings'
        ),
        array(
          'name'=>'countColumn',
          'caption'=>'Count Column',
          'description'=>'The column in the report which is used as the count associated with the occurrence. If not proviced then each occurrence has a count of one.',
          'type'=>'string',
          'required' => false,
          'group' => 'Report Settings'
        )
    );
  }

  /**
   * Retreives the options array required to set up a report according to the default
   * report parameters.
   * @param string $args
   * @param <type> $readAuth
   * @return string
   */
  private function get_report_calendar_options($args, $readAuth) {
    $presets = get_options_array_with_user_data($args['param_presets']);
    $reportOptions = array(
      'id' => 'report-summary',
      'dataSource' => $args['report_name'],
      'mode' => 'report',
      'readAuth' => $readAuth,
      'extraParams' => $presets);
    return $reportOptions;
  }

  /* This is the URL parameter used to pass the location_id filter through */
  private static $locationKey = 'location_id';
  
  private function location_control($args, $readAuth, $node)
  {
    global $user;
    // loctools is not appropriate here as it is based on a node, for which this is a very simpe one, invoking other nodes for the sample creation
    // need to scan param_presets for survey_id..
    $siteUrlParams = array(
      self::$locationKey => array(
        'name' => self::$locationKey,
        'value' => isset($_GET[self::$locationKey]) ? $_GET[self::$locationKey] : null
      )
    );
    $presets = get_options_array_with_user_data($args['param_presets']);
    if(!isset($presets['survey_id']) || $presets['survey_id']==''){
      return('<p>'.lang::get('The location selection control requires that survey_id is set in the presets in the form parameters.').'</p>');
    }
    $attrArgs = array(
        'valuetable'=>'location_attribute_value',
        'attrtable'=>'location_attribute',
        'key'=>'location_id',
        'fieldprefix'=>'locAttr',
        'extraParams'=>$readAuth,
        'survey_id'=>$presets['survey_id']);
    if(isset($args['locationTypeFilter']))
      $attrArgs['location_type_id'] = $args['locationTypeFilter'];
    $locationAttributes = data_entry_helper::getAttributes($attrArgs, false);
    $cmsAttr=extract_cms_user_attr($locationAttributes,false);
    if(!$cmsAttr){
      return('<p>'.lang::get('The location selection control requires that CMS User ID location attribute is defined for locations in this survey.').'</p>');
    }
    $attrListArgs=array('nocache'=>true,
        'extraParams'=>array_merge(array('view'=>'list', 'website_id'=>$args['website_id'],
                           'location_attribute_id'=>$cmsAttr['attributeId'], 'raw_value'=>$user->uid),
                     $readAuth),
        'table'=>'location_attribute_value');
    $attrList = data_entry_helper::get_population_data($attrListArgs);
    if (isset($attrList['error'])){
      return $attrList['error'];
    }
    $locationIDList=array();
    foreach($attrList as $attr) {
      $locationIDList[] = $attr['location_id'];
    }
    $locationListArgs=array('nocache'=>true,
        'extraParams'=>array_merge(array('view'=>'list', 'website_id'=>$args['website_id'], 'id'=>$locationIDList),
                     $readAuth),
        'table'=>'location');
    $locationList = data_entry_helper::get_population_data($locationListArgs);
    if (isset($locationList['error'])) {
      return $locationList['error'];
    }
    $ctrlid='calendar-location-select-'.$node->nid;
    $ctrl='<label for="'.$ctrlid.'" class="location-select-label">'.lang::get('Filter by location').
          ' :</label><select id="'.$ctrlid.'" class="location-select">'.
          '<option value="" class="location-select-option" '.($siteUrlParams[self::$locationKey]['value']==null ? 'selected=\"selected\" ' : '').'>'.lang::get('All locations').'</option>';
    foreach($locationList as $location){
      $ctrl .= '<option value='.$location['id'].' class="location-select-option" '.($siteUrlParams[self::$locationKey]['value']==$location['id'] ? 'selected=\"selected\" ' : '').'>'.
               $location['name'].(isset($args['includeSrefInLocationFilter']) && $args['includeSrefInLocationFilter'] ? ' ('.$location['centroid_sref'].')' : '').
               '</option>';
    }
    $ctrl.='</select>';
    // get the url parameters. Don't use $_GET, because it contains any parameters that are not in the
    // URL when search friendly URLs are used (e.g. a Drupal path node/123 is mapped to index.php?q=node/123
    // using Apache mod_alias but we don't want to know about that)
    $reloadUrl = data_entry_helper::get_reload_link_parts();
    // find the names of the params we must not include
    foreach ($reloadUrl['params'] as $key => $value) {
      if (!array_key_exists($key, $siteUrlParams)){
        $reloadUrl['path'] .= (strpos($reloadUrl['path'],'?')===false ? '?' : '&')."$key=$value";
      }
    }
    $param=(strpos($reloadUrl['path'],'?')===false ? '?' : '&').self::$locationKey.'=';
    data_entry_helper::$javascript .="
jQuery('#".$ctrlid."').change(function(){
  window.location = '".$reloadUrl['path']."' + (jQuery(this).val()=='' ? '' : '".$param."'+jQuery(this).val());
});
";
    return $ctrl;
  }

  /**
   * Return the Indicia form code
   * @param array $args Input parameters.
   * @param array $node Drupal node object
   * @param array $response Response from Indicia services after posting a verification.
   * @return HTML string
   */
  public static function get_form($args, $node, $response) {
  // Future enhancement? manager user access right who can see all walks by all people, with a person filter drop down.
  // Future enhancement? Download
  // Future Enhancement? Restrict to location_type_id
  // TODO configurable use of count attribute for value.
  // Aggregate other sample based attrs?
    global $user;
    $logged_in = $user->uid>0;
    if(!$logged_in) {
      return('<p>'.lang::get('Please log in before attempting to use this form.').'</p>');
    }
    iform_load_helpers(array('report_helper'));
    $auth = report_helper::get_read_auth($args['website_id'], $args['password']);
    // survey_id should be set in param_presets $args entry. This is then fetched by iform_report_get_report_options 
    $reportOptions = self::get_report_calendar_options($args, $auth);
    $reportOptions['id']='calendar-summary-'.$node->nid;
    if(isset($_GET['year'])) $reportOptions['year']=$_GET['year'];
    $reportOptions['weekstart']=$args['weekstart'];
    if(isset($args['weekHeaders'])) $reportOptions['weekHeaders']=$args['weekHeaders'];
    if(isset($args['weekOneContains'])) $reportOptions['weekOneContains']=$args['weekOneContains'];
    if(isset($args['weekNumberFilter'])) $reportOptions['weekNumberFilter']=$args['weekNumberFilter'];
    if(isset($args['rowGroupColumn'])) $reportOptions['rowGroupColumn']=$args['rowGroupColumn'];
    if(isset($args['countColumn']) && $args['countColumn']!='') {
      $reportOptions['countColumn']= 'attr_occurrence_'.str_replace(' ', '_', strtolower($args['countColumn'])); // assume that this is an occurrence attribute.
      $reportOptions['extraParams']['occattrs']=$args['countColumn'];
    }
    $retVal = '';
    if(isset($args['includeLocationFilter']) && $args['includeLocationFilter']){
    	$retVal .= self::location_control($args, $auth, $node);
    	$reportOptions['extraParams'][$args['locationParam']] = (isset($_GET[self::$locationKey])?$_GET[self::$locationKey]:'');
    }
    $retVal .= report_helper::report_calendar_summary($reportOptions);
    return $retVal;
  }
}