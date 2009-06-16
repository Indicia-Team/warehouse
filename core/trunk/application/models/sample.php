<?php
/**
 * INDICIA
 * @link http://code.google.com/p/indicia/
 * @package Indicia
 */

/**
 * Sample Model
 *
 *
 * @package Indicia
 * @subpackage Model
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @version $Rev$ / $LastChangedDate$
 */

class Sample_Model extends ORM
{
  protected $has_many=array('occurrences');
  protected $belongs_to=array
  (
  'survey',
  'location',
  'created_by'=>'user',
  'updated_by'=>'user',
  'sample_method'=>'termlists_term'
  );
  protected $search_field = 'id';

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
    kohana::log('info', $array['date_start']);
    kohana::log('info', 'Array dumped');

    // uses PHP trim() to remove whitespace from beginning and end of all fields before validation
    $array->pre_filter('trim');
    $array->add_rules('date_type', 'required', 'length[1,2]');
    if (array_key_exists('entered_sref_system', $orig_values)) {
      $system = $orig_values['entered_sref_system'];
      $array->add_rules('entered_sref', "sref[$system]");
      $array->add_rules('entered_sref_system', 'sref_system');
    } else {
      $array->add_rules('entered_sref', "required");
      $array->add_rules('entered_sref_system', 'required');
    }
    $array->add_rules('geom', 'required');

    // Any fields that don't have a validation rule need to be copied into the model manually
    $extraFields = array
    (
      'date_start',
      'date_end',
      'location_name',
      'survey_id',
      'deleted',
      'recorder_names'
     );
     foreach ($extraFields as $a)
     {
       if (array_key_exists($a, $array->as_array()))
       {
         $this->__set($a, $array[$a]);
       }
     }

     return parent::validate($array, $save);
  }

  /**
  * Before submission, map vague dates to their underlying database fields.
  */
  protected function preSubmit()
  {

      $vague_date=vague_date::string_to_vague_date($this->submission['fields']['date']['value']);
      $this->submission['fields']['date_start']['value'] = $vague_date['start'];
      $this->submission['fields']['date_end']['value'] = $vague_date['end'];
      $this->submission['fields']['date_type']['value'] = $vague_date['type'];

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

    if  (substr($column,-4) == 'geom')
    {
      $row = $this->db->query("SELECT ST_asText('$value') AS wkt")->current();
      $value = $row->wkt;
    }
    return $value;
  }


  /**
  * Overrides the postSubmit() function to provide support for adding sample attributes
  * within the transaction.
  */
  protected function postSubmit() {
 // Occurrences have sample attributes associated, stored in a
 // metafield.
 if (array_key_exists('metaFields', $this->submission) &&
   array_key_exists('smpAttributes', $this->submission['metaFields']))
   {
     Kohana::log("info", "About to submit sample attributes.");
     foreach ($this->submission['metaFields']['smpAttributes']['value'] as
       $idx => $attr)
     {
       $value = $attr['fields']['value'];
       if ($value['value'] != '') {
   $attrId = $attr['fields']['sample_attribute_id']['value'];
   $oa = ORM::factory('sample_attribute', $attrId);
   $vf = null;
   switch ($oa->data_type) {
     case 'T':
       $vf = 'text_value';
       break;
     case 'F':
       $vf = 'float_value';
       break;
     case 'D':
       // Date
       $vd=vague_date::string_to_vague_date($value['value']);
       $attr['fields']['date_start_value']['value'] = $vd['start'];
       $attr['fields']['date_end_value']['value'] = $vd['end'];
       $attr['fields']['date_type_value']['value'] = $vd['type'];
       break;
     case 'V':
       // Vague Date
       $vd=vague_date::string_to_vague_date($value['value']);
       $attr['fields']['date_start_value']['value'] = $vd['start'];
       $attr['fields']['date_end_value']['value'] = $vd['end'];
       $attr['fields']['date_type_value']['value'] = $vd['type'];
       break;
     default:
       // Lookup in list
       $vf = 'int_value';
       break;
   }

   if ($vf != null) $attr['fields'][$vf] = $value;
   $attr['fields']['sample_id']['value'] = $this->id;

   $oam = ORM::factory('sample_attribute_value');
   $oam->submission = $attr;
   if (!$oam->inner_submit()) {
     $this->db->query('ROLLBACK');
     return null;
       }
     }
   }
   return true;
   } else {
     return true;
   }
}
}
?>
