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
 * Model class for the Identifiers table.
 *
 * @package	Individuals and associations module
 * @subpackage Models
 * @link	http://code.google.com/p/indicia/wiki/DataModel
 */
class Identifier_Model extends ORM
{
  public $search_field='coded_value';

  protected $belongs_to = array(
    'issue_authority'=>'termlists_term',
    'issue_scheme'=>'termlists_term',
    'identifier_type'=>'termlists_term',
    'known_subject',
    'website',
    'created_by'=>'user',
    'updated_by'=>'user',
  );

  protected $has_many = array(
    'identifier_attribute_values',
    'identifiers_subject_observations',
  );
    
  protected $has_and_belongs_to_many = array(
    'identifier_attributes',
    'subject_observations',
  );
    
  // Declare that this model has child attributes, and the name of the node in the submission which contains them
  protected $has_attributes=true;
  // A public attribute does NOT need to be linked to a website to form part of the submissable data for a identifier (unlike, say,
  // sample attributes which are not submissable unless linked via a sample_attributes_websites record).
  public $include_public_attributes = true;
  protected $attrs_submission_name='idnAttributes';
  protected $attrs_field_prefix='idnAttr';
  
  public function validate(Validation $array, $save = false) {
    // uses PHP trim() to remove whitespace from beginning and end of all fields before validation
    $array->pre_filter('trim');
    $array->add_rules('identifier_type_id', 'required', 'digit');
    $array->add_rules('website_id', 'required', 'digit');
    // Explicitly add those fields for which we don't do validation
    $this->unvalidatedFields = array(
      'issue_authority_id', 
      'issue_scheme_id', 
      'issue_date', 
      'first_use_date', 
      'last_observed_date', 
      'final_date', 
      'coded_value',
      'summary',
      'known_subject_id',
      'deleted',
    );
    return parent::validate($array, $save);
  }

  /**
   * Returns an abbreviated version of the summary to act as a caption. Todo, consider if 'coded_value' would be better?
   */
  public function caption()
  {
    if ($this->id) {
      if (strlen($this->summary)>30) {
        return substr($this->summary, 0, 30).'...';
      } else {
        return $this->summary;
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
    kohana::log('debug', 'In Identifier_Model::preSubmit() $this->submission is '.print_r($this->submission, true));
    return parent::presubmit();
  }
  
  /**
  * After submission, TODO perhaps?
  */
  protected function postSubmit($isInsert)
  { 
    kohana::log('debug', 'In Identifier_Model::postSubmit() $this->submission is '.print_r($this->submission, true));
    return parent::postSubmit();
  }

  /** 
   * Gets the list of custom attributes for this model.
   * @param boolean $required Optional. Set to true to only return required attributes (requires 
   * the website and survey identifier to be set).
   * @param int @typeFilter Specify a location type meaning id or a sample method meaning id to
   * filter the returned attributes to those which apply to the given type or method.
   * @param boolean @hasSurveyRestriction true if this objects attributes can be restricted to 
   * survey scope.
   */
  protected function getAttributes($required = false, $typeFilter = null, $hasSurveyRestriction = true) {
    return parent::getAttributes($required, $typeFilter, false);
  }
}
