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
 * @package  Core
 * @subpackage Controllers
 * @author  Indicia Team
 * @license  http://www.gnu.org/licenses/gpl.html GPL
 * @link   http://code.google.com/p/indicia/
 */

/**
 * Controller providing CRUD access to the taxa that belong to a checklist.
 *
 * @package  Core
 * @subpackage Controllers
 */
class Taxa_taxon_list_Controller extends Gridview_Base_Controller
{

  public function __construct()
  {
    parent::__construct('taxa_taxon_list', null, null, 'taxa_taxon_list');
    $this->base_filter['parent_id']=null;
    $this->base_filter['preferred']='t';
    $this->columns = array(
      'taxon'=>'',
      'authority'=>'',
      'taxon_group'=>'Taxon Group',
      'language'=>'',
      'taxonomic_sort_order'=>'Sort Order'
    );
    $this->pagetitle = "Species";
  }
  
 /**
  * Override the default index functionality to filter by taxon_list.
  */
  public function index()
  {
    $taxon_list_id = $this->uri->argument(1);
    $taxonList = ORM::factory('taxon_list',$taxon_list_id);
    $this->pagetitle = "Species in ".$taxonList->title;
    $this->internal_index($taxon_list_id, $taxonList);
  }
 
  public function children($id) {
    $parentTtl = ORM::factory('taxa_taxon_list', $id);
    $this->base_filter['parent_id'] = $id;
    $taxonList = ORM::factory('taxon_list', $parentTtl->taxon_list_id);
    $this->internal_index($parentTtl->taxon_list_id, $taxonList);
    // pass the parent id into the view, so the create list button can use it to autoset
    // the parent of the new list.
    $this->view->parent_id=$id;
  }
  
  private function internal_index($taxon_list_id, $taxonList) {
    // No further filtering of the gridview required as the very fact you can access the parent taxon list
    // means you can access all the taxa for it.
    if (!$this->taxon_list_authorised($taxon_list_id))
    {
      $this->access_denied('table to view records with a taxon list ID='.$taxon_list_id);
      return;
    }
    $this->base_filter['taxon_list_id'] = $taxon_list_id;
    if (!empty($taxonList->parent_id)) {
      unset($this->base_filter['parent_id']);
    }
    parent::index(); 
    $this->view->taxon_list_id = $taxon_list_id;
    $this->view->parent_list_id = $taxonList->parent_id;
    $this->upload_csv_form->staticFields = array(
      'taxa_taxon_list:taxon_list_id' => $taxon_list_id
    );
    $this->upload_csv_form->returnPage = $taxon_list_id;
  }

  /**
   * Define non-standard behaviuor for the breadcrumbs, since this is accessed via a taxon list
   */
  protected function defineEditBreadcrumbs() {
    $this->page_breadcrumbs[] = html::anchor('taxon_list', 'Species Lists');
    if ($this->model->id) {
      // editing an existing item, so our argument is the taxa in taxon list id
      $listId = $this->model->taxon_list_id;
    } else {
      // creating a new one so our argument is the taxon list id
      $listId = $this->uri->argument(1);
    }
    $listTitle = ORM::Factory('taxon_list', $listId)->title;
    $this->page_breadcrumbs[] = html::anchor('taxon_list/edit/'.$listId.'?tab=taxa', $listTitle);
    $this->page_breadcrumbs[] = $this->model->caption();
  }

  /**
   * Returns an array of all values from this model and its super models ready to be
   * loaded into a form. For this controller, we need to also setup the child taxon grid
   * and the synonyms/common names plus the list of images.
   */
  protected function getModelValues() {
    $r = parent::getModelValues();
    $this->loadAttributes($r, array('taxon_list_id'=>array($this->model->taxon_list_id)));
    // Add items to view
    $all_names = $this->model->getSynonomy('taxon_meaning_id', $this->model->taxon_meaning_id);
    $r = array_merge($r, array(
      'metaFields:synonyms' => $this->formatScientificSynonomy($all_names),
      'metaFields:commonNames' => $this->formatCommonSynonomy($all_names)
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
    } else {
      // after a validation failure, the list id is in the post data
      $r['taxa_taxon_list:taxon_list_id'] = $_POST['taxa_taxon_list:taxon_list_id'];
    }
    $this->loadAttributes($r, 
        array('taxon_list_id'=>array($r['taxa_taxon_list:taxon_list_id']))
    );
    return $r;
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

  protected function taxon_list_authorised($id)
  {
    // for this controller, any null ID taxon_list can not be accessed
    if (is_null($id)) return false;
    $websites = $this->get_allowed_website_id_list('editor', false);
    if (!is_null($websites))
    {
      $taxon_list = new Taxon_list_Model($id);
      // for this controller, any taxon_list that does not exist can not be accessed.
      if (!$taxon_list->loaded) return false;
      return (in_array($taxon_list->website_id, $websites));
    }
    return true;
  }

  /**
   * Override the default return page behaviour so that after saving a taxa you
   * are returned to the list of taxa on the sub-tab of the list.
   */
  protected function get_return_page() {
    if (array_key_exists('taxa_taxon_list:taxon_list_id', $_POST)) {
      // after saving a record, the list id to return to is in the POST data.
      // user may select to continue adding new taxa
      if (isset($_POST['what-next'])) {
        if ($_POST['what-next']=='add')
          return 'taxa_taxon_list/create/'.$_POST['taxa_taxon_list:taxon_list_id'];
      }
      // or just return to the list page
      return "taxon_list/edit/".$_POST['taxa_taxon_list:taxon_list_id']."?tab=taxa";
    } elseif (array_key_exists('taxa_taxon_list:taxon_list_id', $_GET))
      // after uploading records, the list id is in the URL get parameters
      return "taxon_list/edit/".$_GET['taxa_taxon_list:taxon_list_id']."?tab=taxa";
    else
      // last resort if we don't know the list, just show the whole lot of lists
      return "taxon_list";
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
          $syn .=  " | ".$synonym->taxon->authority;
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
        $syn .=  ($synonym->taxon->language_id != null) ?
        " | ".$synonym->taxon->language->iso."\n" :
        '';
      }
    }
    return $syn;
  }
  
  /**
   * Controller action for the lumping and splitting tab.
   */
  public function lumping_splitting($id) {
    $ttl = ORM::Factory('taxa_taxon_list', $id);
    $this->setView('taxa_taxon_list/lumping_splitting', '', array(
      'values' => array(
        'taxa_taxon_list:id' => $id,
        'taxa_taxon_list:taxon_list_id' => $ttl->taxon_list_id,
        'taxon_meaning:id' => $ttl->taxon_meaning_id
      )
    ));
  }

  /**
   * Return a list of the tabs to display for this controller's actions.
   */
  protected function getTabs($name) {
    return array(array(
      'controller' => 'taxon_medium',
      'title' => 'Media Files',
      'actions'=>array('edit')
    ), array(
      'controller' => 'taxon_code',
      'title' => 'Codes',
      'actions'=>array('edit')
    ), array(
      'controller' => 'taxa_taxon_list/children',
      'title' => 'Child Taxa',
      'actions'=>array('edit')
    ), array(
      'controller' => 'taxon_relation',
      'title' => 'Relations',
      'actions'=>array('edit')
    ), array(
      'controller' => 'taxa_taxon_list/lumping_splitting',
      'title' => 'Lumping & Splitting',
      'actions'=>array('edit')
    ));
  }

  /**
   * Function used by the AJAX methods which allow adding a taxon from a parent list into
   * the current list. Adds a single taxon.
   * @param integer $parentTtlId The taxa_taxon_list_id to add, from the parent list.
   * @param integer $thisListId The current list ID
   */
  private function add_single_taxon_from_parent_list($parentTtlId, $thisListId) {
    // get the selected name
    $ttl = ORM::factory('taxa_taxon_list', $parentTtlId);
    // find a list of the taxon ids for this meaning which are already in the list.
    $existing = ORM::factory('taxa_taxon_list')->where(array(
      'taxon_list_id'=>$thisListId,
      'taxon_meaning_id'=>$ttl->taxon_meaning_id
    ))->find_all();
    $existingTaxa = array();
    foreach($existing as $item)
      $existingTaxa[] = $item->taxon_id;
    // we must copy across all names for the taxon not just the selected one
    $all_names = ORM::factory('taxa_taxon_list')->where(array(
      'taxon_list_id' => $ttl->taxon_list_id,
      'taxon_meaning_id' => $ttl->taxon_meaning_id
    ))->find_all();
    $existingCount = 0;
    $newCount = 0;
    $r = array('added_preferred_taxa_taxon_list_id'=>false, 'message'=>'');
    // loop through the names
    foreach($all_names as $name) {
      $data = $name->as_array();
      if (in_array($data['taxon_id'], $existingTaxa)) {
        $existingCount++;
      }
      else {
        unset($data['id']);
        $data['taxon_list_id']=$_POST['taxon_list_id'];
        // create a new model using the existing ttl data but a new list id
        $newttl = ORM::factory('taxa_taxon_list');
        $newttl->validate(new Validation($data), true);
        // we want to return the id of the preferred taxon copied over
        if ($newttl->preferred=='t')
          $r['added_preferred_taxa_taxon_list_id'] = $newttl->id;
        $newCount++;
      }
    }
    if (!$r['added_preferred_taxa_taxon_list_id']) {
      // failed to add something, so generate a message as to why
      if ($existingCount>0)
        $r['message'] = 'The taxon already exists in the list.';
      elseif ($newCount>0)
        $r['message'] = 'The taxon already exists in the list but some names were missing, so they have '.
          'been copied across.';
      else
        $r['message'] = 'Failed to add the taxon to the list.';
    }
    return $r;
  }

  /**
   * AJAX controller method for the ability to add a taxon from a parent list into a child list.
   * Takes the child (destination) taxon list id and the source taxa taxon list id as parameters
   * in the $_POST data.
   */
  public function add_parent_taxon() {
    // no template as this is for AJAX
    $this->auto_render=false;
    $outcome = $this->add_single_taxon_from_parent_list($_POST['taxa_taxon_list_id'], $_POST['taxon_list_id']);
    if ($outcome['added_preferred_taxa_taxon_list_id'])
      echo $outcome['added_preferred_taxa_taxon_list_id'];
    else
      echo $outcome['message'];
  }

  public function add_parent_taxon_list() {
    // no template as this is for AJAX
    $this->auto_render=false;
    // convert the pasted text into an array
    $pasted_taxa = str_replace("\r\n", "\n", $_POST['taxa_to_add']);
    $pasted_taxa = str_replace("\r", "\n", $pasted_taxa);
    $pasted_taxa = explode("\n", trim($pasted_taxa));
    $thisListId = $_POST['taxon_list_id'];
    $list = ORM::factory('taxon_list', $thisListId);
    if (!$list->parent_id) {
      throw new exception('Trying to copy taxa into a child list but the list has no parent');
    };
    $messages = array();
    foreach ($pasted_taxa as $pasted_taxon) {
      $pasted_taxon = trim($pasted_taxon);
      if (empty($pasted_taxon))
        continue; // to next in list, as empty line found
      $rows = $this->db->select('id, taxon_meaning_id, taxon_id, preferred, taxon')
        ->from('list_taxa_taxon_lists')
        ->where(array(
          $_POST['search_method'] => $pasted_taxon,
          'taxon_list_id' => $list->parent_id
        ))
        ->orderby('preferred', 'DESC')
        ->get()->result_array(false);
      if (empty($rows))
        $messages[] = "$pasted_taxon could not be found in the parent list";
      elseif (count($rows)>1 && ($rows[0]['preferred']==='f' || $rows[1]['preferred']==='t'))
        $messages[] = "$pasted_taxon was found but could not be used to identify a unique taxon in the parent list";
      else {
        // Found a unique hit, either the only matching preferred name, or the only matching name
        // so we can add it to the sublist database.
        $outcome = $this->add_single_taxon_from_parent_list($rows[0]['id'], $thisListId);
        $taxon = $rows[0]['taxon'];
        if ($outcome['added_preferred_taxa_taxon_list_id'])
          $messages[] = "$taxon was added to the list";
        else {
          $messages[] = "$taxon - $outcome[message]";
        }
      }
    }
    echo json_encode($messages);
  }

}
?>
