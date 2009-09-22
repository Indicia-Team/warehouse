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
 * Controller providing CRUD access to the samples list.
 *
 * @package	Core
 * @subpackage Controllers
 */
class Sample_Controller extends Gridview_Base_Controller
{
  public function __construct()
  {
    parent::__construct('sample', 'gv_sample', 'sample/index');
    $this->pagetitle = 'Samples';
    $this->model = ORM::factory('sample');
    $this->columns = array
    (
      'entered_sref' => 'Spatial Ref.',
      'location' => 'Location',
      'location_name' => 'Location Name',
      'vague_date' => 'Date',
      'recorder_names' => 'Recorder Names',
    );
  }

  protected function getModelValues() {
    $r = parent::getModelValues();
    $gridmodel = ORM::factory('gv_occurrence');
    $grid = Gridview_Controller::factory(
        $gridmodel,
        $this->uri->argument(3) || 1, // page number,
        4
     );
    //$grid->base_filter = array('sample_id' => $this->model->id, 'deleted' => 'f');
    $grid->columns = array('taxon' => '');
    $grid->actionColumns = array('edit' => 'occurrence/edit/£id£');
    $r['occurrences'] = $grid->display();
    return $r;      
  }

  public function edit_gv($id = null, $page_no)
  {
    $this->auto_render = false;
    $gridmodel = ORM::factory('gv_occurrence');
    $grid = Gridview_Controller::factory($gridmodel, $page_no, 4);
    $grid->base_filter = array('sample_id' => $id, 'deleted' => 'f');
    $grid->columns = array('taxon' => '');

    return $grid->display();
  }

  /**
   * Get the list of terms ready for the sample methods list. 
   */
  protected function prepareOtherViewData()
  {    
    return array(
      'method_terms' => $this->get_termlist_terms('indicia:sample_methods')    
    );   
  }
}
