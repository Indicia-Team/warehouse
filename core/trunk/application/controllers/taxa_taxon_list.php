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
 * Controller providing CRUD access to the taxa that belong to a checklist.
 *
 * @package	Core
 * @subpackage Controllers
 */
class Taxa_taxon_list_Controller extends Gridview_Base_Controller
{

  private $taxonListId;
  private $taxonListName;

  public function __construct()
  {
    parent::__construct('taxa_taxon_list', 'gv_taxon_lists_taxon', 'taxa_taxon_list/index');
    $this->base_filter['parent_id']=null;
    $this->base_filter['preferred']='t';
    $this->columns = array(
      'taxon'=>'',
      'authority'=>'',
      'taxon_group'=>'Taxon Group',
      'language'=>'',    
    );
    $this->pagetitle = "Species";    
    $this->model = ORM::factory('taxa_taxon_list');
  }

  /**
  * Override the default page functionality to filter by taxon_list.
  */
  public function page($page_no, $filter=null)
  {
    $taxon_list_id=$filter;
    // At this point, $taxon_list_id has a value - the framework will trap the other case.
    // No further filtering of the gridview required as the very fact you can access the parent taxon list
    // means you can access all the taxa for it.
    if (!$this->taxon_list_authorised($taxon_list_id))
    {
      $this->access_denied('table to view records with a taxon list ID='.$taxon_list_id);
      return;
    }
    $this->base_filter['taxon_list_id'] = $taxon_list_id;
    $this->pagetitle = "Species in ".ORM::factory('taxon_list',$taxon_list_id)->title;
    parent::page($page_no);
    $this->view->taxon_list_id = $taxon_list_id;
    $this->upload_csv_form->staticFields = array(
      'taxa_taxon_list:taxon_list_id' => $taxon_list_id
    );    
    $this->upload_csv_form->returnPage = $taxon_list_id;
  }

  /**
   * Method to retrieve pages for the index grid of taxa_taxon_list entries from an AJAX
   * pagination call. Overrides the base class behaviour to enforce a filter on the 
   * taxon list id.   
   */
  public function page_gv($page_no, $filter=null)
  {
    $taxon_list_id=$filter;
    $this->base_filter['taxon_list_id'] = $taxon_list_id;
    return parent::page_gv($page_no);
  }

  /**
   * Returns an array of all values from this model and its super models ready to be 
   * loaded into a form. For this controller, we need to also setup the child taxon grid 
   * and the synonyms/common names plus the list of images.
   */
  protected function getModelValues() {
    $r = parent::getModelValues();    

    $child_grid_html = $this->get_child_grid($this->model->id, 
       $this->uri->argument(3) || 1, // page number
       4 // limit
    );
    
    // Add items to view
    $r = array_merge($r, array(
      'table' => $child_grid_html,
      'metaFields:synonyms' => $this->formatScientificSynonomy(
        $this->model->getSynonomy('taxon_meaning_id', $this->model->taxon_meaning_id)),
      'metaFields:commonNames' => $this->formatCommonSynonomy(
        $this->model->getSynonomy('taxon_meaning_id', $this->model->taxon_meaning_id))
    ));
    return $r;  
  }
  
  /**
   *  Setup the default values to use when loading this controller to edit a new page.   
   */
  protected function getDefaults() {    
    $r = parent::getDefaults();    
    if ($this->uri->method(false)=='create') {
      // List id is passed as first argument in URL when creating
      $r['taxa_taxon_list:taxon_list_id'] = $this->uri->argument(1);
      // Parent id might be passed in $_POST if creating as child of another taxon.
      if (array_key_exists('taxa_taxon_list:parent_id', $_POST)) {
        $r['taxa_taxon_list:parent_id']=$_POST['taxa_taxon_list:parent_id'];
      }
    } elseif ($this->uri->method(false)=='edit') {      
      if (array_key_exists('taxa_taxon_list:id', $_POST) && $_POST['taxa_taxon_list:id']) {
        $r['table'] = $this->get_child_grid($_POST['taxa_taxon_list:id'],      
          $this->uri->argument(3) || 1, // page number
          1 // limit
        );
      }
    }   
    return $r;    
  }

  /**
   *  Auxilliary function for handling Ajax requests from the edit method child taxa
   *  gridview component.
   */
  public function edit_gv($id,$page_no)
  {
    $this->auto_render=false;
    return $this->get_child_grid($id,$page_no);
  }
  
  /**
   * Returns the HTML required for the grid of children of this taxon entry.
   * 
   * @return string HTML for the grid.
   * @access private  
   */
  private function get_child_grid($id,$page_no)
  {
    $gridmodel = ORM::factory('gv_taxon_lists_taxon');

    $child_grid =	Gridview_Controller::factory(
        $gridmodel,
        $page_no,        
        4
    );
    $child_grid->base_filter = $this->base_filter;
    $child_grid->base_filter['parent_id'] = $id;
    $child_grid->columns =  $this->columns;
    $child_grid->actionColumns = array(
      'edit' => 'taxa_taxon_list/edit/£id£'
    );
    return $child_grid->display();
  }

  /**
   * Reports if editing a taxon in taxon list is authorised.
   * 
   * @param int $id Id of the taxa_taxon_list that is being checked, or null for a new record.
   */
  protected function record_authorised ($id)
  {
    if ($id===null) {
      // Creating a new record, so the taxon list id is an argument
      $list_id=$this->uri->argument(1);
    } else {
      $taxa = new Taxa_taxon_list_Model($id);
      // The id should already exist, otherwise the user is attempting to create by passing 
      // a param to the edit function.
      if (!$taxa->loaded) {
        return false;
      } else {
        $list_id=$taxa->taxon_list_id;        
      }
    }
    return ($this->taxon_list_authorised($list_id));
  }

  protected function taxon_list_authorised ($id)
  {
    // for this controller, any null ID taxon_list can not be accessed
    if (is_null($id)) return false;
    if (!is_null($this->gen_auth_filter))
    {
      $taxon_list = new Taxon_list_Model($id);
      // for this controller, any taxon_list that does not exist can not be accessed.
      if (!$taxon_list->loaded) return false;
      return (in_array($taxon_list->website_id, $this->gen_auth_filter['values']));
    }
    return true;
  }

  /**
   * Override the default return page behaviour so that after saving a taxa you
   * are returned to the list of taxa on the sub-tab of the list.
   */
  protected function get_return_page() {
    if (array_key_exists('taxa_taxon_list:taxon_list_id', $_POST)) {
      return "taxon_list/edit/".$_POST['taxa_taxon_list:taxon_list_id']."?tab=taxa";
    } else {
      return $this->model->object_name;
    }
  }
  
  /**
   * Retrieves the value to display in the textarea for the scientific names.
   * 
   * @return string Value for scientific names
   * @access private   
   */
  private function formatScientificSynonomy(ORM_Iterator $res)
  {
    $syn = "";
    foreach ($res as $synonym)
    {
      if ($synonym->taxon->language->iso == "lat")
      {
        $syn .= $synonym->taxon->taxon;
        if ($synonym->taxon->authority) {
          $syn .=	" | ".$synonym->taxon->authority;
        }
        $syn .= "\n";
      }
    }
    return $syn;
  }

  /**
   * Retrieves the value to display in the textarea for the common names.
   * 
   * @return string Value for common names
   * @access private   
   */
  private function formatCommonSynonomy(ORM_Iterator $res)
  {
    $syn = "";
    foreach ($res as $synonym)
    {
      if ($synonym->taxon->language->iso != "lat")
      {
        $syn .= $synonym->taxon->taxon;
        $syn .=	($synonym->taxon->language_id != null) ?
        " | ".$synonym->taxon->language->iso."\n" :
        '';
      }
    }
    return $syn;
  }

}
?>
