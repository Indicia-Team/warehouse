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
 * @package Core
 * @subpackage Controllers
 * @author	Indicia Team
 * @link http://code.google.com/p/indicia/
 * @license http://www.gnu.org/licenses/gpl.html GPL
 */

/**
 * Controller for the trigger action (subscription) page.
 *
 * @package Core
 * @subpackage Controllers
 */
class Trigger_Action_Controller extends Gridview_Base_Controller {

  /**
   * Constructor
   */
  public function __construct()
  {
    parent::__construct('trigger_action', 'trigger/index');
    $this->columns = array(
      'name'=>'',
      'description'=>'',
      'created_by_name'=>'Owner');
    $this->pagetitle = "Subscriptions";
  }
  
  /**
   * Default behaviour for the edit page breadcrumbs. Can be overrridden.
   */
  protected function defineEditBreadcrumbs() { 
    $this->page_breadcrumbs[] = html::anchor('trigger', 'Triggers');
    $this->page_breadcrumbs[] ='Subscribe';
  }
  
  /**
   * Provide default values for a new notification.
   */
  protected function getDefaults() {
    return array(
      // for a trigger action, param1 should be the user id.
      'trigger_action:param1' => $_SESSION['auth_user']->id,
      'trigger_action:param2' => 'D',
      'trigger_action:trigger_id' => $this->uri->last_segment()
    );
  }
  
  /** 
  * Override the edit method, since we are passed a trigger id, whereas we need to edit the
  * associated action id.  
  */
  public function edit($id) {
    $filter = array('param1'=> "".$_SESSION['auth_user']->id."", 'trigger_id'=>$id, 'deleted'=>'f');      
    $ta = $this->db
        ->select('id')
        ->from('trigger_actions')
        ->where($filter)
        ->limit(1)
        ->get()->result_array();    
    parent::edit($ta[0]->id);
  }
}
?>
