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
 * @package	Core
 * @subpackage Models
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Model class for the Samples table.
 *
 * @package	Core
 * @subpackage Models
 * @link	http://code.google.com/p/indicia/wiki/DataModel
 */
class Sample_Model extends ORM
{
  protected $has_many=array('occurrences', 'sample_attribute_values');
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

  /**
  * Validate and save the data.
  *
  * @todo add a validation rule for valid date types.
  * @todo allow a date string to be passed, which gets mapped to a vague date start, end and type.
  * @todo validate at least a location_name or sref required
  */
  public function validate(Validation $array, $save = FALSE)
  {
    $orig_values = $array->as_array();
    // uses PHP trim() to remove whitespace from beginning and end of all fields before validation
    $array->pre_filter('trim');
    $array->add_rules('date_type', 'required', 'length[1,2]');
    $array->add_rules('date_start', 'date_in_past');
    $array->add_rules('entered_sref', "required");
    $array->add_rules('entered_sref_system', 'required');
    if (array_key_exists('entered_sref_system', $orig_values)) {
      $system = $orig_values['entered_sref_system'];
      $array->add_rules('entered_sref', "sref[$system]");
      $array->add_rules('entered_sref_system', 'sref_system');
    }
    $array->add_rules('geom', 'required');

    // Any fields that don't have a validation rule need to be copied into the model manually
    $this->unvalidatedFields = array
    (
      'date_start',
      'date_end',
      'location_name',
      'survey_id',
      'deleted',
      'recorder_names'
    );
    return parent::validate($array, $save);
  }

  /**
  * Before submission, map vague dates to their underlying database fields.
  */
  protected function preSubmit()
  {    kohana::log('debug','presubmit');
    if (array_key_exists('date', $this->submission['fields'])) {
      $vague_date=vague_date::string_to_vague_date($this->submission['fields']['date']['value']);
      $this->submission['fields']['date_start']['value'] = $vague_date['start'];
      $this->submission['fields']['date_end']['value'] = $vague_date['end'];
      $this->submission['fields']['date_type']['value'] = $vague_date['type'];
      kohana::log('debug','here');
    }
    // Allow a sample to be submitted with a spatial ref and system but no Geom. If so we
    // can work out the Geom
    if (array_key_exists('entered_sref', $this->submission['fields']) &&
        array_key_exists('entered_sref_system', $this->submission['fields']) &&
        !array_key_exists('geom', $this->submission['fields']) &&
        $this->submission['fields']['entered_sref']['value'] &&
        $this->submission['fields']['entered_sref_system']['value']) {
      $this->submission['fields']['geom']['value'] = spatial_ref::sref_to_internal_wkt(
          $this->submission['fields']['entered_sref']['value'],
          $this->submission['fields']['entered_sref_system']['value']
      );
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

}
?>
