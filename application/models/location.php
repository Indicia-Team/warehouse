<?php defined('SYSPATH') or die('No direct script access.');

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
 * @package	Core
 * @subpackage Models
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Model class for the Locations table.
 *
 * @package	Core
 * @subpackage Models
 * @link	http://code.google.com/p/indicia/wiki/DataModel
 */
class Location_Model extends ORM_Tree {

  public static $search_field='name';

  protected $ORM_Tree_children = "locations";
  protected $has_and_belongs_to_many = array('websites');
  protected $has_many = array('samples', 'location_attribute_values', 'location_images');
  protected $belongs_to = array('created_by'=>'user', 'updated_by'=>'user');

  // Declare that this model has child attributes, and the name of the node in the submission which contains them
  protected $has_attributes=true;
  protected $attrs_submission_name='locAttributes';
  protected $attrs_field_prefix='locAttr';

  public function validate(Validation $array, $save = FALSE) {
    $orig_values = $array->as_array();

    // uses PHP trim() to remove whitespace from beginning and end of all fields before validation
    $array->pre_filter('trim');
    $array->add_rules('name', 'required');
    if (isset($orig_values['centroid_sref_system'])) {
      $system = $orig_values['centroid_sref_system'];
      $array->add_rules('centroid_sref', 'required', "sref[$system]");
    } else {
      $array->add_rules('centroid_sref', 'required');
    }
    $array->add_rules('centroid_sref_system', 'required', 'sref_system');

    // Explicitly add those fields for which we don't do validation
    $this->unvalidatedFields = array(
      'code',
      'parent_id',
      'deleted',
      'centroid_geom',
      'boundary_geom',
      'location_type_id',
      'comment'
    );
    return parent::validate($array, $save);
  }

  /**
   * Override set handler to translate WKT to PostGIS internal spatial data.
   */
  public function __set($key, $value)
  {
    if (substr($key,-5) == '_geom')
    {
      if ($value) {
        $row = $this->db->query("SELECT ST_GeomFromText('$value', ".kohana::config('sref_notations.internal_srid').") AS geom")->current();
        $value = $row->geom;
      }
    }
    parent::__set($key, $value);
  }

  /**
   * Override get handler to translate PostGIS internal spatial data to WKT.
   */
  public function __get($column)
  {
    $value = parent::__get($column);

    if  (substr($column,-5) == '_geom' && $value !== null) {
      $row = $this->db->query("SELECT ST_asText('$value') AS wkt")->current();
      $value = $row->wkt;
    }
    return $value;
  }

  /**
   * Return the submission structure, which includes defining the locations_websites table
   * is a sub-model.
   * 
   * @return array Submission structure for a location entry.
   */
  public function get_submission_structure() {
    return array(
      'model' => 'location',
      'joinsTo' => array('websites')        
    );
  } 

}
