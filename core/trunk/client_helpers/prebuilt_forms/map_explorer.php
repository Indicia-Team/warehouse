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

require_once('includes/map.php');
require_once('includes/report.php');

/**
 * Map Explorer. A prebuilt form integrating a map and grid with various options for exploring the data.
 * 
 * @package Client
 * @subpackage PrebuiltForms
 */
class iform_map_explorer {
  
  /** 
   * Return the form metadata. Note the title of this method includes the name of the form file. This ensures
   * that if inheritance is used in the forms, subclassed forms don't return their parent's form definition.
   * @return array The definition of the form.
   */
  public static function get_map_explorer_definition() {
    return array(
      'title'=>'Map explorer',
      'category' => 'Reporting',
      'description'=>'A map plus grid of data, with various options for exploring the data. This is designed to integrate with the Easy Login feature\'s '.
          'preferred taxon groups and locality for the logged in user and is therefore specific to Drupal.'
    );
  }
  
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   * @todo: Implement this method
   */
  public static function get_parameters() {   
    $r = array_merge(
      iform_map_get_map_parameters(),
      iform_report_get_minimal_report_parameters(),
      array(
        array(
          'name' => 'downloadOwnDataOnly',
          'caption' => 'Download own data only',
          'description' => 'If ticked then the user is only allowed to download data when showing just their own data.',
          'type' => 'checkbox',
          'default' => false,
          'required' => false
        ),
        array(
          'name' => 'includeEditLink',
          'caption' => 'Include edit link',
          'description' => 'Include an edit link for each row that was input by the current user',
          'type' => 'checkbox',
          'default' => true,
          'required' => false
        ),
        array(
          'name' => 'includeEditLinkPath',
          'caption' => 'Path to page used for edits',
          'description' => 'The path to the page used for edits. This is just the site relative path, e.g. http://www.example.com/index.php?q=enter-records needs '.
              'to be input as just enter-records. The path is called with the id of the record in a parameter called occurrence_id.',
          'type' => 'text_input',
          'default' => '',
          'required' => false
        ),
        array(
          'name' => 'columns_config',
          'caption' => 'Columns Configuration',
          'description' => 'Define a list of columns with various configuration options when you want to override the '.
              'default output of the report.',
          'type' => 'jsonwidget',
          'schema' => '{
  "type":"seq",
  "title":"Columns List",
  "sequence":
  [
    {
      "type":"map",
      "title":"Column",
      "mapping": {
        "fieldname": {"type":"str","desc":"Name of the field to output in this column. Does not need to be specified when using the template option."},
        "display": {"type":"str","desc":"Caption of the column, which defaults to the fieldname if not specified."},
        "actions": {
          "type":"seq",
          "title":"Actions List",
          "sequence": [{
            "type":"map",
            "title":"Actions",
            "desc":"List of actions to make available for each row in the grid.",
            "mapping": {
              "caption": {"type":"str","desc":"Display caption for the action\'s link."},
              "visibility_field": {"type":"str","desc":"Optional name of a field in the data which contains true or false to define the visibility of this action."},
              "img": {"type":"str","desc":"Set img to the path to an image to use an image for the action instead of a text caption - the caption '.
                  'then becomes the image\'s title. The image path can contain {rootFolder} to be replaced by the root folder of the site, in this '.
                  'case it excludes the path parameter used in Drupal when dirty URLs are used (since this is a direct link to a URL)."},
              "url": {"type":"str","desc":"A url that the action link will point to, unless overridden by JavaScript. The url can contain tokens which '.
                  'will be subsituted for field values, e.g. for http://www.example.com/image/{id} the {id} is replaced with a field called id in the current row. '.
              'Can also use the subsitution {currentUrl} to link back to the current page, {rootFolder} to represent the folder on the server that the current PHP page is running from, and '.
              '{imageFolder} for the image upload folder"},
              "urlParams": {
                "type":"map",
                "subtype":"str",
                "desc":"List of parameters to append to the URL link, with field value replacements such as {id} begin replaced '.
                    'by the value of the id field for the current row."
              },
              "class": {"type":"str","desc":"CSS class to attach to the action link."},
              "javascript": {"type":"str","desc":"JavaScript that will be run when the link is clicked. Can contain field value substitutions '.
                  'such as {id} which is replaced by the value of the id field for the current row. Because the javascript may pass the field values as parameters to functions, '.
                  'there are escaped versions of each of the replacements available for the javascript action type. Add -escape-quote or '.
                  '-escape-dblquote to the fieldname. For example this would be valid in the action javascript: foo(\"{bar-escape-dblquote}\"); '.
                  'even if the field value contains a double quote which would have broken the syntax."}
            }
          }]
        },
        "visible": {"type":"bool","desc":"Should this column be shown? Hidden columns can still be used in templates or actions."},
        "template": {"type":"txt","desc":"Allows you to create columns that contain dynamic content using a template, rather than just the output '.
        'of a field. The template text can contain fieldnames in braces, which will be replaced by the respective field values. '.
        'Note that template columns cannot be sorted by clicking grid headers." }
      }
    }
  ]
}',
          'required' => false,
          'group'=>'Report Settings'
        ),
      )
    );
    // @todo Set the default report name
    foreach ($r as &$param) {
      if ($param['name']==='report_name') {
        $param['default'] = 'library/occurrences/explore_list';
        $param['description'] .= '<br/>The report used must meet a set of criteria to be used for this form, as illustrated by the library/occurrences/explore_list '.
            'report. The report should have the following columns:<br/>'.
            '<strong>belongs_to_user</strong> - a boolean indicating if the record belongs to (i.e. is editable by) the logged in user.<br/>'.
            '<strong>certainty</strong> - text output which identifies the certainty of the record if known. This is appended to the word '.
            'certainty to make a class which is attached to the row HTML, allowing you to use CSS to style the row output. E.g. if the report '.
            'outputs C in this column then the row HTML will have a class certaintyC.<br/>'.
          '<br/>The report should have the following parameters:<br/>'.
            '<strong>location_id</strong> - Warehouse ID of the user\'s preferred recording location passed automatically from the user\'s account. Only applied if own_locality is 1.<br/>'.
            '<strong>ownLocality</strong> - Boolean (1 or 0) parameter which defines if the output should be filtered to the contents of the location identified by location_id.<br/>'.
            '<strong>taxon_groups</strong> Takes a comma separated list of taxon_group_ids as a string suitable for insertion into an SQL in (...) clause. '.
            'Will be passed the user\'s preferred species groups from their user account if the Easy Login feature is installed. Only applied if ownGroups is 1.<br/>'.
            '<strong>ownGroups</strong> - Boolean (1 or 0) parameter which defines if the output should be filtered to the contents of the taxon groups identified by taxon_groups.<br/>'.
            '<strong>currentUser</strong> Warehouse User ID of the logged in user, used to filter records to their own data. Only applied if ownData is 1.<br/>'.
            '<strong>ownData</strong> - Boolean (1 or 0) parameter which defines if the output should be filtered to the user\'s own records.';
      }
      elseif ($param['name']==='param_presets') 
        $param['default'] = "smpattrs=\noccattrs=\nlocation_id={profile_location}\ntaxon_groups={profile_taxon_groups}\ncurrentUser={profile_indicia_user_id}";
      elseif ($param['name']==='param_defaults') 
        $param['default'] = "idlist=\nsearchArea=";
      elseif ($param['name']==='standard_controls') 
        $param['default'] = "layerSwitcher\npanZoomBar";
    }
    return $r;
  }
  
  /**
   * Return the generated output.
   * @param array $args List of parameter values passed through to the form depending on how the form has been configured.
   * This array always contains a value for language.
   * @param object $node The Drupal node object.
   * @param array $response When this form is reloading after saving a submission, contains the response from the service call.
   * Note this does not apply when redirecting (in this case the details of the saved object are in the $_GET data).
   * @return Form HTML.
   */
  public static function get_form($args, $node, $response=null) {
    iform_load_helpers(array('report_helper','map_helper'));
    $readAuth = report_helper::get_read_auth($args['website_id'], $args['password']);
    $sharing='reporting';
    $reportOptions = array_merge(
      iform_report_get_report_options($args, $readAuth),
      array(
        'reportGroup'=>'explore',
        'rememberParamsReportGroup'=>'explore',
        'paramsOnly'=>true,
        'paramsInMapToolbar'=>true,
        'sharing'=>$sharing,
        'paramsFormButtonCaption'=>lang::get('Filter'),
        'rowId'=>'occurrence_id',
      )
    );    
    iform_report_apply_explore_user_own_preferences($reportOptions);
    $reportOptions['extraParams']['limit']=3000;
    $r = report_helper::report_grid($reportOptions);
   
    $r .= report_helper::report_map(array(
      'readAuth' => $readAuth,
      'dataSource'=>$args['report_name'],
      'extraParams'=>$reportOptions['extraParams'],
      'paramDefaults'=>$reportOptions['paramDefaults'],
      'autoParamsForm'=>false,
      'reportGroup'=>'explore',
      'rememberParamsReportGroup'=>'explore',
      'clickableLayersOutputMode'=>'report',
      'sharing'=>$sharing,
      'rowId'=>'occurrence_id',
      'ajax'=>TRUE
    ));
    $options = array_merge(
      iform_map_get_map_options($args, $readAuth),
      array(
        'featureIdField'=>'occurrence_id',
        'clickForSpatialRef'=>false,
        'reportGroup'=>'explore',
        'toolbarDiv'=>'top'
      )
    );
    $olOptions = iform_map_get_ol_options($args);
    $r .= map_helper::map_panel($options, $olOptions);
    $allowDownload = !isset($args['downloadOwnDataOnly']) || !$args['downloadOwnDataOnly'] 
      || (isset($reportOptions['extraParams']['ownData']) && $reportOptions['extraParams']['ownData']===1)
      || (isset($_POST['explore-ownData']) && $_POST['explore-ownData']==='1')
      || (!(isset($_POST['explore-ownData']) || $_POST['explore-ownData']==='0') 
            && isset($reportOptions['paramDefaults']['ownData']) && $reportOptions['paramDefaults']['ownData']===1);
    $reportOptions = array_merge(
        $reportOptions,
        array(
          'id'=>'explore-records',
          'paramsOnly'=>false,
          'autoParamsForm'=>false,
          'downloadLink'=>$allowDownload,
          'rowClass'=>'certainty{certainty}'
        )
    );
    if (isset($args['includeEditLink']) && $args['includeEditLink'] && !empty($args['includeEditLinkPath']))
      $reportOptions['columns'][] = array(
          'display'=>'Actions',
          'actions'=>array(
            array('caption'=>'edit','url'=>url($args['includeEditLinkPath']),'urlParams'=>array('occurrence_id'=>'{occurrence_id}'),'visibility_field'=>'belongs_to_user')
          )
      );
    $r .= report_helper::report_grid($reportOptions);
    return $r;

  }
}
