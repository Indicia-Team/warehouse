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
 * Model class for the Taxa_Taxon_Lists table.
 *
 * @package	Core
 * @subpackage Models
 * @link	http://code.google.com/p/indicia/wiki/DataModel
 */
class Taxa_taxon_list_Model extends Base_Name_Model {

  protected $belongs_to = array('taxon', 'taxon_list',  'taxon_meaning',
    'created_by' => 'user',
    'updated_by' => 'user');

  protected $children = 'taxa_taxon_lists';

  public function validate(Validation $array, $save = FALSE) {
    $array->pre_filter('trim');
    $array->add_rules('taxon_id', 'required');
    $array->add_rules('taxon_list_id', 'required');
    $array->add_rules('taxon_meaning_id', 'required');
#		$array->add_callbacks('deleted', array($this, '__dependents'));

    // Explicitly add those fields for which we don't do validation
    $extraFields = array(
      'taxonomic_sort_order',
      'parent_id',
      'deleted',
      'preferred',
      'image_path',
      'description'
    );
    return parent::validate($array, $save, $extraFields);
  }

  /**
   * If we want to delete the record, we need to check that no dependents exist.
   */
  public function __dependents(Validation $array, $field){
    if ($array['deleted'] == 'true'){
      $record = ORM::factory('taxa_taxon_list', $array['id']);
      if ($record->children->count()!=0){
        $array->add_error($field, 'has_children');
      }
    }
  }

  /**
   * Return a displayable caption for the item.
   * For People, this should be a combination of the Firstname and Surname.
   */
  public function caption()
  {
    return ($this->taxon_id != null ? $this->taxon->taxon : '');
  }

  /**
   * Override the list of default submittable fields for CSV import. This allows details of the
   * taxon to also be imported.
   */
  public function getSubmittableFields() {
    $arr = parent::getSubmittableFields();
    return array_merge(array(
      'taxon' => '',
      'fk_language' => '',
      'language_id' => '',
      'fk_taxon_group' => '',
      'taxon_group_id' => '',
      'authority' => '',
      'search_code' => '',
      'external_key' => '',
      'fk_parent' => '',
      'commonNames' => '',
      'synonymy' => ''
    ));
  }

    /**
  * Overrides the postSubmit function to add in synonomies and common names
  */
  protected function postSubmit($id)
  {
    $arrCommonNames=$this->parseRelatedNames(
      $this->model->submission['metaFields']['commonNames']['value'],
      'set_common_name_sub_array'
    );
    Kohana::log("debug", "Number of common names is: ".count($arrCommonNames));

    $arrSyn=$this->parseRelatedNames(
      $this->model->submission['metaFields']['synonomy']['value'],
      'set_synonym_sub_array'
    );
    Kohana::log("debug", "Number of synonyms is: ".count($arrSyn));

    $arrSyn = array_merge($arrSyn, $arrCommonNames);

    Kohana::log("debug", "Looking for existing terms with meaning ".$this->model->taxon_meaning_id);
    $existingSyn = $this->getSynonomy('taxon_meaning_id', $this->model->taxon_meaning_id);

    // Iterate through existing synonomies, discarding those that have
    // been deleted and removing existing ones from the list to add

    foreach ($existingSyn as $syn)
    {
      // Is the taxon from the db in the list of synonyms?
      if (array_key_exists($syn->taxon->taxon, $arrSyn) &&
        $arrSyn[$syn->taxon->taxon]['lang'] ==
        $syn->taxon->language->iso &&
        $arrSyn[$syn->taxon->taxon]['auth'] ==
        $syn->taxon->authority)
      {
        $arrSyn = array_diff_key($arrSyn, array($syn->taxon->taxon => ''));
        Kohana::log("debug", "Known synonym: ".$syn->taxon->taxon);
      }
      else
      {
        // Synonym has been deleted - remove it from the db
        $syn->deleted = 't';
        Kohana::log("debug", "Deleting synonym: ".$syn->taxon->taxon);
        $syn->save();
      }
    }

    // $arraySyn should now be left only with those synonyms
    // we wish to add to the database

    Kohana::log("debug", "Synonyms remaining to add: ".count($arrSyn));
    $sm = ORM::factory('taxa_taxon_list');
    foreach ($arrSyn as $taxon => $syn)
    {

      $sm->clear();

      $lang = $syn['lang'];
      $auth = $syn['auth'];

      // Wrap a new submission
      Kohana::log("info", "Wrapping submission for synonym ".$taxon);

      $lang_id = ORM::factory('language')->where(array('iso' => $lang))->find()->id;
      // If language not found, use english as the default. Future versions may wish this to be
      // user definable.
      $lang_id = $lang_id ? $lang_id : ORM::factory('language')->where(array('iso' => 'eng'))->find()->id;
      $syn = $_POST;
      $syn['taxon_id'] = null;
      $syn['taxon'] = $taxon;
      $syn['authority'] = $auth;
      $syn['language_id'] = $lang_id;
      $syn['id'] = '';
      $syn['preferred'] = 'f';
      $syn['taxon_meaning_id'] = $this->model->taxon_meaning_id;

      $sub = $this->wrap($syn);

      $sm->submission = $sub;
      $sm->submit();
    }

    url::redirect('taxa_taxon_list/'.$this->model->taxon_list_id);
  }

  /**
   * Build the array that stores the language attached to common names being submitted.
   */
  protected function set_common_name_sub_array($tokens, &$array) {
    if (count($tokens) == 2) {
      $array[$tokens[0]] = array(
        'lang' => trim($tokens[1]),
        'auth' => ''
      );
    } else {
      $array[$tokens[0]] = array(
        'lang' => config('indicia.default_lang'),
        'auth' => ''
      );
    }
  }

  /**
   * Build the array that stores the author attached to synonyms being submitted.
   */
  protected function set_synonym_sub_array($tokens, &$array) {
    if (count($tokens) == 2) {
      $array[$tokens[0]] = array(
        'auth' => trim($tokens),
        'lang' => 'lat'
      );
    } else {
      $array[$tokens[0]] = array(
        'auth' => '',
        'lang' => 'lat'
      );
    }
  }

}
