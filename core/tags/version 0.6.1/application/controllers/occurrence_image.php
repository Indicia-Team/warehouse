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
    parent::__construct('occurrence_image', 'occurrence_image', 'occurrence_image/index');
    $this->columns = array(
      'caption'=>'',
      'path'=>'Image'
    );
    $this->pagetitle = "Images";
  }

  /**
  * Override the default page functionality to filter by occurrence_id.
  */
  public function page($page_no, $filter=null)
  {
    $occurrence_id=$filter;
    // At this point, $occurrence_id has a value - the framework will trap the other case.
    // No further filtering of the gridview required as the very fact you can access the parent occurrence
    // means you can access all the images for it.
    $this->base_filter['occurrence_id'] = $occurrence_id;
    parent::page($page_no);
    $this->view->occurrence_id = $occurrence_id;
  }

  /**
   * Method to retrieve pages for the index grid of taxa_taxon_list entries from an AJAX
   * pagination call. Overrides the base class behaviour to enforce a filter on the
   * taxon list id.
   */
  public function page_gv($page_no, $filter=null)
  {
    $occurrence_id=$filter;
    $this->base_filter['occurrence_id'] = $occurrence_id;
    return parent::page_gv($page_no);
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