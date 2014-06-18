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
 * @package    Client
 * @subpackage PrebuiltForms
 * @author    Indicia Team
 * @license    http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link     http://code.google.com/p/indicia/
 */

/**
 * Prebuilt Indicia data entry form.
 * NB has Drupal specific code.
 * 
 * @package    Client
 * @subpackage PrebuiltForms
 */

require_once('dynamic_location.php');

class iform_dynamic_location_splash_squares extends iform_dynamic_location {

  /** 
   * Return the form metadata.
   * @return array The definition of the form.
   */
  public static function get_dynamic_location_splash_squares_definition() {
    return array(
      'title'=>'Location entry form - Splash squares',
      'category' => 'Miscellaneous',
      'description'=>'A data entry form for adding and editing Splash location squares.' 
    );
  }
  
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {
    $retVal = array_merge(
      parent::get_parameters(),
      array(
        //As the attribute ids will vary between different databases, we need to manually
        //map the attribute ids to variables in the code
        array(
          'name'=>'user_squares_person_attr_id',
          'caption'=>'User Squares Person Attribute Id',
          'description'=>'Indicia ID for the person attribute that holds a user\'s squares.',
          'type'=>'select',
          'table'=>'person_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Splash Squares Page Setup'
        ),
        array(
          'name'=>'core_square_id',
          'caption'=>'Core Square Location Type Id',
          'description'=>'Indicia ID for the core square location type.',
          'type'=>'select',
          'table'=>'termlists_term',
          'valueField'=>'id',
          'captionField'=>'term',
          'group'=>'Splash Squares Page Setup'
        ),
        array(
          'name'=>'square_vice_county_location_attr_id',
          'caption'=>'Vice County Name Location Attrbute Id',
          'description'=>'Indicia ID for the location attribute that holds the names of vice counties for the square.',
          'type'=>'select',
          'table'=>'location_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Splash Squares Page Setup'
        ),
        array(
          'name'=>'vice_county_location_type_id',
          'caption'=>'Vice County Location Type Id',
          'description'=>'Indicia ID for the vice county location type.',
          'type'=>'select',
          'table'=>'termlists_term',
          'valueField'=>'id',
          'captionField'=>'term',
          'group'=>'Splash Squares Page Setup'
        ),
        array(
          'name'=>'show_grid_system_selector',
          'caption'=>'Show selection drop-down for grid system?',
          'description'=>'Allow the user to select the grid system? Useful if you want to support more than one system such as OSGB and OSIE.',
          'type'=>'boolean',
          'default'=>false,
          'group'=>'Splash Squares Page Setup'
        ),
      )
    );
    return $retVal;
  }
  
  /**
   * Return the generated form output.
   * @return Form HTML.
   */
  public static function get_form($args, $node) {
    $r = parent::get_form($args, $node);
    //The system page configuration includes a setting to set the default spatial reference system to British National Grid, but
    //we also need to hide the field so the user cannot change it.
    if ($args['show_grid_system_selector']==false)
      data_entry_helper::$javascript .= "$('#imp-sref-system').hide();";
    return $r;
  }
  
  /**
   * Handles the construction of a submission array from a set of form values.
   * This is different to a standard location submission because when we submit a new additional square, we find any vice counties the
   * square intersects with, and then we save them to a custom attribute ready for use in the square's name. We use a custom attribute as
   * it is faster than doing the intersection in real-time, particularly in report grids.
   * 
   * @param array $values Associative array of form data values. 
   * @param array $args iform parameters. 
   * @return array Submission structure.
   */
  public static function get_submission($values, $args) {
    $s = parent::get_submission($values, $args);
    //We only want to find vice counties for additional squares and when in add mode.
    if (!empty($_GET['location_type_id']) && $_GET['location_type_id']!=$args['core_square_id']) {
      $readAuth = data_entry_helper::get_read_auth($args['website_id'], $args['password']);
      $userData = data_entry_helper::get_population_data(array(
        'table' => 'user',
        'extraParams' => $readAuth + array('id' =>hostsite_get_user_field('indicia_user_id'))
      ));     

      $s['subModels'][] = array(
          'fkId' => 'int_value', 
          'model' => array(
            'id' => 'person_attribute_value',
            'fields' => array(
              'person_id' => $userData[0]['person_id'],
              'person_attribute_id' => $args['user_squares_person_attr_id']
          )
        )
      );
      //Use a report to collect the names of the vice counties that intersect the square.
      $readAuth = data_entry_helper::get_read_auth($args['website_id'], $args['password']);
      $reportOptions = array(
        'dataSource'=>'reports_for_prebuilt_forms/Splash/get_vice_county_names_for_grid_ref',
        'readAuth'=>$readAuth,
        'extraParams' => array('website_id'=>$args['website_id'],
            'vice_county_location_type_id'=>$args['vice_county_location_type_id'],
            'square_grid_ref'=>$s['fields']['geom']['value']),
        'valueField'=>'id',
        'captionField'=>'name'
      );
      $squareViceCountyData = data_entry_helper::get_report_data($reportOptions);
      //Build up a comma seperate list of vice counties if the square interects more than one.
      $squareViceCountyDataForDatabase = ''; 
      foreach ($squareViceCountyData as $squareViceCounty) {
        if (isset($squareViceCounty['name']))
          $squareViceCountyDataForDatabase .= $squareViceCounty['name'].', ';
      }
      //Chop off the last comma and space
      if (!empty($squareViceCountyDataForDatabase))
        $squareViceCountyDataForDatabase = substr($squareViceCountyDataForDatabase, 0, -2);
      //Save the custom attribute which holds the vice counties.
      if (!empty($squareViceCountyData[0]['name'])) {
        $s['subModels'][] = array(
            'fkId' => 'location_id', 
            'model' => array(
              'id' => 'location_attribute_value',
              'fields' => array(
                'text_value'=>$squareViceCountyDataForDatabase,
                'location_attribute_id' => $args['square_vice_county_location_attr_id']
            )
          )
        );
      }   
    }
    return $s;
  }
}

