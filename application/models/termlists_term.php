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
    'term', 'termlist',
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
    $extraFields = array(
      'parent_id',
      'preferred',
      'deleted',
      'sort_order'
    );
    return parent::validate($array, $save, $extraFields);
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
  protected function postSubmit($id){
    try {
      $arrSyn=$this->parseRelatedNames(
      $this->model->submission['metaFields']['synonomy']['value'],
        'set_synonym_sub_array'
      );
      Kohana::log("debug", "Number of synonyms is: ".count($arrSyn));

      Kohana::log("info", "Looking for existing terms with meaning ".$this->model->meaning_id);
      $existingSyn = $this->getSynonomy('meaning_id', $this->model->meaning_id);

      // Iterate through existing synonomies, discarding those that have
      // been deleted and removing existing ones from the list to add

      foreach ($existingSyn as $syn) {
        // Is the term from the db in the list of synonyms?
        if (array_key_exists($syn->term->term, $arrSyn) &&
            $arrSyn[$syn->term->term]['lang'] ==
            $syn->term->language->iso ) {
          $arrSyn = array_diff_key($arrSyn, array($syn->term->term => ''));
          Kohana::log("debug", "Known synonym: ".$syn->term->term);
        } else {
          // Synonym has been deleted - remove it from the db
          $syn->deleted = 't';
          Kohana::log("debug", "Deleted synonym: ".$syn->term->term);
          $syn->save();
        }
      }

      // $arraySyn should now be left only with those synonyms
      // we wish to add to the database

      Kohana::log("info", "Synonyms remaining to add: ".count($arrSyn));
      $sm = ORM::factory('termlists_term');
      foreach ($arrSyn as $term => $syn) {

        $sm->clear();

        $lang = $syn['lang'];

        // Wrap a new submission
        Kohana::log("info", "Wrapping submission for synonym ".$term);

        $syn = $_POST;
        $syn['term_id'] = null;
        $syn['term'] = $term;
        $syn['language_id'] = ORM::factory('language')->where(array(
          'iso' => $lang))->find()->id;
        $syn['id'] = '';
        $syn['preferred'] = 'f';
        $syn['meaning_id'] = $this->model->meaning_id;

        $sub = $this->wrap($syn);

        $sm->submission = $sub;
        $sm->submit();
      }
      return true;
    } catch (Exception $e) {
      $this->errors['synonymy']=$e.getMessage();
      kohana::log('error', $e->getMessage());
      return false;
    }
  }

  /**
   * Build the array that stores the language attached to synonyms being submitted.
   */
  protected function set_synonym_sub_array($tokens, &$array) {
    if (count($tokens) >= 2) {
      $array[$tokens[0]] = array('lang' => trim($tokens[1]));
    } else {
      $array[$tokens[0]] = array('lang' => 'eng');
    }
  }
}
