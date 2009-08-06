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
    $this->pageNoUriSegment = 4;
    $this->model = ORM::factory('termlists_term');
  }

  private function formatCommonSynonomy(ORM_Iterator $res){
    $syn = "";
    foreach ($res as $synonym) {
      if ($synonym->term->language->iso != "lat"){
        $syn .= $synonym->term->term;
        $syn .=	($synonym->term->language_id != null) ?
          ",".$synonym->term->language->iso."\n" :
          '';
      }
    }
    return $syn;
  }
  /**
   * Override the default page functionality to filter by termlist.
   */
  public function page($termlist_id, $page_no, $limit){
    // At this point, $termlist_id has a value - the framework will trap the other case.
    // No further filtering of the gridview required as the very fact you can access the parent term list
    // means you can access all the terms for it.
    if (!$this->termlist_authorised($termlist_id))
    {
      $this->access_denied('table to view records with a termlist ID='.$termlist_id);
      return;
    }
    parent::page($page_no, $limit);
    $this->base_filter['termlist_id'] = $termlist_id;
    $this->pagetitle = "Terms in ".ORM::factory('termlist',$termlist_id)->title;
    $this->view->termlist_id = $termlist_id;
  }

  public function page_gv($termlist_id, $page_no, $limit){
    $this->base_filter['termlist_id'] = $termlist_id;
    $this->view->termlist_id = $termlist_id;
    parent::page_gv($page_no, $limit);
  }

  public function edit($id,$page_no,$limit) {
    // At this point, $id is provided - the framework will trap the empty or null case.
    if (!$this->record_authorised($id))
    {
      $this->access_denied('record with ID='.$id);
      return;
        }
    // Generate model
    $this->model->find($id);
    $gridmodel = ORM::factory('gv_termlists_term');

    // Add grid component
    $grid =	Gridview_Controller::factory($gridmodel,
        $page_no,
        $limit,
        4);
    $grid->base_filter = $this->base_filter;
    $grid->base_filter['parent_id'] = $id;
    $grid->columns = $this->columns;
    $grid->actionColumns = array(
      'edit' => 'termlists_term/edit/£id£'
    );

    // Add items to view
    $vArgs = array(
      'termlist_id' => $this->model->termlist_id,
      'table' => $grid->display(true),
      'synonomy' => $this->formatCommonSynonomy($this->
          getSynonomy($this->model->meaning_id)),
      );
    $this->setView('termlists_term/termlists_term_edit', 'Term', $vArgs);

  }
  // Auxilliary function for handling Ajax requests from the edit method gridview component
  public function edit_gv($id,$page_no,$limit) {
    $this->auto_render=false;

    $gridmodel = ORM::factory('gv_term_termlist');

    $grid =	Gridview_Controller::factory($gridmodel,
        $page_no,
        $limit,
        4);
    $grid->base_filter = $this->base_filter;
    $grid->base_filter['parent_id'] = $id;
    $grid->columns =  $this->columns;
    $grid->actionColumns = array(
      'edit' => 'termlists_term/edit/£id£'
    );
    return $grid->display();
  }
  /**
   * Creates a new term given the id of the termlist to initially attach it to
   */
  public function create($termlist_id){
    // At this point, $termlist_id has a value - the framework will trap the other case.
    if (!$this->termlist_authorised($termlist_id))
    {
      $this->access_denied('table to create records with a taxon list ID='.$termlist_id);
      return;
        }
    $parent = $this->input->post('parent_id', null);
    $this->model->parent_id = $parent;

    $vArgs = array(
      'table' => null,
      'termlist_id' => $termlist_id,
      'synonomy' => null);

    $this->setView('termlists_term/termlists_term_edit', 'Term', $vArgs);

  }

  public function save(){
    $_POST['preferred'] = 't';
    if (!is_numeric($_POST['language_id']))
          $_POST['language_id']=1; // English
    parent::save();
  }

  protected function wrap($array) {

    $sa = array(
      'id' => 'termlists_term',
      'fields' => array(),
      'fkFields' => array(),
      'superModels' => array(),
      'metaFields' => array()
    );

    // Declare which fields we consider as native to this model
    $nativeFields = array_intersect_key($array, $this->model->table_columns);

    // Use the parent method to wrap these
    $sa = parent::wrap($nativeFields);

    // Declare child models
    if (array_key_exists('meaning_id', $array) == false ||
      $array['meaning_id'] == '') {
        $sa['superModels'][] = array(
          'fkId' => 'meaning_id',
          'model' => parent::wrap(
            array_intersect_key($array, ORM::factory('meaning')
            ->table_columns), false, 'meaning'));
      }

    $termFields = array_intersect_key($array, ORM::factory('term')
      ->table_columns);
    if (array_key_exists('term_id', $array) && $array['term_id'] != ''){
      $termFields['id'] = $array['term_id'];
    }
    $sa['superModels'][] = array(
      'fkId' => 'term_id',
      'model' => parent::wrap($termFields, false, 'term'));

    $sa['metaFields']['synonomy'] = array(
      'value' => $array['synonomy']
    );

    return $sa;
  }

  /**
   * Overrides the fail functionality to add args to the view.
   */
  protected function submit_fail(){
    $mn = $this->model->object_name;
    $vArgs = array(
      'termlist_id' => $this->model->termlist_id,
      'synonomy' => null,
    );
    $this->setView($mn."/".$mn."_edit", ucfirst($mn), $vArgs);
  }

  protected function record_authorised ($id)
  {
    // note this function is not accessed when creating a record
    // for this controller, any null ID termlist_term can not be accessed
    if (is_null($id)) return false;
    $term = new Termlists_term_Model($id);
    // for this controller, any termlist_term that does not exist can not be accessed.
    // ie prevent sly creation using the edit function
    if (!$term->loaded) return false;
    return ($this->termlist_authorised($term->termlist_id));
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
}
?>
