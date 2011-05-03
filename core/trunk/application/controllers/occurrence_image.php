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
 * Controller providing CRUD access to the images for an occurrence image
 *
 * @package	Core
 * @subpackage Controllers
 */
class Occurrence_image_Controller extends Gridview_Base_Controller
{
	public function __construct()
  {
    parent::__construct('occurrence_image', 'occurrence_image/index');
    $this->columns = array(
      'caption'=>'',
      'path'=>'Image'
    );
    $this->pagetitle = "Images";
  }

  /**
  * Override the default index functionality to filter by occurrence_id.
  */
  public function index()
  { 
    if ($this->uri->total_arguments()>0) {
      $this->base_filter=array('occurrence_id' => $this->uri->argument(1));
    }
    parent::index();
    // pass the location id into the view, so the create button can use it to autoset
    // the location of the new image.
    if ($this->uri->total_arguments()>0) {
      $this->view->occurrence_id=$this->uri->argument(1);;
    }
  }

  /**
   *  Setup the default values to use when loading this controller to edit a new page.
   */
  protected function getDefaults() {
    $r = parent::getDefaults();
    if ($this->uri->method(false)=='create') {
      // occurrence id is passed as first argument in URL when creating. But the image
      // gets linked by meaning, so fetch the meaning_id.
      $r['occurrence:id'] = $this->uri->argument(1);
      $r['occurrence_image:occurrence_id'] = $this->uri->argument(1);
      $r['occurrence_image:caption'] = kohana::lang('misc.new_image');
    }
    return $r;
  }

  /**
   * Override the default return page behaviour so that after saving an image you
   * are returned to the occurence entry which has the image.
   */
  protected function get_return_page() {
    if (array_key_exists('occurrence_image:occurrence_id', $_POST)) {
      return "occurrence/edit/".$_POST['occurrence_image:occurrence_id']."?tab=images";
    } else {
      return $this->model->object_name;
    }
  }

}