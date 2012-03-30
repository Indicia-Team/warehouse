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
 * @package  Core
 * @subpackage Models
 * @author  Indicia Team
 * @license  http://www.gnu.org/licenses/gpl.html GPL
 * @link   http://code.google.com/p/indicia/
 */

/**
 * Model class for the Samples table.
 *
 * @package  Core
 * @subpackage Models
 * @link  http://code.google.com/p/indicia/wiki/DataModel
 */
class Sample_Model extends ORM_Tree
{
  protected $ORM_Tree_children = "samples";
  protected $has_many=array('occurrences', 'sample_attribute_values', 'sample_images');
  protected $belongs_to=array
  (
    'survey',
    'location',
    'created_by'=>'user',
    'updated_by'=>'user',
    'sample_method'=>'termlists_term'
  );
  protected $search_field = 'id';
  // Declare that this model has child attributes, and the name of the node in the submission which contains them
  protected $has_attributes=true;
  protected $attrs_submission_name='smpAttributes';
  protected $attrs_field_prefix='smpAttr';
  
  // Declare additional fields required when posting via CSV.
  protected $additional_csv_fields=array(
    'survey_id' => 'Survey ID',
    'website_id' => 'Website ID'
  );
  // define underlying fields which the user would not normally see, e.g. so they can be hidden from selection
  // during a csv import
  protected $hidden_fields=array(
    'geom'
  );

  /**
  * Validate and save the data.
  *
  * @todo add a validation rule for valid date types.
  * @todo validate at least a location_name or sref required
  */
  public function validate(Validation $array, $save = FALSE)
  {
    $orig_values = $array->as_array();
    // uses PHP trim() to remove whitespace from beginning and end of all fields before validation
    $array->pre_filter('trim');
 
    // Any fields that don't have a validation rule need to be copied into the model manually
    $this->unvalidatedFields = array
    (
      'date_end',
      'location_name',
      'survey_id',
      'deleted',
      'recorder_names',
      'parent_id',
      'comment'
    );
    
    $array->add_rules('date_type', 'required', 'length[1,2]');
    $array->add_rules('survey_id', 'required');
    $array->add_rules('date_start', 'date_in_past');
    // We need either at least one of the location_id and sref/geom : in some cases may have both
    if (array_key_exists('location_id', $orig_values) && $orig_values['location_id']!=='' && $orig_values['location_id']!== null) { // if a location is provided, we don't need an sref.
      $array->add_rules('location_id', 'required');
      // if any of the sref fields are also supplied, need all 3 fields
      if ((array_key_exists('entered_sref', $orig_values) && $orig_values['entered_sref']!=='' && $orig_values['entered_sref']!== null) ||
          (array_key_exists('entered_sref_system', $orig_values) && $orig_values['entered_sref_system']!=='' && $orig_values['entered_sref_system']!== null) ||
          (array_key_exists('geom', $orig_values)) && $orig_values['geom']!==''  && $orig_values['geom']!== null) {
        $array->add_rules('entered_sref', "required");
        $array->add_rules('entered_sref_system', 'required');
        $array->add_rules('geom', 'required');
        if (array_key_exists('entered_sref_system', $orig_values) && $orig_values['entered_sref_system']!=='') {
          $system = $orig_values['entered_sref_system'];
          $array->add_rules('entered_sref', "sref[$system]");
          $array->add_rules('entered_sref_system', 'sref_system');
        }
      }
    } else {
      // without a location_id, default to requires an sref.
      // no need to copy over location_id, as not present.
      $array->add_rules('entered_sref', "required");
      $array->add_rules('entered_sref_system', 'required');
      $array->add_rules('geom', 'required');
      if (array_key_exists('entered_sref_system', $orig_values) && $orig_values['entered_sref_system']!=='') {
        $system = $orig_values['entered_sref_system'];
        $array->add_rules('entered_sref', "sref[$system]");
        $array->add_rules('entered_sref_system', 'sref_system');
      }    
    }

    return parent::validate($array, $save);
  }

  /**
  * Before submission, map vague dates to their underlying database fields.
  */
  protected function preSubmit()
  { 
    if (array_key_exists('date', $this->submission['fields'])) {
      $vague_date=vague_date::string_to_vague_date($this->submission['fields']['date']['value']);
      $this->submission['fields']['date_start']['value'] = $vague_date['start'];
      $this->submission['fields']['date_end']['value'] = $vague_date['end'];
      $this->submission['fields']['date_type']['value'] = $vague_date['type'];      
    }
    // Allow a sample to be submitted with a spatial ref and system but no Geom. If so we
    // can work out the Geom    
    if (array_key_exists('entered_sref', $this->submission['fields']) &&
        array_key_exists('entered_sref_system', $this->submission['fields']) &&
        !(array_key_exists('geom', $this->submission['fields']) && $this->submission['fields']['geom']['value']) &&
        $this->submission['fields']['entered_sref']['value'] &&
        $this->submission['fields']['entered_sref_system']['value']) {                    
      try {
        $this->submission['fields']['geom']['value'] = spatial_ref::sref_to_internal_wkt(
            $this->submission['fields']['entered_sref']['value'],
            $this->submission['fields']['entered_sref_system']['value']
        );
      } catch (Exception $e) {
        $this->errors['entered_sref'] = $e->getMessage();
      }
    }
    return parent::presubmit();
  }

  /**
  * Override set handler to translate WKT to PostGIS internal spatial data.
  */
  public function __set($key, $value)
  {
    if (substr($key,-4) == 'geom')
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

    if  (substr($column,-4) == 'geom' && $value!==null)
    {
      $row = $this->db->query("SELECT ST_asText('$value') AS wkt")->current();
      $value = $row->wkt;
    }
    return $value;
  }
  
    /**
   * Return a displayable caption for the item.
   * For samples this is a combination of the date and spatial reference.
   */
  public function caption()
  {
    return ('Sample on '.$this->date.' at '.$this->entered_sref);
  }

}
?>
