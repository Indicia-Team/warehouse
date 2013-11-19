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
class iform_report_calendar_grid {

  private static $locationKey = 'location_id';
  private static $siteUrlParams;
  
  /** 
   * Return the form metadata.
   * @return string The definition of the form.
   */
  public static function get_report_calendar_grid_definition() {
    return array(
      'title'=>'Report Calendar Grid',
      'category' => 'Reporting',
      'description'=>'Outputs a grid of data loaded from an Indicia report, arranged as a calendar.',
      'helpLink' => 'http://code.google.com/p/indicia/wiki/PrebuiltFormReportCalendarGrid'
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
          'default'=>'library/samples/samples_list_for_cms_user2',
          'group'=>'Report Settings'
        ),
        array(
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
          'description'=>'Choose whether to include a filter on the locations assigned to this user using the CMS User ID location attribute. This alters how the links are highlighted, and provides a default site when creating a new sample.',
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
          'name'=>'includeWeekNumber',
          'caption'=>'Include Week Number column in calendar grid',
          'description'=>'Choose whether to include the week number column in the calendar grid.',
          'type'=>'boolean',
          'default' => false,
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
          'name'=>'newURL',
          'caption'=>'New Sample URL',
          'description'=>'The URL to invoke when selecting a date which does not have a previous sample associated with it.<br />'.
                         'To the end of this will be appended "&date=&lt;X&gt;" whose value will be the date selected (see also "New Sample Location Parameter").',
          'type'=>'string',
          'group' => 'Report Settings'
        ),
        array(
          'name'=>'newURLLocationParam',
          'caption'=>'New Sample Location Parameter',
          'description'=>'When generating a new sample and the location filter has been set, use this parameter to pass the location '.
                         'ID to the next form.',
          'default'=>'location_id',
          'type'=>'string',
          'group' => 'Report Settings'
        ),
        array(
          'name'=>'existingURL',
          'caption'=>'Existing Sample URL',
          'description'=>'The URL to invoke when selecting an existing sample.<br />'.
                         'To the end of this will be appended "&sample_id=&lt;n&gt;".',
          'type'=>'string',
          'group' => 'Report Settings'
        )
    );
  }

  // Although public, this function is only to be used as a callback.
  public static function build_link($records, $options, $cellContents){
    // siteIDFilter not present if all selected.
    $cellclass="newLink";
    foreach($records as $record){
      $location=empty($record["location_name"]) ? $record["entered_sref"] : $record["location_name"];
      $cellContents .= '<a href="'.$options["existingURL"].'sample_id='.$record["sample_id"].'" title="View existing sample for '.$location.' on '.$options['consider_date'].' (ID='.$record["sample_id"].')" >'.$location.'</a> ';
      // we assume that the location has been filtered in the report.
      $cellclass='existingLink';
    }
    // we want to be able to add more.
    $c = count($records);
    if(!isset($options['siteIDFilter']) || $c==0)
      $cellContents .= ' <a href="'.$options["newURL"].'date='.$options['consider_date'].'" class="newLink" title="Create a new sample on '.$options['consider_date'].
        (isset($options['siteIDFilter']) && ($c==0 || $records[0]['location_id']!=$options['siteIDFilter']) ? ' for the selected location.' : '').'"></a> ';
    return array('cellclass'=>$cellclass, 'cellContents'=>$cellContents);
  }

  /**
   * Retreives the options array required to set up a report according to the default
   * report parameters.
   * @param string $args
   * @param <type> $readAuth
   * @return string
   */
  private static function  get_report_calendar_options($args, $readAuth) {
    self::$siteUrlParams = array(
        self::$locationKey => array(
            'name' => self::$locationKey,
            'value' => isset($_GET[self::$locationKey]) ? $_GET[self::$locationKey] : null
        )
    );
    $presets = get_options_array_with_user_data($args['param_presets']);
    $reportOptions = array(
      'id' => 'report-grid',
      'dataSource' => $args['report_name'],
      'mode' => 'report',
      'readAuth' => $readAuth,
      'extraParams' => $presets);
    if (self::$siteUrlParams[self::$locationKey]['value'] != null)
      $reportOptions['extraParams']['location_id'] = self::$siteUrlParams[self::$locationKey]['value'];
    return $reportOptions;
  }

  private static function location_control($args, $readAuth, $node)
  {
    global $user;
    // loctools is not appropriate here as it is based on a node, for which this is a very simple one, invoking other nodes for the sample creation
    // need to scan param_presets for survey_id..
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
    if(isset($args['locationTypeFilter']) && $args['locationTypeFilter']!="")
      $attrArgs['location_type_id'] = $args['locationTypeFilter'];
    $locationAttributes = data_entry_helper::getAttributes($attrArgs, false);
    $cmsAttr=extract_cms_user_attr($locationAttributes,false);
    if(!$cmsAttr){
      return('<p>'.lang::get('The location selection control requires that CMS User ID location attribute is defined for locations in this survey. If restricting to a particular location type, this must be set in the parameters page for this form instance.').'</p>');
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
    $ctrl='<label for="'.$ctrlid.'" class="location-select-label">'.lang::get('Filter by site').
          ' :</label><select id="'.$ctrlid.'" class="location-select">'.
          '<option value="" class="location-select-option" '.(self::$siteUrlParams[self::$locationKey]['value']==null ? 'selected=\"selected\" ' : '').'>'.lang::get('All sites').'</option>';
    foreach($locationList as $location){
      $ctrl .= '<option value='.$location['id'].' class="location-select-option" '.(self::$siteUrlParams[self::$locationKey]['value']==$location['id'] ? 'selected=\"selected\" ' : '').'>'.
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
      if (!array_key_exists($key, self::$siteUrlParams)){
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
  // Future enhancement? Download list of surveys used as basis for calendar
  // Future Enhancement? Restrict to location_type_id
    global $user;
    $logged_in = $user->uid>0;
    if(!$logged_in) {
      return('<p>'.lang::get('Please log in before attempting to use this form.').'</p>');
    }
    iform_load_helpers(array('report_helper'));
    $auth = report_helper::get_read_auth($args['website_id'], $args['password']);
    /* survey_id should be set in param_presets $args entry. This is then fetched by iform_report_get_report_options */
    $reportOptions = self::get_report_calendar_options($args, $auth);
    // get the grid output before outputting the download link, so we can check if the download link is needed.
    $reportOptions['id']='calendar-grid-'.$node->nid;
    if(isset($_GET['year'])) {
      $reportOptions['year']=$_GET['year'];
      $reportOptions['viewPreviousIfTooEarly']=false;
    }
    $reportOptions['weekstart']= $args['weekstart'];
    $reportOptions['includeWeekNumber']=(isset($args['includeWeekNumber']) && $args['includeWeekNumber']==true);
    if(isset($args['weekOneContains'])) {
      $reportOptions['weekOneContains']= $args['weekOneContains'];
    }
    if(isset($args['weekNumberFilter'])) {
      $reportOptions['weekNumberFilter']= $args['weekNumberFilter'];
    }
    $reportOptions['newURL']=$args['newURL'];
    $reportOptions['existingURL']=$args['existingURL'];
    $reportOptions['buildLinkFunc']=array('iform_report_calendar_grid', 'build_link');
    if(isset($_GET['location_id'])){
      $reportOptions['siteIDFilter']=$_GET['location_id']; // this gets passed through to buildLinkFunc Callback
      $reportOptions['newURL'].=(strpos($reportOptions['newURL'],'?')===false ? '?' : '&').$args['newURLLocationParam'].'='.$_GET['location_id'];
    }
    // note that we want to see samples entered on other days, so do not want to filter by the location_id.
    $grid = (isset($args['includeLocationFilter']) && $args['includeLocationFilter'] ? self::location_control($args, $auth, $node) : '').
            report_helper::report_calendar_grid($reportOptions);
    return $grid;
  }
}