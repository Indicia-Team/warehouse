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

require_once('includes/form_generation.php');
require_once('includes/map.php');

/**
 * Prebuilt Indicia data entry form that presents taxon search box, date control, map picker,
 * survey selector and comment entry controls.
 * 
 * @package	Client
 * @subpackage PrebuiltForms
 */
class iform_record_details {

  /**
   * Return the form metadata.
   * @return string The definition of the form.
   */
  public static function get_record_details_definition() {
    return array(
      'title'=>'View details of a record',
      'category' => 'Utilities',
      'description'=>'A summary view of a record. Pass a parameter in the URL called occurrence_id to '.
          'define which occurrence to show.'
    );
  }
  
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {   
    return array_merge(
      iform_map_get_map_parameters(),
      array(
        array(
          'name' => 'hide_fields',
          'caption' => 'Fields to Hide',
          'description' => 'List of data fields to hide, one per line. ',
          'type' => 'textarea',
          'default' => "CMS User ID\nEmail\nFirst name\nLast name\nSurname"
        )
      )
    );
  }
  
  /**
   * Return the generated form output.
   * @return Form HTML.
   */
  public static function get_form($args) {
    iform_load_helpers(array('map_helper'));
    $hidden = str_replace("\r\n", "\n", $args['hide_fields']);
    $hidden = explode("\n", $hidden);
    if (empty($_GET['occurrence_id'])) {
      return 'This form requires an occurrence_id parameter in the URL.';
    }
    // Get authorisation tokens to update and read from the Warehouse.
    $auth = data_entry_helper::get_read_auth($args['website_id'], $args['password']);
    data_entry_helper::load_existing_record($auth, 'occurrence', $_GET['occurrence_id']);
    data_entry_helper::load_existing_record($auth, 'sample', data_entry_helper::$entity_to_load['occurrence:sample_id']);
    $r .= "<div id=\"controls\">\n";
    $r .= "<table>\n";
    if (!in_array('Species', $hidden))
      $r .= "<tr><td><strong>".lang::get('Species')."</strong></td><td>".data_entry_helper::$entity_to_load['occurrence:taxon']."</td></tr>\n";
    if (!in_array('Date', $hidden))
      $r .= "<tr><td><strong>Date</strong></td><td>".data_entry_helper::$entity_to_load['sample:date']."</td></tr>\n";
    if (!in_array('Grid Reference', $hidden))
    $r .= "<tr><td><strong>Grid Reference</strong></td><td>".data_entry_helper::$entity_to_load['sample:entered_sref']."</td></tr>\n";
    $siteLabels = array();
    if (!empty(data_entry_helper::$entity_to_load['sample:location'])) $siteLabels[] = data_entry_helper::$entity_to_load['sample:location'];
    if (!empty(data_entry_helper::$entity_to_load['sample:location_name'])) $siteLabels[] = data_entry_helper::$entity_to_load['sample:location_name'];
    if (!in_array('Site', $hidden) && !empty($siteLabels))
      $r .= "<tr><td><strong>Site</strong></td><td>".implode(' | ', $siteLabels)."</td></tr>\n";
    $smpAttrs = data_entry_helper::getAttributes(array(
        'id' => data_entry_helper::$entity_to_load['sample:id'],
        'valuetable'=>'sample_attribute_value',
        'attrtable'=>'sample_attribute',
        'key'=>'sample_id',
        'extraParams'=>$auth,
        'survey_id'=>data_entry_helper::$entity_to_load['occurrence:survey_id']
    ));
    $occAttrs = data_entry_helper::getAttributes(array(
        'id' => $_GET['occurrence_id'],
        'valuetable'=>'occurrence_attribute_value',
        'attrtable'=>'occurrence_attribute',
        'key'=>'occurrence_id',
        'extraParams'=>$auth,
        'survey_id'=>data_entry_helper::$entity_to_load['occurrence:survey_id']
    ));
    $attributes = array_merge($smpAttrs, $occAttrs);
    foreach($attributes as $attr) {
      if (!in_array($attr['caption'], $hidden))
        $r .= "<tr><td><strong>".lang::get($attr['caption'])."</strong></td><td>".$attr['displayValue']."</td></tr>\n";
    }
    $r .= "</table>\n";
    $r .= "</div>\n";
    $options = iform_map_get_map_options($args, $readAuth);
    $olOptions = iform_map_get_ol_options($args);
    $options['initialFeatureWkt'] = data_entry_helper::$entity_to_load['occurrence:wkt'];
    $r .= map_helper::map_panel($options, $olOptions);
        
    return $r;
  }  
  
}