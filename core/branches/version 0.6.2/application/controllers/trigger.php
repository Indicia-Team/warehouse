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
 * Controller for the trigger page.
 *
 * @package Core
 * @subpackage Controllers
 */
class Trigger_Controller extends Gridview_Base_Controller {

  /**
   * Constructor
   */
  public function __construct()
  {
    parent::__construct('trigger', 'gv_trigger', 'trigger/index');
    $this->columns = array(
      'name'=>'',
      'description'=>'',
      'created_by_name'=>'Owner'
    );
    $this->pagetitle = "Triggers";
    $this->base_filter['private_for_user_id'] = array(null, $_SESSION['auth_user']->id);
  }
  
  /**
   * Override to specify action columns for subscription.
   */
  protected function get_action_columns() {
    return array('edit trigger' => $this->controllerpath.'/edit/£id£',        
        'subscribe' => 'trigger_action/create/£id£',
        'edit subscription' => 'trigger_action/edit/£id£');
  }
  
  /**
   * Override to control the visibility of the edit trigger and subscription
   * actions depending on the circumstance and row data.
   */
  protected function get_action_visibility($row, $actionName) {
    if ($actionName == 'edit trigger') {      
      return $row['private_for_user_id'] == $_SESSION['auth_user']->id || $this->auth->logged_in('CoreAdmin');
    } else {
      // @todo Performance implications of this approach
      // Because we can't write view code that returns a trigger action record based on a parameter (the user ID)
      // we need to check the database at this point to decide if they are already subscribed.
      $filter = array('param1'=> "".$_SESSION['auth_user']->id."", 'trigger_id'=>$row['id'], 'deleted'=>'f');      
      $ta = $this->db
          ->select('id')
          ->from('trigger_actions')
          ->where($filter)
          ->limit(1)
          ->get();
      if ($actionName == 'subscribe') {      
        return $ta->count()===0; 
      } elseif ($actionName == 'edit subscription') {      
        return $ta->count()!==0; 
      }
    }
    return false;
  }
  
  /** 
  * Provide the list of trigger templates to the edit view
  */
  protected function prepareOtherViewData($values) {
    $files = Array();    
    $templateDir = DOCROOT.Kohana::config('indicia.localReportDir').'/trigger_templates/';
    $dh = opendir($templateDir);
    while ($file = readdir($dh))  {
      if ($file != '..' && $file != '.' && is_file($templateDir.$file))
      { 
        $file = str_replace('.xml', '', $file);
        $files["trigger_templates/$file"] = $file;
      }
    }
    return array('triggerFileList' => $files);
  }
  
  public function record_authorised($id)
  {
    return $this->auth->logged_in('CoreAdmin');
  }
  
  /**
   * Controller action to display the parameters editing page for the report associated with this
   * trigger. Displayed after clicking Next on the main edit page.
   */
  public function edit_params($id=null) {
    $this->model = ORM::Factory($this->model->object_name, $id);    
    if ($id) 
      // existing record, so we can get the params json data to convert it to individual params
      $params = json_decode($this->model->params_json, true);    
    else
      $params = array();
    
          
    $this->setView('trigger/params_edit', 'Parameters for '.$this->model->caption(), array(
      'values'=>$_POST,
      'other_data' => array('defaults' => $params)
    )); 
    $this->defineEditBreadcrumbs();
  }
  
  /**
   * Override the save method so we can convert the report parameters form into a JSON
   * value to store in the triggers.json_params field.
   */
  public function save() {
    // build the parameters JSON value from the params form
    $params = array();
    foreach($_POST as $key=>$value) {
      if (substr($key, 0, 6)=='param-') {
        // The last part of the key is the field name
        $tokens = explode('-', $key);
        $param = array_pop($tokens);
        $params[$param] = $value;
      }
    }
    $_POST['params_json'] = json_encode($params);   
    parent::save();
  }
  
}
?>
