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
 * Controller providing CRUD access to the images for a taxon
 *
 * @package	Core
 * @subpackage Controllers
 */
class Taxon_image_Controller extends Gridview_Base_Controller
{
	public function __construct()
  {
    parent::__construct('taxon_image', 'gv_taxon_image', 'taxon_image/index');
    $this->columns = array(
      'caption'=>'',
      'path'=>'Image'    
    );
    $this->pagetitle = "Images";
  }

  /**
  * Override the default page functionality to filter by taxa_taxon_list_id.
  */
  public function page($page_no, $filter=null)
  {  	
    $taxa_taxon_list_id=$filter;
    // At this point, $taxa_taxon_list_id has a value - the framework will trap the other case.
    // No further filtering of the gridview required as the very fact you can access the parent taxon
    // means you can access all the images for it.
    // However, the grid actually needs to be filtered by taxon_meaning_id.
    $ttl = ORM::Factory('taxa_taxon_list', $taxa_taxon_list_id);
    $this->base_filter['taxon_meaning_id'] = $ttl->taxon_meaning_id;   
    parent::page($page_no);
    $this->view->taxa_taxon_list_id = $taxa_taxon_list_id;
  }
  
  /**
   *  Setup the default values to use when loading this controller to create a new image.   
   */
  protected function getDefaults() {    
    $r = parent::getDefaults();    
    if ($this->uri->method(false)=='create') {
      // taxa_taxon_list id is passed as first argument in URL when creating. But the image
      // gets linked by meaning, so fetch the meaning_id.
      $ttl = ORM::Factory('taxa_taxon_list', $this->uri->argument(1)); 
      $r['taxa_taxon_list:id'] = $this->uri->argument(1);
      $r['taxon_image:taxon_meaning_id'] = $ttl->taxon_meaning_id;
      $r['taxon_image:caption'] = kohana::lang('misc.new_image');
    }
    return $r;
  }
  
  /**
   * Setup the default values to use when loading this controller to edit an existing image.   
   */
  protected function getModelValues() {    
    $r = parent::getModelValues();
    // The image is linked to a taxon meaning, but we need to use this to link back to the 
    // preferred taxa in taxon list, so when you save it knows where to go back to.
    $ttl = ORM::Factory('taxa_taxon_list')->where(array(
      'taxon_meaning_id' => $this->model->taxon_meaning_id,
      'preferred' => 'true'
    ))->find();
    $r['taxa_taxon_list:id'] = $ttl->id;
    return $r;
  }
  
/**
   * Override the default return page behaviour so that after saving an image you
   * are returned to the taxa_taxon_list entry which has the image.
   */
  protected function get_return_page() {
    if (array_key_exists('taxa_taxon_list:id', $_POST)) {
      return "taxa_taxon_list/edit/".$_POST['taxa_taxon_list:id']."?tab=images";
    } else {
      return $this->model->object_name;
    }
  }

}