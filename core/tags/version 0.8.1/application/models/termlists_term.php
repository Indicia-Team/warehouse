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
 * Model class for the Termlists_Terms table.
 *
 * @package	Core
 * @subpackage Models
 * @link	http://code.google.com/p/indicia/wiki/DataModel
 */
class Termlists_term_Model extends Base_Name_Model {
  // TODO: this is a temporary placeholder. Need to think how we can get the term (from the terms table)
  // in as the search field in termlists_terms. Perhaps a view?
  public $search_field='id';
  
  protected $list_id_field = 'termlist_id';

  protected $belongs_to = array(
    'term', 'termlist', 'meaning',
    'created_by' => 'user',
    'updated_by' => 'user'
  );

  protected $ORM_Tree_children = 'termlists_terms';

  public function validate(Validation $array, $save = FALSE) {
    $array->pre_filter('trim');
    $array->add_rules('term_id', 'required');
    $array->add_rules('termlist_id', 'required');
    $array->add_rules('meaning_id', 'required');
    // $array->add_callbacks('deleted', array($this, '__dependents'));

    // Explicitly add those fields for which we don't do validation
    $this->unvalidatedFields = array(
      'parent_id',
      'preferred',
      'deleted',
      'sort_order'
    );
    return parent::validate($array, $save);
  }
  /**
   * If we want to delete the record, we need to check that no dependents exist.
   */
  public function __dependents(Validation $array, $field){
    if ($array['deleted'] == 'true'){
      $record = ORM::factory('termlists_term', $array['id']);
      if (count($record->children)!=0){
        $array->add_error($field, 'has_children');
      }
    }
  }

  /**
   * Overrides the post submit function to add in synonomies
   */
  protected function postSubmit($isInsert){
    $success = true;
    if ($this->submission['fields']['preferred']['value']=='t') {
      try {
        if (array_key_exists('synonyms', $this->submission['metaFields'])) {
          $arrSyn=$this->parseRelatedNames(
            $this->submission['metaFields']['synonyms']['value'],
            'set_synonym_sub_array'
          );
        } else $arrSyn=array();
        $meaning_id=$this->submission['fields']['meaning_id']['value'];
        $existingSyn = $this->getSynonomy('meaning_id', $meaning_id);

        // Iterate through existing synonomies, discarding those that have
        // been deleted and removing existing ones from the list to add
        // Not sure this is correct way of doing it as it would appear that you can only have one synonym per language....
        foreach ($existingSyn as $syn) {
          // Is the term from the db in the list of synonyms?
          if (array_key_exists($syn->term->language->iso, $arrSyn) &&
              $arrSyn[$syn->term->language->iso] == $syn->term->term)
            // This one already in db, so can remove from our array
            $arrSyn = array_diff_key($arrSyn, array($syn->term->language->iso => ''));
          else {
            // Synonym has been deleted - remove it from the db
            $syn->deleted = 't';
            $syn->save();
          }
        }

        // $arraySyn should now be left only with those synonyms
        // we wish to add to the database

        Kohana::log("info", "Synonyms remaining to add: ".count($arrSyn));
        $sm = ORM::factory('termlists_term');
        kohana::log('debug', $arrSyn);
        foreach ($arrSyn as $lang => $term) {
          $sm->clear();
          $syn = array();
          // Wrap a new submission
          Kohana::log("debug", "Wrapping submission for synonym ".$term);
          $lang_id = ORM::factory('language')->where(array('iso' => $lang))->find()->id;
          // If language not found, use english as the default. Future versions may wish this to be
          // user definable.
          $lang_id = $lang_id ? $lang_id : ORM::factory('language')->where(array('iso' => 'eng'))->find()->id;
          // copy the original post array to pick up the common things, first the taxa_taxon_list data
          foreach (array('parent', 'sort_order', 'termlist_id') as $field) {
            if (isset($this->submission['fields'][$field])) {
              $syn["termlists_term:$field"]=is_array($this->submission['fields'][$field]) ? $this->submission['fields'][$field]['value'] : $this->submission['fields'][$field];
            }
          }
          // unlike the taxa there are no term based shared data.
          // Now update the record with specifics for this synonym
          $syn['term:id'] = null;
          $syn['term:term'] = $term;
          $syn['term:language_id'] = $lang_id;
          $syn['termlists_term:id'] = '';
          $syn['termlists_term:preferred'] = 'f';
          // meaning Id cannot be copied from the submission, since for new data it is generated when saved
          $syn['termlists_term:meaning_id'] = $meaning_id;
          // Prevent a recursion by not posting synonyms with a synonym
          $syn['metaFields:synonyms']='';
          $sub = $this->wrap($syn);
          // Don't resubmit the meaning record, again we can't rely on the order of the supermodels in the list
          foreach($sub['superModels'] as $idx => $supermodel) {
            if ($supermodel['model']['id']=='meaning') {
              unset($sub['superModels'][$idx]);
              break;
            }
          }
          $sm->submission = $sub;
          if (!$sm->submit()) {
            $success=false;
            foreach($sm->errors as $key=>$value) {
              $this->errors[$sm->object_name.':'.$key]=$value;
            }          
          }
        }
      } catch (Exception $e) {
        $this->errors['general']='<strong>An error occurred</strong><br/>'.$e->getMessage();
        error::log_error('Exception during postSubmit in termlists_term model.', $e);
        $success = false;
      }
    }
    return $success;
  }

  /**
   * Build the array that stores the language attached to synonyms being submitted.
   */
  protected function set_synonym_sub_array($tokens, &$array) {
    if (count($tokens) >= 2) {
      $array[trim($tokens[1])] = trim($tokens[0]);
    } else {
      $array[kohana::config('indicia.default_lang')] = trim($tokens[0]);
    }
  }

  /**
   * Return a displayable caption for the item.
   */
  public function caption()
  {
    if ($this->id) {
      return ($this->term_id != null ? $this->term->term : '');
    } else {
      return 'Term in List';
    }
  }

  /**
   * Return the submission structure, which includes defining term and meaning as the parent
   * (super) models, and the synonyms as metaFields which are specially handled.
   *
   * @return array Submission structure for a termlists_term entry.
   */
  public function get_submission_structure()
  {
    return array(
      'model'=>$this->object_name,
      'superModels'=>array(
        'meaning'=>array('fk' => 'meaning_id'),
        'term'=>array('fk' => 'term_id')
      ),
      'metaFields'=>array('synonyms')
    );
  }

  /**
   * Set default values for a new entry.
   */
  public function getDefaults() {
    return array(
      'preferred'=>'t'
    );
  }
  
  /**
   * Define a form that is used to capture a set of predetermined values that apply to every record during an import.
   */
  public function fixed_values_form() {
    return array(
      'term:language_id' => array( 
        'display'=>'Language', 
        'description'=>'Select the language to import preferred terms for.', 
        'datatype'=>'lookup',
        'population_call'=>'direct:language:id:language' 
      )
    );
  }

}
