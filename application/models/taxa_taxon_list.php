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
  public $search_field='taxon';
  
  protected $lookup_against='lookup_taxa_taxon_list';
  
  protected $belongs_to = array(
    'taxon', 
    'taxon_list',  
    'taxon_meaning',
    'created_by' => 'user',
    'updated_by' => 'user'
  );

  protected $ORM_Tree_children = 'taxa_taxon_lists';  
  protected $list_id_field = 'taxon_list_id';

  public function validate(Validation $array, $save = FALSE) {
    $array->pre_filter('trim');
    $array->add_rules('taxon_id', 'required');
    $array->add_rules('taxon_list_id', 'required');
    $array->add_rules('taxon_meaning_id', 'required');
#		$array->add_callbacks('deleted', array($this, '__dependents'));

    // Explicitly add those fields for which we don't do validation
    $this->unvalidatedFields = array(
      'taxonomic_sort_order',
      'parent_id',
      'deleted',
      'allow_data_entry',
      'preferred',
      'description'
    );
    return parent::validate($array, $save);
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
   */
  public function caption()
  {
    if ($this->id) {
      return ($this->taxon_id != null ? $this->taxon->taxon : '');
    } else {
      return 'Taxon in List';
    }    
  }

  public function preSubmit() {
    $this->submission['fields']['allow_data_entry'] = array('value' => (isset($this->submission['fields']['allow_data_entry']) ? 't' : 'f'));

    return parent::preSubmit();
  }
  
  /**
  * Overrides the postSubmit function to add in synonomies and common names. This only applies
  * when adding a preferred name, not a synonym or common name.
  */
  protected function postSubmit()
  {
    $result = true;
    if ($this->submission['fields']['preferred']['value']=='t' && array_key_exists('metaFields', $this->submission)) {      
      if (array_key_exists('commonNames', $this->submission['metaFields'])) {
        $arrCommonNames=$this->parseRelatedNames(
            $this->submission['metaFields']['commonNames']['value'],
            'set_common_name_sub_array'
        ); 
      } else $arrCommonNames=array();
      Kohana::log("debug", "Number of common names is: ".count($arrCommonNames));
      if (array_key_exists('synonyms', $this->submission['metaFields'])) {
        $arrSyn=$this->parseRelatedNames(
          $this->submission['metaFields']['synonyms']['value'],
          'set_synonym_sub_array'
        );
      } else $arrSyn=array();
      Kohana::log("debug", "Number of synonyms is: ".count($arrSyn));

      $arrSyn = array_merge($arrSyn, $arrCommonNames);

      Kohana::log("debug", "Looking for existing taxa with meaning ".$this->taxon_meaning_id);
      $existingSyn = $this->getSynonomy('taxon_meaning_id', $this->taxon_meaning_id);

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
          if ($this->common_taxon_id==$syn->taxon->id) {
            $this->common_taxon_id=null;
          }
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
        // copy the original post array to pick up the common things, first the taxa_taxon_list data
        $this->copy_shared_fields_from_submission('taxa_taxon_list', $this->submission['fields'], $syn, array(
            'description', 'parent', 'taxonomic_sort_order', 'allow_data_entry', 'taxon_list_id'        
        ));

        // Next do the data in the taxon supermodel - we have to search for it rather than rely on it being in a particular position in the list
        foreach($this->submission['superModels'] as $supermodel) {
          if ($supermodel['model']['id']=='taxon') {
            $this->copy_shared_fields_from_submission('taxon',$supermodel['model']['fields'], $syn, array(
                'description', 'external_key', 'taxon_group_id'
            ));
            break;
          }
        }
        // Now update the record with specifics for this synonym
        $syn['taxon:id'] = null;
        $syn['taxon:taxon'] = $taxon;
        $syn['taxon:authority'] = $auth;
        $syn['taxon:language_id'] = $lang_id;
        $syn['taxa_taxon_list:id'] = '';
        $syn['taxa_taxon_list:preferred'] = 'f';
        // taxon meaning Id cannot be copied from the submission, since for new data it is generated when saved
        $syn['taxa_taxon_list:taxon_meaning_id'] = $this->taxon_meaning_id;
        $sub = $this->wrap($syn);
        // Don't resubmit the meaning record, again we can't rely on the order of the supermodels in the list
        foreach($sub['superModels'] as $idx => $supermodel) {
          if ($supermodel['model']['id']=='taxon_meaning') {
            unset($sub['superModels'][$idx]);
            break;
          }
        }
        $sm->submission = $sub;
        if (!$sm->submit()) {
          $result=false;
          foreach($sm->errors as $key=>$value) {
            $this->errors[$sm->object_name.':'.$key]=$value;
          }          
        } else {
          // If synonym is not latin (a common name), and we have no common name for this object, use it.
          if ($this->common_taxon_id==null && $syn['taxon:language_id']!=2) {
            $this->common_taxon_id=$sm->taxon->id;             
          }
        }        
      }
      // post the common name id change if required.
      if (isset($this->changed['common_taxon_id'])) {
        $this->save();        
      }      
    }
    return $result;
  }
  
  /**
   * When posting synonyms or common names, some field values can be re-used from the preferred term such as the 
   * descriptions and taxon group. This is a utility method for copying submission data matching a list of fields into the 
   * save array for the synonym/common name.
   * @param string $modelName The name of the model data is being copied for, used as a prefix when building the save array
   * @param array $source The array of fields and values for the part of the submission being copied (i.e. 1 model's values).
   */
  protected function copy_shared_fields_from_submission($modelName, $source, &$saveArray, $fields) {
    foreach ($fields as $field) {
      if (isset($source[$field])) {
        $saveArray["$modelName:$field"]=is_array($source[$field]) ? $source[$field]['value'] : $source[$field];
      }
    }
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
        'lang' => kohana::config('indicia.default_lang'),
        'auth' => ''
      );
    }
  }

  /**
   * Build the array that stores the author attached to synonyms being submitted.
   */
  protected function set_synonym_sub_array($tokens, &$array) {
    $array[$tokens[0]] = array(
      'auth' => '',
      'lang' => 'lat'
    );
    if (count($tokens) == 2) {
      $array[$tokens[0]]['auth']=trim($tokens[1]);
    }
  }

  /**
   * Return the submission structure, which includes defining taxon and taxon_meaning
   * as the parent (super) models, and the synonyms and commonNames as metaFields which 
   * are specially handled.
   * 
   * @return array Submission structure for a taxa_taxon_list entry.
   */
  public function get_submission_structure()
  {
    return array(
        'model'=>$this->object_name,
        'superModels'=>array(
          'taxon_meaning'=>array('fk' => 'taxon_meaning_id'),
          'taxon'=>array('fk' => 'taxon_id')
        ),
        'metaFields'=>array('synonyms', 'commonNames')      
    );
  }
  
  /** 
   * Set default values for a new entry.   
   */
  public function getDefaults() {
    return array(
      'preferred'=>'t',
      'taxa_taxon_list:allow_data_entry' => 't'
    );  
  }
  
  /**
   * Define a form that is used to capture a set of predetermined values that apply to every record during an import.
   */
  public function fixed_values_form() {
    return array(
      'taxa_taxon_list:taxon_list_id' => array( 
        'display'=>'Species List', 
        'description'=>'Select the list to import into.', 
        'datatype'=>'lookup',
        'population_call'=>'direct:taxon_list:id:title' 
      ),
      'taxon:language_id' => array( 
        'display'=>'Language', 
        'description'=>'Select the language to import preferred taxa for.', 
        'datatype'=>'lookup',
        'population_call'=>'direct:language:id:language' 
      ),
      'taxon:taxon_group_id' => array( 
        'display'=>'Taxon Group', 
        'description'=>'Select the taxon group to import taxa for.',
        'datatype'=>'lookup',
        'population_call'=>'direct:taxon_group:id:title'
      )
    );
  }

}
