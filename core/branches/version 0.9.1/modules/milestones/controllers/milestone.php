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
 * @package    Milestones
 * @subpackage Controllers
 * @author    Indicia Team
 * @license    http://www.gnu.org/licenses/gpl.html GPL
 * @link     http://code.google.com/p/indicia/
 */

/*
 * Controller class for the milestones plugin module.
 */
class Milestone_Controller extends Gridview_Base_Controller {
  public function __construct() {
    parent::__construct('milestone');
    $this->pagetitle = "Milestones";
    //Contruct the grid columns that will appear on the websites page milestones tab.
    $this->columns = array(
      'id'          => '',
      'title'       => '',
      'awarded_by'  => ''
    );
    $this->set_website_access('admin');
  }
  
  public function index($filter = null) {
    if ($this->uri->total_arguments()>0) {
      $this->website_id = $this->uri->argument(1);
      $this->base_filter['website_id'] = $this->website_id;
    }
    parent::index();
  }
 
  /*
   * Define what happens when the user clicks to edit a milestone as we need to give the page the filter_id from the grid
   * view as a $_GET so it knows which filter to display
   */
  protected function get_action_columns() {
    return array(
      array(
        'caption'=>'edit',
        'url'=>"milestone/edit/{id}?filter_id={filter_id}"
      ),
    );
  }
  
  /**
   *  Setup the default values to use when loading this controller to edit a new page.
   */
  protected function getDefaults() {
    $r = parent::getDefaults();
    if ($this->uri->method(false)=='create') {
      // Website id is passed as first argument in URL when creating
      $r['milestone:website_id'] = $this->uri->argument(1);
    }
    return $r;
  }
  
  /**
   * Override the default return page behaviour so that after saving a milestone you
   * are returned to the list of milestones on the sub-tab of the website.
   */
  protected function get_return_page() {
    if (array_key_exists('website_id', $_POST)) {
      //just return to the website page
      return "website/edit/".$_POST['website_id']."?tab=Milestones";
    } elseif (array_key_exists('website_id', $_GET)) {    
      return "website/edit/".$_GET['website_id']."?tab=Milestones";
    } else {
      // last resort if we don't know the list, just show the whole lot of milestones
      return $this->model->object_name;
      
    }
  }
}
?>