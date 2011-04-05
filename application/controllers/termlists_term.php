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
      'gv_termlists_term',
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
  * Override the default page functionality to filter by termlist.
  */
  public function page($page_no, $filter=null)
  {
    $termlist_id=$filter;
    // At this point, $termlist_id has a value - the framework will trap the other case.
    // No further filtering of the gridview required as the very fact you can access the parent termlist
    // means you can access all the taxa for it.
    if (!$this->termlist_authorised($termlist_id))
    {
      $this->access_denied('table to view records with a termlist ID='.$termlist_id);
      return;
    }
    $this->base_filter['termlist_id'] = $termlist_id;
    $this->pagetitle = "Species in ".ORM::factory('termlist',$termlist_id)->title;
    parent::page($page_no);
    $this->view->termlist_id = $termlist_id;
    $this->upload_csv_form->staticFields = array(
      'termlists_term:termlist_id' => $termlist_id
    );
    $this->upload_csv_form->returnPage = $termlist_id;
  }

  /**
   * Method to retrieve pages for the index grid of termlists_term entries from an AJAX
   * pagination call. Overrides the base class behaviour to enforce a filter on the
   * termlist id.
   */
  public function page_gv($page_no, $filter=null) {
    $termlist_id=$filter;
    $this->base_filter['termlist_id'] = $termlist_id;
    parent::page_gv($page_no);
  }

 /**
   * Returns an array of all values from this model and its super models ready to be
   * loaded into a form. For this controller, we need to also setup the child term grid
   * and the synonyms/common names.
   */
  protected function getModelValues() {
    $r = parent::getModelValues();

    $child_grid_html = $this->get_child_grid($this->model->id,
       is_numeric($this->uri->argument(3)) ? $this->uri->argument(3) : 1, // page number
       1 // limit
    );

    // Add items to view
    $r = array_merge($r, array(
      'table' => $child_grid_html,
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
    } else {
      if (array_key_exists('termlists_term:id', $_POST) && $_POST['termlists_term:id']) {
        $r['table'] = $this->get_child_grid($_POST['termlists_term:id'],
          is_numeric($this->uri->argument(3)) ? $this->uri->argument(3) : 1, // page number
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
   * Returns the HTML required for the grid of children of this term entry.
   *
   * @return string HTML for the grid.
   * @access private
   */
  private function get_child_grid($id,$page_no)
  {
    $gridmodel = ORM::factory('gv_termlists_term');

    $child_grid =	Gridview_Controller::factory(
        $gridmodel,
        $page_no,
        4
    );
    $child_grid->base_filter = $this->base_filter;
    $child_grid->base_filter['parent_id'] = $id;
    $child_grid->columns =  $this->columns;
    $child_grid->actionColumns = array(
      'edit' => 'termlists_term/edit/£id£'
    );
    return $child_grid->display();
  }

  /**
   * Reports if editing a term in term list is authorised.
   *
   * @param int $id Id of the termlists_term that is being checked, or null for a new record.
   */
  protected function record_authorised($id)
  {
    if ($id===null) {
      // Creating a new record, so the taxon list id is an argument
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

  protected function termlist_authorised ($id)
  {
    // for this controller, any null ID termlist can not be accessed
    if (is_null($id)) return false;
    if (!is_null($this->gen_auth_filter))
    {
      $termlist = new Termlist_Model($id);
      // for this controller, any termlist that does not exist can not be accessed.
      if (!$termlist->loaded) return false;
      return (in_array($termlist->website_id, $this->gen_auth_filter['values']));
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
}
?>
