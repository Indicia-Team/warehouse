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
 * Model class for the Known_subjects table.
 *
 * @package	Groups and individuals module
 * @subpackage Models
 * @link	http://code.google.com/p/indicia/wiki/DataModel
 */
class Known_subject_Model extends ORM_Tree
{
  protected $ORM_Tree_children = 'known_subjects';
  
  public $search_field='description';

  protected $belongs_to = array(
    'subject_type'=>'termlists_term',
    'website',
    'created_by'=>'user',
    'updated_by'=>'user',
  );

  protected $has_many = array(
    'identifiers',
    'subject_observations',
    'known_subject_comments',
    'known_subjects_taxa_taxon_lists',
    'known_subject_attribute_values',
  );
    
  protected $has_and_belongs_to_many = array(
    'taxa_taxon_lists',
    'known_subject_attributes',
  );
    
  // Declare that this model has child attributes, and the name of the node in the submission which contains them
  protected $has_attributes=true;
  // A public attribute does NOT need to be linked to a website to form part of the submissable data for a known_subject (unlike, say,
  // sample attributes which are not submissable unless linked via a sample_attributes_websites record).
  public $include_public_attributes = true;
  protected $attrs_submission_name='ksjAttributes';
  protected $attrs_field_prefix='ksjAttr';
  
  public function validate(Validation $array, $save = false) {
    // uses PHP trim() to remove whitespace from beginning and end of all fields before validation
    $array->pre_filter('trim');
    $array->add_rules('subject_type_id', 'required', 'digit');
    $array->add_rules('website_id', 'required', 'digit');
    // Explicitly add those fields for which we don't do validation
    $this->unvalidatedFields = array(
      'parent_id', 
      'description',
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
      if (strlen($this->description)>30) {
        return substr($this->description, 0, 30).'...';
      } else {
        return $this->description;
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
    kohana::log('debug', 'In Known_subject_Model::preSubmit() $this->submission is '.print_r($this->submission, true));
    return parent::presubmit();
  }
  
  /**
  * After submission, TODO perhaps?
  */
  protected function postSubmit($isInsert)
  { 
    kohana::log('debug', 'In Known_subject_Model::postSubmit() $this->submission is '.print_r($this->submission, true));
    return parent::postSubmit($isInsert);
  }
  
  /**
   * Return the submission structure, which includes defining the taxa_taxon_lists table
   * is a sub-model.
   * 
   * @return array Submission structure for a known_subject entry.
   */
  public function get_submission_structure() {
    $r = parent::get_submission_structure();
    $r['joinsTo'] = array('taxa_taxon_lists');
    return $r;
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
