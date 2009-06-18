<?php
/**
* INDICIA
* @link http://code.google.com/p/indicia/
* @package Indicia
*/

/**
* Occurrence Model
*
*
* @package Indicia
* @subpackage Model
* @license http://www.gnu.org/licenses/gpl.html GPL
* @version $Rev$ / $LastChangedDate$
*/
class Occurrence_Model extends ORM
{
  protected $has_many=array(
    'occurrence_attribute_values'
  );
  protected $belongs_to=array(
    'determiner'=>'person',
    'sample',
    'taxa_taxon_list',
    'created_by'=>'user',
    'updated_by'=>'user',
    'verified_by'=>'user'
  );
  // Declare that this model has child attributes, and the name of the node in the submission which contains them
  protected $has_attributes=true;
  protected $attrs_submission_name='occAttributes';
  protected $attrs_field_prefix='occAttr';

  public function caption()
  {
    return $this->id;
  }

  public function validate(Validation $array, $save = false) {
    $array->pre_filter('trim');
    $array->add_rules('sample_id', 'required');
    $array->add_rules('website_id', 'required');
    $array->add_rules('taxa_taxon_list_id', 'required');
    // Explicitly add those fields for which we don't do validation
    $extraFields = array(
      'comment',
      'determiner_id',
      'deleted',
      'record_status',
      'verified_by_id',
      'verified_on',
      'confidential'
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

 // Overrides preSubmit to add in verifier status
 protected function preSubmit()
 {
   if (array_key_exists('record_status', $this->submission['fields']))
   {
     $rs = $this->submission['fields']['record_status']['value'];
     if ($rs == 'V' && !$this->verified_by_id)
     {
       $defaultUserId = Kohana::config('indicia.defaultPersonId');
       $this->submission['fields']['verified_by_id']['value'] = $_SESSION['auth_user'] || $defaultUserId;
       $this->submission['fields']['verified_on']['value'] = date("Ymd H:i:s");
     }
   }
   parent::preSubmit();
 }

}
?>
