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
 * @package Core
 * @subpackage Controllers
 * @author	Indicia Team
 * @link http://code.google.com/p/indicia/
 * @license http://www.gnu.org/licenses/gpl.html GPL
 */

/**
 * Controller for the known_subject page.
 *
 * @package Groups and individuals module
 * @subpackage Controllers
 */
class Known_subject_Controller extends Gridview_Base_Controller {

  /**
   * Constructor
   */
  public function __construct()
  {
    parent::__construct('known_subject');
    $this->columns = array(
      'id'=>'ID',
      'taxa'=>'',
      'subject_type'=>'',
      'short_description'=>'Description',
    );
    $this->pagetitle = "Known Subjects";
  }

  public function page_authorised()
  {
    return $this->auth->logged_in('CoreAdmin') || $this->auth->has_any_website_access('editor');
  }
  
  protected function getModelValues() {
    $r = parent::getModelValues();
    $r['joinsTo:taxa_taxon_list:id'] = 
      $this->reformatTaxaJoinsForList($r, 'taxa_taxon_list', true);
    // load data for attributes, TODO, fix this
    $websiteId = $r['known_subject:website_id'];
    $this->loadAttributes($r, array(
        'website_id'=>$websiteId,
    ));
    return $r;      
  }
  
  /**
   * Load default values either when creating a sample new or reloading after a validation failure.
   * This adds the custom attributes list to the data available for the view. 
   */
  protected function getDefaults() {
    $r = parent::getDefaults();
    $r['joinsTo:taxa_taxon_list:id'] = 
      $this->reformatTaxaJoinsForList($r, 'taxa_taxon_list', true);
    if (array_key_exists('known_subject:id', $_POST)) {
      $websiteId = $r['known_subject:website_id'];
      $this->loadAttributes($r, array(
        'website_id'=>$websiteId,
      ));
    }
    return $r;
  }

  /**
   * Get the list of terms ready for the subject type list. 
   */
  protected function prepareOtherViewData($values)
  {    
    return array(
      'subject_type_terms' => $this->get_termlist_terms('indicia:assoc:subject_type')    
    );   
  }

  protected function reformatTaxaJoinsForList($values, $singular_table, $id_only=false) {
    // re-format values for joined taxa. These are returned suitable for checkboxes, 
    // but we put them in an array suitable for a list type control
    // as array(id = 'value', ... ) or id $id_only is true, array(id1, id2, ...)
    $join_ids = array();
    $join_keys = preg_grep('/^joinsTo:'.$singular_table.':[0-9]+$/', array_keys($values));
    foreach ($join_keys as $key) {
      $id = substr($key, strlen('joinsTo:'.$singular_table.':'));
      if ($id_only) {
        $join_ids[] = $id;
      } else {
        $name = ORM::Factory($singular_table, $id)->taxon->taxon;
        $join_ids[$id] = $name;
      }
    }              
    return $join_ids;      
  }
}
?>
