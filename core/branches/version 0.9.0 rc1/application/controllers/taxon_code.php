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
 * @subpackage Controllers
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Controller providing CRUD access to the list of taxon_codes for a taxon meaning.
 * @todo This class has similarities to the Taxon_image_Controller class so might benefit from
 * a shared base class - a base class for entities linked to taxon meanings.
 *
 * @package	Core
 * @subpackage Controllers
 */
class Taxon_code_Controller extends Gridview_Base_Controller {

  /**
   * Constructor
   */
  public function __construct()
  {
    parent::__construct('taxon_code');
    $this->columns = array(
      'code'=>''
      );
    $this->pagetitle = "Codes";
  }  

  /**
   * Override the default index functionality to filter by taxa_taxon_list_id (as identified
   * by the taxon owning the tab).
   */
  public function index()
  { 
    if ($this->uri->total_arguments()>0) {
      $ttl = ORM::factory('taxa_taxon_list', $this->uri->argument(1));
      $this->base_filter=array('taxon_meaning_id' => $ttl->taxon_meaning_id);
    }
    parent::index();
    // pass the taxa_taxon_list id into the view, so the create button can use it to autoset
    // the taxon of the new code.
    if ($this->uri->total_arguments()>0) {
      $this->view->taxon_meaning_id=$this->uri->argument(1);
    }
  }
  
  /**
   *  Setup the default values to use when loading this controller to create a new taxon code.   
   */
  protected function getDefaults() {    
    $r = parent::getDefaults();    
    if ($this->uri->method(false)=='create') {
      // taxa_taxon_list id is passed as first argument in URL when creating. But the code
      // gets linked by meaning, so fetch the meaning_id.
      $ttl = ORM::Factory('taxa_taxon_list', $this->uri->argument(1)); 
      $r['taxa_taxon_list:id'] = $this->uri->argument(1);
      $r['taxon_code:taxon_meaning_id'] = $ttl->taxon_meaning_id;
    }
    return $r;
  }
  
  /**
   * Setup the default values to use when loading this controller to edit an existing code.   
   */
  protected function getModelValues() {    
    $r = parent::getModelValues();
    // The code is linked to a taxon meaning, but we need to use this to link back to the 
    // preferred taxa in taxon list, so when you save it knows where to go back to.
    $ttl = ORM::Factory('taxa_taxon_list')->where(array(
      'taxon_meaning_id' => $this->model->taxon_meaning_id,
      'preferred' => 'true'
    ))->find();
    $r['taxa_taxon_list:id'] = $ttl->id;
    return $r;
  }
  
/**
   * Override the default return page behaviour so that after saving a code you
   * are returned to the taxa_taxon_list entry which has the code.
   */
  protected function get_return_page() {
    if (array_key_exists('taxa_taxon_list:id', $_POST)) {
      return "taxa_taxon_list/edit/".$_POST['taxa_taxon_list:id']."?tab=codes";
    } else {
      return $this->model->object_name;
    }
  }
  
  /**
   * Get the list of terms ready for the code types list. We only want child terms as the parent
   * terms are categories such as searchable.
   */
  protected function prepareOtherViewData($values)
  {    
    return array(
      'code_type_terms' => $this->get_termlist_terms('indicia:taxon_code_types', array('parent_id is not'=>null))    
    );   
  }
}

?>
