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
      'title' => 'Survey',
    	'entered_sref' => 'Spatial Ref.',
      'location' => 'Location',
      'date_start' => 'Date'
    );
    $this->auth_filter = $this->gen_auth_filter;
  }

  protected function getModelValues() {
    $r = parent::getModelValues();
    $this->loadOccurrences($r);
    $this->loadAttributes($r);
    $r['website_id']=ORM::factory('survey', $r['sample:survey_id'])->website_id;
    return $r;      
  }
  
  /**
   * Load default values either when creating a sample new or reloading after a validation failure.
   * This adds the custome attributes list to the data available for the view. 
   */
  protected function getDefaults() {
    $r = parent::getDefaults();
    if (array_key_exists('sample:id', $_POST)) { 
      $this->loadOccurrences($r);
    }
    $this->loadAttributes($r);
    if (array_key_exists('sample:survey_id', $_POST)) { 
      $this->loadOccurrences($r);
      $r['sample:survey_id'] = $_POST['sample:survey_id'];
      $r['website_id']=ORM::factory('survey', $r['sample:survey_id'])->website_id;
    }
    return $r;
  }
  
  /** 
   * Loads the list of occurrences for the sample into a grid.
   */
  private function loadOccurrences(&$r) {
  	$occ_gridmodel = ORM::factory('gv_occurrence');
    $occ_grid = Gridview_Controller::factory(
        $occ_gridmodel,
        $this->uri->argument(3) || 1, // page number,
        4
     );
    $occ_grid->base_filter = array('sample_id' => $this->model->id, 'deleted' => 'f');
    $occ_grid->columns = array('taxon' => '');
    $occ_grid->actionColumns = array('edit' => 'occurrence/edit/£id£');
    $r['occurrences'] = $occ_grid->display();
  }
  
  /**
   * Loads the custom attributes for this sample into the load array. Also sets up
   * any lookup lists required.
   */
  private function loadAttributes(&$r) {
    // Grab all the custom attribute data
    $attrs = $this->db->
        from('list_sample_attribute_values')->
        where('sample_id', $this->model->id)->
        get()->as_array(false);
    $r['attributes'] = $attrs;
    foreach ($attrs as $attr) {
      // if there are any lookup lists in the attributes, preload the options    	
      if (!empty($attr['termlist_id'])) {
        $r['terms_'.$attr['termlist_id']]=$this->get_termlist_terms($attr['termlist_id']);
        $r['terms_'.$attr['termlist_id']][0] = '-no value-';
      }
    }
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
  protected function prepareOtherViewData($values)
  {    
    return array(
      'method_terms' => $this->get_termlist_terms('indicia:sample_methods')    
    );   
  }
}
