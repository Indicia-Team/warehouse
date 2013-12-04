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

require_once('includes/report.php');

/**
 * Prebuilt Indicia data form that lists the output of any report
 *
 * @package Client
 * @subpackage PrebuiltForms
 */
class iform_report_grid {

  /** 
   * Return the form metadata.
   * @return string The definition of the form.
   */
  public static function get_report_grid_definition() {
    return array(
      'title'=>'Report Grid',
      'category' => 'Reporting',
      'description'=>'Outputs a grid of data loaded from an Indicia report. Can automatically include the report parameters form required for the '.
          'generation of the report.',
      'helpLink' => 'http://code.google.com/p/indicia/wiki/PrebuiltFormReportGrid'
    );
  }
  
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {

    return array_merge(
      iform_report_get_report_parameters(),
      array(
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
              "img": {"type":"str","desc":"Set img to the path to an image to use an image for the action instead of a text caption - the caption '.
                  'then becomes the image\'s title. The image path can contain {rootFolder} to be replaced by the root folder of the site, in this '.
                  'case it excludes the path parameter used in Drupal when dirty URLs are used (since this is a direct link to a URL)."},
              "visibility_field": {"type":"str","desc":"Optional name of a field in the data which contains true or false to define the visibility of this action."},
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
          'Note that template columns cannot be sorted by clicking grid headers." },
        "json": {"type":"bool","desc":"Set to true if the column contains a json string object with properties that can be decoded to give strings that '.
          'can be used as replacements in a template."},
        "update": {
          "type":"map",
          "title":"Update Specification",
          "desc":"Defines the configuration to allow this field to update the database via AJAX. This assumes assume that we have access through iform_ajaxproxy.",
          "mapping": {
            "permission": {"type":"str","desc":"The CMS permission that the user must have in order for the field to be editable. If left blank then all users may update it."},
            "method": {"type":"str","desc":"Ajax proxy method, e.g. loc"},
            "tablename": {"type":"str","desc":"Submission table name: used to create the form field names from which the submission is built; e.g. location"},
            "fieldname": {"type":"str","desc":"Field name for this field in submission; e.g. code"},
            "website_id": {"type":"str","desc":"website_id"},
            "class": {"type":"str","desc":"Class name to apply to input control."},
            "parameters": {
              "type":"map",
              "subtype":"str",
              "desc":"List of parameters to copy from the report to the submission; with field value replacements such as {id} begin replaced '.
                  'by the value of the id field for the current row."
            }
          }
        }
      }
    }
  ]
}',
          'required' => false,
          'group'=>'Report Settings'
        ), array(
          'name' => 'gallery_col_count',
          'caption' => 'Gallery Column Count',
          'description' => 'If set to a value greater than one, then each grid row will contain more than one record of data from the database, allowing '.
              ' a gallery style view to be built.',
          'type' => 'int',
          'required' => false,
          'default' => 1,
          'group'=>'Report Settings'
        ),        
        array(
          'name' => 'download_link',
          'caption' => 'Download link',
          'description' => 'Should a link be made available to download the report content as CSV?',
          'type' => 'checkbox',
          'default' => 1,
          'required' => false,
          'group'=>'Report Settings'
        ), array(
          'name' => 'footer',
          'caption' => 'Footer',
          'description' => 'Additional HTML to include in the report footer area. If using this to create internal links, the replacement {rootFolder} can be used to give the path to the root of the site.',
          'type' => 'textarea',
          'required' => false,
          'group' => 'Report Settings'
        )
      )
    );
  }

  /**
   * Return the Indicia form code
   * @param array $args Input parameters.
   * @param array $node Drupal node object
   * @param array $response Response from Indicia services after posting a verification.
   * @return HTML string
   */
  public static function get_form($args, $node, $response) {
    iform_load_helpers(array('report_helper'));
    data_entry_helper::add_resource('jquery_form');
    $auth = report_helper::get_read_auth($args['website_id'], $args['password']);
    $reportOptions = iform_report_get_report_options($args, $auth);
    // get the grid output before outputting the download link, so we can check if the download link is needed.
    $reportOptions['id']='grid-'.$node->nid;
    if (isset($args['footer']))
      $reportOptions['footer'] = $args['footer'];
    $reportOptions['downloadLink'] = (!isset($args['download_link']) || $args['download_link']);
    $grid = report_helper::report_grid($reportOptions);
    return $grid;
  }

}