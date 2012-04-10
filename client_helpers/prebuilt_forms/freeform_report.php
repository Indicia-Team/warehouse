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
 * A prebuilt form which wraps the freeform_report control, allowing report output to be displayed as a flexible
 * freeform banded output.
 * 
 * @package Client
 * @subpackage PrebuiltForms
 * @todo Rename the form class to iform_...
 */
class iform_freeform_report {
  
  /** 
   * Return the form metadata. Note the title of this method includes the name of the form file. This ensures
   * that if inheritance is used in the forms, subclassed forms don't return their parent's form definition.
   * @return array The definition of the form.
   */
  public static function get_freeform_report_definition() {
    return array(
      'title'=>'Freeform report',
      'category' => 'Reporting',
      //'helpLink'=>'<optional help URL>',
      'description'=>'Report which allows output to be displayed as a flexible freeform banded output.'
    );
  }
  
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   * @todo: Implement this method
   */
  public static function get_parameters() {   
    return array_merge(
      iform_report_get_report_parameters(),
      array(
        array(
          'name'=>'header',
          'caption'=>'Header',
          'description'=>'HTML to insert into the header of the report output.',
          'type'=>'textarea',
          'required'=>false,
          'group'=>'Output templates'
        ),
        array(
          'name'=>'footer',
          'caption'=>'Footer',
          'description'=>'HTML to insert into the footer of the report output.',
          'type'=>'textarea',
          'required'=>false,
          'group'=>'Output templates'
        ), 
        array(
          'name'=>'bands',
          'caption'=>'Report Bands',
          'description'=>'A list of bands which are output once per report row in the order provided. '.
              'Bands may also be configured to output as a header band, only when the value of a certain field changes between rows. '.
              'For example if a report is sorted by site name, then a band can be emmitted only when the site name value changes and can display '.
              'the new site name as a header.',
          'group'=>'Output templates',
          'required'=>false,
          'type'=>'jsonwidget',
          'schema'=>'{
  "type":"seq",
  "title":"Bands",
  "sequence":
  [
    {
      "type":"map",
      "title":"Band",
      "mapping": {
        "content":{"type":"str","desc":"Contains an HTML template for the output of the band. The '.
            'template can contain replacements for each field value in the row, e.g. the '.
            'replacement {survey} is replaced with the value of the field called survey. The actual replacements '.
            'available depends on the selected report\'s output fields."},
        "triggerFields":{"type":"seq","sequence":[{"type":"str"}]}
      }
    }
  ]
}'
        )
      )
    );
  }
  
  /**
   * Return the generated form output.
   * @param array $args List of parameter values passed through to the form depending on how the form has been configured.
   * This array always contains a value for language.
   * @param object $node The Drupal node object.
   * @param array $response When this form is reloading after saving a submission, contains the response from the service call.
   * Note this does not apply when redirecting (in this case the details of the saved object are in the $_GET data).
   * @return Form HTML.
   * @todo: Implement this method 
   */
  public static function get_form($args, $node, $response=null) {
    iform_load_helpers(array('report_helper'));
    $auth = report_helper::get_read_auth($args['website_id'], $args['password']);
    $reportOptions = iform_report_get_report_options($args, $auth);
    $reportOptions['header'] = $args['header'];
    $reportOptions['footer'] = $args['footer'];
    $reportOptions['bands'] = json_decode($args['bands'], true);
    return report_helper::freeform_report($reportOptions);
  }
  
}
