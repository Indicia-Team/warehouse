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
 * Model class for the Occurrences table.
 *
 * @package	Core
 * @subpackage Models
 * @link	http://code.google.com/p/indicia/wiki/DataModel
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
    return parent::validate($array, $save, $extraFields);
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
