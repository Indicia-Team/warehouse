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
 * Model class for the Subject_observations table.
 *
 * @package	Groups and individuals module
 * @subpackage Models
 * @link	http://code.google.com/p/indicia/wiki/DataModel
 */
class Subject_Observation_Model extends ORM_Tree
{
  protected $ORM_Tree_children = 'subject_observations';
  
  public $search_field='comment';
  
  protected $belongs_to=array(
    'sample',
    'website',
    'known_subject',  // optional, null values may be a problem? May need an empty KS model?
    'subject_type'=>'termlists_term',
    'count_qualifier'=>'termlists_term',
    'created_by'=>'user',
    'updated_by'=>'user',
  );
  protected $has_many=array(
    'identifiers_subject_observations',
    'occurrences_subject_observations',
    'subject_observation_attribute_values',
  );
  protected $has_and_belongs_to_many = array( // this won't understand join table rows with deleted='t'
    'identifiers',
    'occurrences',
    'subject_observation_attributes',
  );
  // Declare that this model has child attributes, and the name of the node in the submission which contains them
  protected $has_attributes=true;
  // A public attribute does NOT need to be linked to a website to form part of the submissable data for a subject_observation (unlike, say,
  // sample attributes which are not submissable unless linked via a sample_attributes_websites record).
  public $include_public_attributes = true;  // TODO, do we want this?
  protected $attrs_submission_name='sjoAttributes';
  protected $attrs_field_prefix='sjoAttr';
  
  public function validate(Validation $array, $save = false) {
    // uses PHP trim() to remove whitespace from beginning and end of all fields before validation
    $array->pre_filter('trim');
    $array->add_rules('sample_id', 'required', 'digit');
    $array->add_rules('subject_type_id', 'required', 'digit');
    $array->add_rules('website_id', 'required', 'digit');
    // Explicitly add those fields for which we don't do validation
    $this->unvalidatedFields = array(
      'parent_id',
      'known_subject_id',
      'count',
      'count_qualifier_id',
      'comment',
      'deleted',
    );
    return parent::validate($array, $save);
  }

  /**
   * Returns an abbreviated version of the description to act as a caption
   */
  public function caption()
  {
    if ($this->id) {
      if (strlen($this->comment)>30) {
        return substr($this->comment, 0, 30).'...';
      } else {
        return $this->comment;
      }
    } else {
      return $this->getNewItemCaption();
    }
  }

  /**
  * Before submission, TODO perhaps?
  */
  protected function preSubmit()
  { 
    kohana::log('debug', 'In Subject_observation_Model::preSubmit() $this->submission is '.print_r($this->submission, true));
    // if sample_id not set in occurrence submissions, then set it now
    if (array_key_exists('subModels', $this->submission)) {
      foreach ($this->submission['subModels'] as &$subModel) {
        if (array_key_exists('model', $subModel)
          && array_key_exists('id', $subModel['model'])
          && $subModel['model']['id'] === 'occurrences_subject_observation'
          && array_key_exists('superModels', $subModel['model'])) {
          foreach ($subModel['model']['superModels'] as &$superModel) {
            if (array_key_exists('model', $superModel)
              && array_key_exists('id', $superModel['model'])
              && $superModel['model']['id'] === 'occurrence'
              && array_key_exists('fields', $superModel['model'])
              && array_key_exists('sample_id', $superModel['model']['fields'])
              && array_key_exists('value', $superModel['model']['fields']['sample_id'])
              && $superModel['model']['fields']['sample_id']['value'] == 0
              && array_key_exists('fields', $this->submission)
              && array_key_exists('sample_id', $this->submission['fields'])
              && array_key_exists('value', $this->submission['fields']['sample_id'])) {
              $superModel['model']['fields']['sample_id']['value'] = 
                $this->submission['fields']['sample_id']['value'];
            }
          }
        }
      }
    }
    return parent::presubmit();
  }
  
  /**
  * After submission, TODO perhaps?
  */
  protected function postSubmit($isInsert)
  { 
    kohana::log('debug', 'In Subject_observation_Model::postSubmit() $this->submission is '.print_r($this->submission, true));
    return parent::postSubmit($isInsert);
  }
  
  /**
   * Return the submission structure, which includes defining the occurrences table
   * is a sub-model.
   * 
   * @return array Submission structure for a subject_observation entry.
   */
  public function get_submission_structure() {
    $r = parent::get_submission_structure();
    $r['joinsTo'] = array('occurrences');
    return $r;
  } 
  
}
?>
