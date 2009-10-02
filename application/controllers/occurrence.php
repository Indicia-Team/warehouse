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

 defined('SYSPATH') or die('No direct script access.');

/**
 * Controller providing CRUD access to the occurrence data.
 *
 * @package	Core
 * @subpackage Controllers
 */
class Occurrence_controller extends Gridview_Base_Controller {

  public function __construct()
  {
    parent::__construct('occurrence', 'gv_occurrence', 'occurrence/index');
    $this->pagetitle = 'Occurrences';
    $this->model = ORM::factory('occurrence');
    $this->actionColumns = array
    (
      'Edit Occ' => 'occurrence/edit/£id£',
      'Edit Smp' => 'sample/edit/£sample_id£'
    );
    $this->columns = array
    (
      'taxon' => 'Taxon',
      'entered_sref' => 'Spatial Ref',
      'entered_sref_system' => 'System',
      'recorder_names' => 'Recorder Names',
      'date' => 'Date'
    );
  }

  /**
  * Action for occurrence/create page/
  * Displays a page allowing entry of a new occurrence.
  */
  public function create()
  {
    if (!$this->page_authorised())
    {
      $this->access_denied();
    }
    else
    {
      $this->setView('occurrence/occurrence_edit', 'Occurrence');
    }
  }
  
/**
   * Returns an array of all values from this model and its super models ready to be 
   * loaded into a form. For this controller, we need to also setup the grid of comments and
   * list of images.
   */
  protected function getModelValues() {
    $r = parent::getModelValues();
    $gridmodel = ORM::factory('occurrence_comment');
    $grid = Gridview_Controller::factory(
        $gridmodel,	
        $this->uri->argument(3) || 1, // page number
        4 // limit
    );
    $grid->base_filter = array('occurrence_id' => $this->model->id, 'deleted' => 'f');
    $grid->columns = array('comment' => '', 'updated_on' => '');    
    $r['comments']=$grid->display();
    $r['images']=ORM::factory('occurrence_image')->where('occurrence_id', $this->model->id)->find_all();
    return $r;  
  }

  public function edit_gv($id = null, $page_no)
  {
    $this->auto_render = false;
    $gridmodel = ORM::factory('occurrence_comment');
    $grid = Gridview_Controller::factory($gridmodel,	$page_no, 4);
    $grid->base_filter = array('occurrence_id' => $id, 'deleted' => 'f');
    $grid->columns = array('comment' => '', 'updated_on' => '');

    return $grid->display();
  }

  public function save()
  {
    $_POST['confidential'] = isset($_POST['confidential']) ? 't' : 'f';
    parent::save();
  }
}