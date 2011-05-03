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
 * Controller providing CRUD access to the terms that belong to a termlist.
 *
 * @package	Core
 * @subpackage Controllers
 */
class Termlists_term_Controller extends Gridview_Base_Controller {

  public function __construct() {
    parent::__construct(
      'termlists_term',
      'termlists_term/index'
    );

    $this->base_filter['parent_id']=null;
    $this->base_filter['preferred']='t';
    $this->columns = array(
      'term'=>'',
      'language'=>'',
      );
    $this->pagetitle = "Terms";
  }
  
 /**
  * Override the default index functionality to filter by termlist.
  */
  public function index()
  {
    $termlist_id = $this->uri->argument(1);
    $list = ORM::factory('termlist',$termlist_id);
    $this->pagetitle = "Terms in ".$list->title;
    $this->internal_index($termlist_id);
  }
 
  public function children($id) {
    $parentTlt = ORM::factory('termlists_term', $id);
    $this->base_filter['parent_id'] = $id;
    $this->internal_index($parentTlt->termlist_id);
    // pass the parent id into the view, so the create list button can use it to autoset
    // the parent of the new list.
    $this->view->parent_id=$id;
  }
  
  private function internal_index($termlist_id) {
    // No further filtering of the gridview required as the very fact you can access the parent termlist
    // means you can access all the taxa for it.
    if (!$this->termlist_authorised($termlist_id))
    {
      $this->access_denied('table to view records with a termlist ID='.$termlist_id);
      return;
    }
    $this->base_filter['termlist_id'] = $termlist_id;
    parent::index(); 
    $this->view->termlist_id = $termlist_id;
    $list = ORM::factory('termlist', $termlist_id);
    $this->view->parent_list_id = $list->parent_id;
    $this->upload_csv_form->staticFields = array(
      'termlists_term:termlist_id' => $termlist_id
    );
    $this->upload_csv_form->returnPage = $termlist_id;
  }

  /**
   * Define non-standard behaviuor for the breadcrumbs, since this is accessed via a term list
   */
  protected function defineEditBreadcrumbs() {
    $this->page_breadcrumbs[] = html::anchor('termlist', 'Term Lists');
    if ($this->model->id) {
      // editing an existing item, so our argument is the termlists_term_id
      $listId = $this->model->termlist_id;
    } else {
      // creating a new one so our argument is the termlist id
      $listId = $this->uri->argument(1);
    }
    $listTitle = ORM::Factory('termlist', $listId)->title;
  	$this->page_breadcrumbs[] = html::anchor('termlist/edit/'.$listId.'?tab=terms', $listTitle);
	  $this->page_breadcrumbs[] = $this->model->caption();
  }

  private function formatSynonomy(ORM_Iterator $res){
    $syn = "";
    foreach ($res as $synonym) {
      $syn .= $synonym->term->term;
      $syn .=	($synonym->term->language_id != null) ?
        " | ".$synonym->term->language->iso."\n" :
        '';
    }
    return $syn;
  }

 /**
   * Returns an array of all values from this model and its super models ready to be
   * loaded into a form.
   * and the synonyms/common names.
   */
  protected function getModelValues() {
    $r = parent::getModelValues();

    // Add items to view
    $r = array_merge($r, array(
      'metaFields:synonyms' => $this->formatSynonomy($this->model->getSynonomy('meaning_id', $this->model->meaning_id))
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
      $r['termlists_term:termlist_id'] = $this->uri->argument(1);
      // Parent id might be passed in $_POST if creating as child of another term.
      if (array_key_exists('termlists_term:parent_id', $_POST)) {
        $r['termlists_term:parent_id']=$_POST['termlists_term:parent_id'];
      }
    }
    return $r;
  }

  /**
   * Reports if editing a term in term list is authorised.
   *
   * @param int $id Id of the termlists_term that is being checked, or null for a new record.
   */
  protected function record_authorised($id)
  {
    if ($id===null) {
      // Creating a new record, so the termlist id is an argument
      $list_id=$this->uri->argument(1);
    } else {
      $terms = new Termlists_Term_Model($id);
      // The id should already exist, otherwise the user is attempting to create by passing
      // a param to the edit function.
      if (!$terms->loaded) {
        return false;
      } else {
        $list_id=$terms->termlist_id;
      }
    }
    return ($this->termlist_authorised($list_id));
  }

  protected function termlist_authorised($id)
  {
    // for this controller, any null ID termlist can not be accessed
    if (is_null($id)) return false;
    $websites = $this->get_allowed_website_id_list('editor');
    if (!is_null($websites))
    {
      $termlist = new Termlist_Model($id);
      // for this controller, any termlist that does not exist can not be accessed.
      if (!$termlist->loaded) return false;
      return (in_array($termlist->website_id, $websites));
    }
    return true;
  }

  /**
   * Override the default return page behaviour so that after saving a term you
   * are returned to the list of terms on the sub-tab of the list.
   */
  protected function get_return_page() {
    if (array_key_exists('termlists_term:termlist_id', $_POST)) {
      // after saving a record, the list id to return to is in the POST data
      // user may select to continue adding new terms
      if (isset($_POST['what-next'])) {
        if ($_POST['what-next']=='add')
          return 'termlists_term/create/'.$_POST['termlists_term:termlist_id'];
      }
      // or just return to the list page
      return "termlist/edit/".$_POST['termlists_term:termlist_id']."?tab=terms";
    } elseif (array_key_exists('termlists_term:termlist_id', $_GET))
      // after uploading records, the list id is in the URL get parameters
      return "termlist/edit/".$_GET['termlists_term:termlist_id']."?tab=terms";
    else
      // last resort if we don't know the list, just show the whole lot of lists
      return $this->model->object_name;
  }
  
  /**
   * Return a list of the tabs to display for this controller's actions.
   */
  protected function getTabs($name) {
    return array(array(
      'views'=>'termlists_term/termlists_term_edit',
      'controller' => 'termlists_term/children',
      'title' => 'Child Terms',
      'actions'=>array('edit')
    ));
  }
  
  /**
   * AJAX controller method for the ability to add a term from a parent list into a child list.
   * Takes the child (destination) termlist id and the source termlist term id as parameters
   * in the $_POST data.
   * @todo Should make a base class for termlists_term and taxa_taxon_list to handle this sort of stuff. 
   */
  public function add_parent_term() {
    // no template as this is for AJAX
    $this->auto_render=false;
    // get the selected name
    $ttl = ORM::factory('termlists_term', $_POST['termlists_term_id']);
    // find a list of the term ids for this meaning which are already in the list.
    $existing = ORM::factory('termlists_term')->where(array(
        'termlist_id'=>$_POST['termlist_id'],
        'meaning_id'=>$ttl->meaning_id
    ))->find_all();
    $existingTaxa = array();
    foreach($existing as $item)
      $existingTaxa[] = $item->term_id;
    // we must copy across all names for the term not just the selected one
    $all_names = ORM::factory('termlists_term')->where(array(
      'termlist_id' => $ttl->termlist_id,
      'meaning_id' => $ttl->meaning_id
    ))->find_all();
    $existingCount = 0;
    $newCount = 0;
    // loop through the names

    foreach($all_names as $name) {
      $data = $name->as_array();
      if (in_array($data['term_id'], $existingTaxa))
        $existingCount++;
      else {
        unset($data['id']);
        $data['termlist_id']=$_POST['termlist_id'];
        // create a new model using the existing ttl data but a new list id
        $newttl = ORM::factory('termlists_term');
        $newttl->validate(new Validation($data), true);
        // we want to return the id of the preferred term copied over
        if ($newttl->preferred=='t')
          $prefId = $newttl->id;
        $newCount++;
      }
    }
    if (isset($prefId))
      echo $prefId;
    elseif ($newCount===0)
      echo 'The term already exists in the list.';
    elseif ($newCount>0)
      echo 'The term already exists in the list but some names were missing, so they have '.
        'been copied across.';
  }

}
?>
