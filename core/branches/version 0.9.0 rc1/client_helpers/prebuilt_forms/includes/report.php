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

/**
 * List of methods that can be used for a prebuilt form report configuration.
 * @package Client
 * @subpackage PrebuiltForms.
 */

/**
 * Return a minimal list of parameter definitions for a form that includes definition of a report.
 * @return array List of parameter definitions.
 */
function iform_report_get_minimal_report_parameters() {
  return array(
    array(
      'name'=>'report_name',
      'caption'=>'Report Name',
      'description'=>'Select the report to provide the output for this page.',
      'type'=>'report_helper::report_picker',
      'group'=>'Report Settings'
    ), array(
      'name' => 'param_presets',
      'caption' => 'Preset parameter values',
      'description' => 'To provide preset values for any report parameter and avoid the user having to enter them, enter each parameter into this '.
          'box one per line. Each parameter is followed by an equals then the value, e.g. survey_id=6. You can use {user_id} as a value which will be replaced by the '.
          'user ID from the CMS logged in user or {username} as a value replaces with the logged in username. If you have installed the Profile module then you can also '.
          'use {profile_*} to refer to the value of a field in the user\'s profile (replace the asterisk to make the field name match the field created in the profile). '.
          'Parameters with preset values are not shown in the parameters form and therefore can\'t be overridden by the user.',
      'type' => 'textarea',
      'required' => false,
      'group'=>'Report Settings'
    ), array(
      'name' => 'param_defaults',
      'caption' => 'Default parameter values',
      'description' => 'To provide default values for any report parameter which allow the report to run initially but can be overridden, enter each parameter into this '.
          'box one per line. Each parameter is followed by an equals then the value, e.g. survey_id=6. You can use {user_id} as a value which will be replaced by the '.
          'user ID from the CMS logged in user or {username} as a value replaces with the logged in username. If you have installed the Profile module then you can also '.
          'use {profile_*} to refer to the value of a field in the user\'s profile (replace the asterisk to make the field name match the field created in the profile). '.
          'Unlike preset parameter values, parameters referred to by default parameter values are displayed in the parameters form and can therefore be changed by the user.',
      'type' => 'textarea',
      'required' => false,
      'group'=>'Report Settings'
    ), array(
      'name' => 'param_ignores',
      'caption' => 'Default params to exclude from the form',
      'description' => 'Provide a list of the parameter names which are in the Default Parameter Values but should not appear in the parameters form. An example usage of this '.
          'is to provide parameters that can be overridden via a URL parameter.',
      'type' => 'textarea',
      'required' => false,
      'group'=>'Report Settings'
    ), 
      array(
      'name' => 'items_per_page',
      'caption' => 'Items per page',
      'description' => 'Maximum number of rows shown on each page of the table',
      'type' => 'int',
      'default' => 20,
      'required' => true,
      'group'=>'Report Settings'
    ),
  );
}

/**
 * Return a list of parameter definitions for a form that includes definition of a report.
 * @return array List of parameter definitions.
 */
function iform_report_get_report_parameters() {
  return array_merge(
    iform_report_get_minimal_report_parameters(),
    array(
      array(
        'name' => 'output',
        'caption' => 'Output Mode',
        'description' => 'Select what combination of the params form and report output will be output. This can be used to develop a single page '.
            'with several reports linked to the same parameters form, e.g. using the Drupal panels module.',
        'type' => 'select',
        'required' => true,
        'options' => array(
          'default'=>'Include a parameters form and output',
          'form'=>'Parameters form only - the output will be displayed elsewhere.',
          'output'=>'Output only - the params form will be output elsewhere.',
        ),
        'default' => 'default',
        'group'=>'Report Settings'
      ), array(
        'name' => 'report_group',
        'caption' => 'Report group',
        'description' => 'When using several reports on a single page (e.g. <a href="http://code.google.com/p/indicia/wiki/DrupalDashboardReporting">dashboard reporting</a>) '.
            'you must ensure that all reports that share a set of input parameters have the same report group as the parameters report.',
        'type' => 'text_input',
        'default' => 'report',
        'group' => 'Report Settings'
      ), array(
        'name' => 'remember_params_report_group',
        'caption' => 'Remember report parameters group',
        'description' => 'Enter any value in this parameter to allow the report to save its parameters for the next time the report is loaded. '.
          'The parameters are saved site wide, so if several reports share the same value and the same report group then the parameter '.
          'settings will be shared across the reports even if they are on different pages of the site. This functionality '.
          'requires cookies to be enabled on the browser.',
        'type'=>'text_input',
        'required'=>false,
        'default' => '',
        'group'=>'Report Settings'
      ), array(
        'name' => 'params_in_map_toolbar',
        'caption' => 'Params in map toolbar',
        'description' => 'Should the report input parameters be inserted into a map toolbar instead of displaying a panel of input parameters at the top? '.
            'This is only useful when there is a map output onto the page which has a toolbar in the top or bottom position.',
        'type' => 'checkbox',
        'required' => false,
        'group' => 'Report Settings'
      ), array(
        'name' => 'row_class',
        'caption' => 'Row Class',
        'description' => 'A CSS class to add to each row in the grid. Can include field value replacements in braces, e.g. {certainty} to construct '.
            'classes from field values, e.g. to colour rows in the grid according to the data.',
        'type' => 'text_input',
        'default' => '',
        'required' => 'false',
        'group' => 'Report Settings'
      ), array(
        'name' => 'refresh_timer',
        'caption' => 'Automatic reload seconds',
        'description' => 'Set this value to the number of seconds you want to elapse before the report will be automatically reloaded, useful for '.
        'displaying live data updates at BioBlitzes. Combine this with Page to reload to define a sequence of pages that load in turn.',
        'type' => 'int',
        'required' => false,
        'group'=>'Page Refreshing'
      ), array(
        'name' => 'load_on_refresh',
        'caption' => 'Page to reload',
        'description' => 'Provide the full URL of a page to reload after the number of seconds indicated above.',
        'type' => 'string',
        'required' => false,
        'group'=>'Page Refreshing'
      )
    )
  );
}

/**
 * Retreives the options array required to set up a report according to the default
 * report parameters.
 * @global <type> $indicia_templates
 * @param string $args
 * @param <type> $readAuth
 * @return string
 */
function iform_report_get_report_options($args, $readAuth) {
  // handle auto_params_form for backwards compatibility
  if (empty($args['output']) && !empty($args['auto_params_form'])) {
    if (!$args['auto_params_form'])
      $args['output']='output';
  }
  if (isset($args['map_toolbar_pos']) && $args['map_toolbar_pos']=='map')
    // report params cannot go in the map toolbar if displayed as overlay on map
    $args['params_in_map_toolbar']=false;
  $r = '';
  require_once('user.php');
  $presets = get_options_array_with_user_data($args['param_presets']);
  $defaults = get_options_array_with_user_data($args['param_defaults']);
  $ignores = isset($args['param_ignores']) ? helper_base::explode_lines($args['param_ignores']) : array();
  // default columns behaviour is to just include anything returned by the report
  $columns = array();
  // this can be overridden
  if (isset($args['columns_config']) && !empty($args['columns_config']))
    $columns = json_decode($args['columns_config'], true);
  $reportOptions = array(
    'id' => 'report-grid',
    'reportGroup' => isset($args['report_group']) ? $args['report_group'] : '',
    'rememberParamsReportGroup' => isset($args['remember_params_report_group']) ? $args['remember_params_report_group'] : '',
    'dataSource' => $args['report_name'],
    'mode' => 'report',
    'readAuth' => $readAuth,
    'columns' => $columns,
    'itemsPerPage' => empty($args['items_per_page']) ? 20 : $args['items_per_page'],
    'extraParams' => $presets,
    'paramDefaults' => $defaults,
    'ignoreParams' => $ignores,
    'galleryColCount' => isset($args['gallery_col_count']) ? $args['gallery_col_count'] : 1,
    'headers' => isset($args['gallery_col_count']) && $args['gallery_col_count']>1 ? false : true,
    'paramsInMapToolbar'=>isset($args['params_in_map_toolbar']) ? $args['params_in_map_toolbar'] : false    
  );
   // put each param control in a div, which makes it easier to layout with CSS
  if (!isset($args['params_in_map_toolbar']) || !$args['params_in_map_toolbar']) {
    $reportOptions['paramPrefix']='<div id="container-{fieldname}" class="param-container">';
    $reportOptions['paramSuffix']='</div>';
  }
  // If in Drupal, allow the params panel to collapse.
  if (function_exists('drupal_add_js')) {
    if (function_exists('hostsite_add_library') && (!defined('DRUPAL_CORE_COMPATIBILITY') || DRUPAL_CORE_COMPATIBILITY!=='7.x')) {
      hostsite_add_library('collapse');
      $reportOptions['fieldsetClass'] = 'collapsible';
    }
  }
  
  if (empty($args['output']) || $args['output']=='default') {
    $reportOptions['autoParamsForm'] = true;
  } elseif ($args['output']=='form') {
    $reportOptions['autoParamsForm'] = true;
    $reportOptions['paramsOnly'] = true;
  } else {
    $reportOptions['autoParamsForm'] = false;
  }
  if (!empty($args['row_class'])) {
    $reportOptions['rowClass'] = $args['row_class'];
  }
  // Set up a page refresh for dynamic update of the report at set intervals
  if (isset($args['refresh_timer']) && $args['refresh_timer']!==0 && is_numeric($args['refresh_timer'])) { // is_numeric prevents injection
    if (isset($args['load_on_refresh']) && !empty($args['load_on_refresh']))
      report_helper::$javascript .= "setTimeout('window.location=\"".$args['load_on_refresh']."\";', ".$args['refresh_timer']."*1000 );\n";
    else
      report_helper::$javascript .= "setTimeout('window.location.reload( false );', ".$args['refresh_timer']."*1000 );\n";
  }
  return $reportOptions;
}

/** 
 * Takes a set of report parameters and applies preferences from the user's EasyLogin profile to the report parameters. Assumes
 * parameters called ownData, ownLocality, ownGroups. See the library/occurrences/explore_list report for an example.
 */
function iform_report_apply_explore_user_own_preferences(&$reportOptions) {
  $allParams = array_merge($reportOptions['paramDefaults'], $reportOptions['extraParams']);
  global $user;
  if (!isset($user->profile_indicia_user_id) && function_exists('profile_load_profile'))
    profile_load_profile($user);
  // Unless ownData explicitly set, we either default it to unchecked, or we set it unchecked and hidden if the user account
  // is not on the warehouse
  if (!array_key_exists('ownData', $allParams)) {
    if (!empty($user->profile_indicia_user_id))
      $reportOptions['paramDefaults']['ownData']=0;
    else
      $reportOptions['extraParams']['ownData']=0;
  }
  // Unless ownLocality explicitly set, we either default it to checked, or we set it unchecked and hidden if the user account
  // has no location preferences set
  if (!array_key_exists('ownLocality', $allParams)) {
    if (!empty($user->profile_location))
      $reportOptions['paramDefaults']['ownLocality']=1;
    else
      $reportOptions['extraParams']['ownLocality']=0;
  }
  // Unless ownGroups explicitly set, we either default it to checked, or we set it unchecked and hidden if the user account
  // has no taxon groups set
  if (!array_key_exists('ownGroups', $allParams)) {
    if (!empty($user->profile_taxon_groups))
      $reportOptions['paramDefaults']['ownGroups']=1;
    else
      $reportOptions['extraParams']['ownGroups']=0;
  }
}