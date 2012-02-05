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
 * Controller providing CRUD access to the relationships for a taxon
 *
 * @package  Core
 * @subpackage Controllers
 */
class Taxon_relation_Controller extends Gridview_Base_Controller
{
  public function __construct()
  {
    parent::__construct('taxon_relation');
    $this->columns = array(
      'my_taxon'=>'',
      'term'=>'',
      'other_taxon'=>''
    );
    $this->pagetitle = "Relationships";
  }
  
 /**
  * Override the default index functionality to filter by sample_id.
  */
  public function index()
  { 
    if ($this->uri->total_arguments()>0) {
      $taxa_taxon_list_id = $this->uri->argument(1);
      $ttl = ORM::factory('taxa_taxon_list', $taxa_taxon_list_id);
      $this->base_filter=array('my_taxon_meaning_id' => $ttl->taxon_meaning_id);
    }
    parent::index();
    // pass the sample id into the view, so the create button can use it to autoset
    // the sample of the new image.
    if ($this->uri->total_arguments()>0) {
      $this->view->taxa_taxon_list_id=$taxa_taxon_list_id;
    }
  }

  /**
  * Override the default page functionality to filter by taxon_id.
  */
  public function page($page_no, $filter=null)
  {
    $taxa_taxon_list_id=$filter;
    // At this point, $taxa_taxon_list_id has a value - the framework will trap the other case.
    // No further filtering of the gridview required as the very fact you can access the parent taxon
    // means you can access all the relationships for it.
    // However, the grid actually needs to be filtered by taxon_meaning_id.
    $ttl = ORM::Factory('taxa_taxon_list', $taxa_taxon_list_id);
    $this->base_filter['my_taxon_meaning_id'] = $ttl->taxon_meaning_id;
    parent::page($page_no);
    $this->view->taxa_taxon_list_id = $taxa_taxon_list_id;
  }

  /**
   *  Setup the default values to use when loading this controller to create a new relationship.
   */
  protected function getDefaults() {
    $r = parent::getDefaults();
    if ($this->uri->method(false)=='create') {
      // taxa_taxon_list id is passed as first argument in URL when creating. But the relationship
      // gets linked by meaning, so fetch the meaning_id.
      $ttl = ORM::Factory('taxa_taxon_list', $this->uri->argument(1));
      $t = ORM::Factory('taxon', $ttl->taxon_id);
      $r['taxa_taxon_list:id'] = $this->uri->argument(1);
      $r['taxon_relation:from_taxon_meaning_id'] = $ttl->taxon_meaning_id;
      $r['taxon_relation:to_taxon_meaning_id'] = '';
      $r['taxon_relation:taxon_relation_type_id'] = '';
      $r['taxon:from_taxon'] = $t->taxon;
      $r['relation:term'] = '';
      $r['taxon:to_taxon'] = '';
    }
    return $r;
  }

  /**
   *  Setup the default values to use when loading this controller to edit an existing relation.
   */
  protected function getModelValues() {
    $r = parent::getModelValues();
    // The relation is linked to a taxon meaning, but we need to use this to link back to the
    // preferred taxa in taxon list, so when you save it knows where to go back to.
    $ttl = ORM::Factory('taxa_taxon_list')->where(array(
      'taxon_meaning_id' => $this->model->from_taxon_meaning_id,
      'preferred' => 'true'
    ))->find();
    $r['taxa_taxon_list:id'] = $ttl->id; // use this as default.
    $t = ORM::Factory('taxon', $ttl->taxon_id);
    $r['taxon:from_taxon'] = $t->taxon;
    $ttl = ORM::Factory('taxa_taxon_list')->where(array(
      'taxon_meaning_id' => $this->model->to_taxon_meaning_id,
      'preferred' => 'true'
    ))->find();
    $t = ORM::Factory('taxon', $ttl->taxon_id);
    $r['taxon:to_taxon'] = $t->taxon;
    $tr = ORM::Factory('taxon_relation_type', $this->model->taxon_relation_type_id);
    $r['relation:term'] = $tr->forward_term;
    // Other entities can just look up the taxa_taxon_list id, but we have 2 so can't, so mangle the referring URL.
    $referer = explode('/', $_SERVER["HTTP_REFERER"]);
    for($i=0; $i< count($referer); $i++){
      if($referer[$i] == 'taxa_taxon_list' && $referer[$i+1] == 'edit'){
        $value = explode('?', $referer[$i+2]);
        $r['taxa_taxon_list:id'] = $value[0];
        break;
      }
    }
    return $r;
  }

/**
   * Override the default return page behaviour so that after saving an relation you
   * are returned to the taxa_taxon_list entry which has the relation.
   */
  protected function get_return_page() {
    if (array_key_exists('taxa_taxon_list:id', $_POST)) {
      return "taxa_taxon_list/edit/".$_POST['taxa_taxon_list:id']."?tab=relations";
    } else {
      return $this->model->object_name;
    }
  }

}