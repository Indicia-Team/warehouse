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
    $this->actionColumns = array
    (
      'Edit Occ' => 'occurrence/edit/#id#',
      'Edit Smp' => 'sample/edit/#sample_id#'
    );
    $this->columns = array
    (
      'website' => 'Website',
      'survey' => 'Survey',
      'taxon' => 'Taxon',
      'entered_sref' => 'Spatial Ref',
      'date_start' => 'Date'
    );
    $this->auth_filter = $this->gen_auth_filter;
  }

  /**
  * Action for occurrence/create page/
  * Displays a page allowing entry of a new occurrence.
  */
  public function create()
  {
    $this->setView('occurrence/occurrence_edit', 'Occurrence');
  }

  /**
   * Override the index page controller action to add filters for the parent sample if viewing the child occurrences.
   */
  public function page($page_no, $filter=null) {
    // This constructor normally has 1 argument which is the grid page. If there is a second argument
    // then it is the parent list ID.
    if ($this->uri->total_arguments()>1) {
      $this->base_filter=array('sample_id' => $this->uri->argument(2));
    }
    parent::page($page_no, $filter);
  }
  
  /**
   * Returns an array of all values from this model and its super models ready to be 
   * loaded into a form. For this controller, we need to also setup the grid of comments and
   * list of images.
   */
  protected function getModelValues() {
    $r = parent::getModelValues();
    $this->loadAttributes($r);
    return $r;  
  }
  
  protected function getDefaults() {
    $r = parent::getDefaults();
    $this->loadAttributes($r);
    return $r;
  }
  
  public function save()
  {
    $_POST['confidential'] = isset($_POST['confidential']) ? 't' : 'f';
    parent::save();
  }
  
  /**
   * Return a list of the tabs to display for this controller's actions.
   */
  protected function getTabs($name) {
    return array(
      array(
        'controller' => 'occurrence_comment',
        'title' => 'Comments',
        'views'=>'occurrence',
        'actions'=>array('edit')
      ), array(
        'controller' => 'occurrence_image',
        'title' => 'Images',
        'views'=>'occurrence',
        'actions'=>array('edit')
      )
    );
  }

  /**
   * Check access to a occurrence when editing. The occurrence's website must be in the list
   * of websites the user is authorised to administer.
   */
  protected function record_authorised ($id)
  {
    if (!is_null($id) AND !is_null($this->auth_filter))
    {
      $occ = new Occurrence_Model($id);
      return (in_array($occ->website_id, $this->auth_filter['values']));
    }
    return true;
  }
}