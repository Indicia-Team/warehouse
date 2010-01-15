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
  protected $search_field='id';

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
  protected function postSubmit(){
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

        foreach ($existingSyn as $syn) {
          // Is the term from the db in the list of synonyms?
          if (array_key_exists($syn->term->term, $arrSyn) &&
              $arrSyn[$syn->term->term]['lang'] ==
              $syn->term->language->iso ) {
            $arrSyn = array_diff_key($arrSyn, array($syn->term->term => ''));
          } else {
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
        foreach ($arrSyn as $term => $syn) {
          $sm->clear();
          $lang = $syn['lang'];
          // Wrap a new submission
          Kohana::log("info", "Wrapping submission for synonym ".$term);
          $syn = $_POST;
          $syn['term:id'] = null;
          $syn['term:term'] = $term;
          $syn['term:language_id'] = ORM::factory('language')->where(array(
            'iso' => $lang))->find()->id;
          $syn['termlists_term:id'] = '';
          $syn['termlists_term:preferred'] = 'f';
          $syn['termlists_term:meaning_id'] = $meaning_id;
          // Prevent a recursion by not posting synonyms with a synonym
          $syn['metaFields:synonyms']='';

          $sub = $this->wrap($syn);
          // Don't resubmit the meaning record
          unset($sub['superModels'][0]);
          
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
      $array[$tokens[0]] = array('lang' => trim($tokens[1]));
    } else {
      $array[$tokens[0]] = array('lang' => kohana::config('indicia.default_lang'));
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

}
